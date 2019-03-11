<?php
namespace App\Parser;

use App\Lib\Parser\Parser;
use App\Http\Request;
use App\Helpers\StringHelper;
use App\db\Tools\DBQuery;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class RozetkaParser extends Parser {
    /**
     * Limit of pages for parsing.
     */
    protected $categoriesUrl = [];
    protected $request;
    protected $pagesCount;
    protected $currentPage;
    private $logger;

    public function __construct($categoriesUrl = [])
    {
        $this->request = new Request();

        $this->categoriesUrl = $categoriesUrl;
        
        $this->logger = new Logger('parser-channel');
        $this->logger->pushHandler(new StreamHandler(LOGS.'/parser.log', Logger::DEBUG));
    }

    public function parse($forCat = '', $startFromPage = 0): void
    {
        $this->logger->info('Parsing start...');
        foreach ($this->categoriesUrl as $categoryUrl) {
            /**
             * clear pages after starting parse new category
             */
            $this->logger->info('Category: ' . $categoryUrl);

            $this->pagesCount = null;

            $resultItems = [];

            $currentURL = $categoryUrl;
            
            $this->currentPage = (!empty($forCat) && $forCat === $currentURL && (bool)$startFromPage) ? $startFromPage : 1;

            if (! isset($this->pagesCount)) {
                $htmlPage = $this->request->makeRequest($currentURL);

                $dom = new \DOMDocument();
                $dom->loadHtml($htmlPage);
                $xpath = new \DomXPath($dom);

                $queryPagination = $xpath->query("//a[contains(@class, 'paginator-catalog-l-link')]");
                if ($queryPagination->length > 0)
                    $this->pagesCount = $queryPagination[$queryPagination->length - 1]->nodeValue;
                
                $queryCategories = $xpath->query("//ul[contains(@class, 'breadcrumbs-catalog')]/li[position()>1]");
                
                $parentCategoryId = NULL;
                if ($queryCategories->length > 0) {
                    foreach ($queryCategories as $i => $node) {
                        $catName = StringHelper::enRussian(trim($node->nodeValue));

                        $categoriesRes = DBQuery::select('categories', [['name', 'LIKE', $catName]]);
                        $categoryCount = count($categoriesRes);

                        if (! (bool)$categoryCount) {
                            $catId = DBQuery::insert('categories', [
                                'parent_id' => $parentCategoryId,
                                'name' => $catName
                            ]);
                            
                            $parentCategoryId = $catId;
                        } else {
                            $parentCategoryId = $categoriesRes[0]['id'];
                        }
                    }
                }

                $this->logger->info('Category pages number succesfully computed. Pages count: ' . $this->pagesCount);                    
                unset($htmlPage);
            }

            while ($this->currentPage <= $this->pagesCount) {
                $this->logger->info('Page number: ' . $this->currentPage);
                file_put_contents(LOGS.'/pageCounter.txt', $this->currentPage . PHP_EOL, FILE_APPEND);
                $htmlPage = $this->request->makeRequest($currentURL . ($this->currentPage > 1 ? 'page=' . $this->currentPage . '/' : ''));
                $this->currentPage = $this->currentPage + 1;

                try {
                    if (! $htmlPage){
                        throw new \Exception('Cannot retrieve html page data');
                    }
                } catch (\Exception $e) {
                    http_response_code(500);
                    echo sprintf("<b style='font-size: 22px'>Error!</b><br><b>File: %s</b><br><b>Line: %d</b><br><b>Message:</b> %s<hr>", $e->getFile(), $e->getLine(), $e->getMessage());
                    $this->logger->error($e->getMessage());
                    exit();
                }

                $items = $this->parseItemsList($htmlPage);

                foreach ($items as $item) {
                    $htmlPageProduct = $this->request->makeRequest($item . 'characteristics/');

                    try {
                        if (! $htmlPageProduct){
                            throw new \Exception('Cannot retrieve html page data');
                        }
                    } catch (\Exception $e) {
                        http_response_code(500);
                        echo sprintf("<b style='font-size: 22px'>Error!</b><br><b>File: %s</b><br><b>Line: %d</b><br><b>Message:</b> %s<hr>", $e->getFile(), $e->getLine(), $e->getMessage());
                        $this->logger->info($e->getMessage());
                        exit();
                    }

                    unset($items);

                    $product = $this->parseProductItem($htmlPageProduct, $item);
                    $this->logger->info('Product data succesfully parsed');
                    
                    $category_id = DBQuery::insert('categories', [
                        'parent_id' => $parentCategoryId,
                        'name' => $product['category']
                    ]);
                        
                    if ((bool)$category_id) {
                        $this->logger->info('Category "' . $product['category'] . '" succesfully added. ID: ' . $category_id);
                    }
                    
                    if ($category_id == 0) {
                        $category_id = DBQuery::select('categories', [['name', 'like', $product['category']]], ['id'])[0]['id'];
                        $this->logger->info('Category "' . $product['category'] . '" already exists. ID: ' . $category_id);                    
                    }

                    $product_id = DBQuery::insert('items', [
                        'sku'   => $product['sku'],
                        'price' => $product['price'],
                        'name' => $product['title'],
                        'category_id' => $category_id,
                        'description' => $product['description'],
                        'attributes' => json_encode($product['attributes']),
                        'seller' => $product['seller'],
                        'isOnTop' => $product['topSale']
                    ]);

                    if ((bool)$product_id) {
                        $this->logger->info('Product "' . $product['title'] . '" succesfully inserted. ID: ' . $product_id);                    
                    }

                    if ($product_id == 0) {
                        $product_id = DBQuery::select('items', [['name', 'like', $product['title']]], ['id'])[0]['id'];
                        $this->logger->info('Product "' . $product['title'] . '" already exists. ID: ' . $product_id);                    

                    }

                    foreach ($product['images'] as $image) {
                        DBQuery::insert('images', [
                            'product_id' => $product_id,
                            'link'       => $image
                        ]);
                    }
                    unset($product);
                }
            }
            
            $this->logger->info('Parse category ended');
        }
        $this->logger->info('Parse ended');

    }
    /**
     * Using XPath to get products list links.
     *
     * @param string $html Variable containing html code of current category page.
     */
    protected function parseItemsList(string $html): array
    {   
        $this->logger->info('Start parsing product links...');

        $dom = new \DOMDocument();
        $dom->loadHtml($html);
        $xpath = new \DomXPath($dom);

        $queryProducts = $xpath->query("//div[@name='goods_list_container']//div[@class='g-i-tile-i-box-desc']/div[contains(@class, 'g-i-tile-i-title')]/a");
        
        $onPageItems = [];

        if ($queryProducts->length > 0) {
            foreach ($queryProducts as $link) {
                $attr = $link->attributes[0];
                if ($attr->name === 'href') {
                    $onPageItems[] = $attr->value;
                }
            }
        }

        $this->logger->info('Product links succesfully parsed!');
        $this->logger->info('Links:');

        foreach ($onPageItems as $links) {
            $this->logger->info($links);
        }

        return $onPageItems;
    }

    protected function parseProductItem(string $html, $link): array
    {
        $product = [
            'category' => '',
            'images' => [],
            'title' => '',
            'attributes' => []
        ];
        $dom = new \DOMDocument();
        $dom->loadHtml($html);
        $xpath = new \DomXPath($dom);

        // Get product category
        $queryCategory = $xpath->query("//ul[@name='breadcrumbs']/li[last()]");
        if ($queryCategory->length > 0) {
            $product['category'] = StringHelper::enRussian($queryCategory[0]->nodeValue);
        }
        
        // Get product images
        $images = $this->getProductImages($link);

        $product['images'] = $images;

        $queryProductCharacteristics = $xpath->query("//div[contains(@class, 'pp-characteristics-tab')]");

        if ($queryProductCharacteristics->length > 0) {
            foreach ($queryProductCharacteristics as $q) {
                //Get product title
                $queryProductTitle = $xpath->query("//span[@class='pp-characteristics-tab-product-name']", $q);

                if ($queryProductTitle->length > 0)
                    $product['title'] = StringHelper::enRussian($queryProductTitle[0]->nodeValue);
                $queryProductAttributes = $xpath->query("//table[@class='chars-t']", $q); 
    
                // Get product characteristics from table            
                if ($queryProductAttributes->length > 0) {
                    foreach ($queryProductAttributes[0]->childNodes as $tr) {
                        list($key, $value) = [StringHelper::enRussian($tr->childNodes[0]->nodeValue), StringHelper::enRussian($tr->childNodes[2]->nodeValue)];
                        $product['attributes'][$key] = $value;
                    }
                }
            }
        }
        

        $mainData = $this->getProductMainData($link);
        $product['description'] = $mainData['description'];
        $product['seller'] = $mainData['seller'];
        $product['topSale'] = $mainData['topSale'];
        $product['price'] = $mainData['price'];
        $product['sku'] = $mainData['sku'];
        
        return $product;
    }

    /**
     * @param string $link
     * Getting images from product page
     * @return array
     */
    private function getProductImages(string $link): array
    {
        $imagesLinks = [];

        $dom = new \DOMDocument();
        $imagePage = $this->request->makeRequest($link . 'photo/');
        $dom->loadHtml($imagePage);
        $xpath = new \DomXPath($dom);
        
        $queryImages = $xpath->query("//div[@class='pp-photo-tab-i-foto']/*");
        
        if ($queryImages->length > 0) {
            foreach ($queryImages as $i => $imageNode) {
                $imagesLinks[] = $imageNode->childNodes[1]->attributes[0]->value;
            }
        }
        
        return $imagesLinks;
    }

    /**
     * @param string $link
     * 
     * @return string
     */
    private function getProductMainData(string $link): array
    {
        $mainData = [
            'description' => '',
            'seller' => '',
            'topSale' => false,
            'price'   => 0,
            'sku'     => ''
        ];
        
        $dom = new \DOMDocument();
        $mainProductInfo = $this->request->makeRequest($link);
        $dom->loadHtml($mainProductInfo);
        $xpath = new \DomXPath($dom);

        $queryDescription = $xpath->query("//div[@id='short_text']/*");
        
        if ($queryDescription->length > 0) {
            foreach ($queryDescription as $descPart) {
                $mainData['description'] .= StringHelper::enRussian($dom->saveHTML($descPart));
            }
        }

        $querySeller = $xpath->query("//span[@class='safe-merchant-label-title']");

        if ($querySeller->length > 0) {
            $mainData['seller'] = StringHelper::enRussian($querySeller[0]->nodeValue);
        }
        
        $queryTopSale = $xpath->query("//img[contains(@class, 'g-tag-icon-large-popularity')]");
        $mainData['topSale'] = (bool) $queryTopSale->length;

        $queryPrice = $xpath->query("//meta[@itemprop='price']");
        
        if ($queryPrice->length > 0) {
            $mainData['price'] = $queryPrice[0]->attributes[1]->nodeValue;
        }

        $querySKU = $xpath->query("//span[@name='goods_code']");

        if ($querySKU->length > 0) {
            $mainData['sku'] = $querySKU[0]->nodeValue;
        }
        
        if ($querySeller->length > 0) {
            $mainData['seller'] = StringHelper::enRussian($querySeller[0]->nodeValue);
        }
        

        return $mainData;
    }

    private function parseCategories(string $link): array
    {
        return [];
    }
}