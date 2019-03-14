<?php
namespace App\Parser;

use App\Lib\Parser\Parser;
use App\Http\ProxyRequest;
use App\Helpers\StringHelper;
use App\db\Tools\DBQuery;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class RozetkaParser extends Parser {
    /**
     * Limit of pages for parsing.
     */
    protected $categoryUrl = '';
    protected $request;
    protected $pagesCount;
    protected $currentPage;
    private $logger;

    public function __construct($categoryUrl = [])
    {
        $this->request = new ProxyRequest();

        $this->categoryUrl = $categoryUrl;
        
        // Create channel for parsing logs
        $this->logger = new Logger('parser-channel');
        $this->logger->pushHandler(new StreamHandler(LOGS . '/parser.log', Logger::DEBUG));
    }

    public function parse(string $forCat = '', int $startFromPage = 0): void
    {
        $this->logger->info('Parsing start...');

        /**
         * clear pages after starting parse new category
         */
        $this->logger->info('Category: ' . $this->categoryUrl);

        $this->pagesCount = null;

        $resultItems = [];

        $currentURL = $this->categoryUrl;
        
        /**
         * Set current page.
         * If set parametres $forCat and $startFromPage then set to it
         */
        $this->currentPage = (!empty($forCat) && $forCat === $currentURL && (bool)$startFromPage) ? $startFromPage : 1;

        /**
         * If $pagesCount not isset then make request to main category page
         * and grab it
         * Also Truncate config table that contain parameters: pagesCount and productsCount
         * It is need for ajax progress bar
         */
        if (! isset($this->pagesCount)) {
            $htmlPage = $this->request->makeRequest($currentURL);
            $this->logger->error($this->request->lastHTTPCode);
            $this->logger->error($this->request->lastErrorCode);
            $dom = new \DOMDocument();
            $dom->loadHtml($htmlPage);
            $xpath = new \DomXPath($dom);

            try {
                if (! $htmlPage){
                    throw new \Exception('Cannot retrieve html page data');
                }
            } catch (\Exception $e) {
                http_response_code(500);
                include VIEWS_PATH . 'errors/error.php';
                $this->logger->error($e->getMessage());
                exit();
            }

            // get paginator
            $queryPagination = $xpath->query("//a[contains(@class, 'paginator-catalog-l-link')]");
            if ($queryPagination->length > 0)
                $this->pagesCount = $queryPagination[$queryPagination->length - 1]->nodeValue;

            $this->logger->info('Category pages number succesfully computed. Pages count: ' . $this->pagesCount);
            
            DBQuery::raw("TRUNCATE table config;");
            DBQuery::insert('config', [
                'pagesCount'    => $this->pagesCount - ($this->currentPage - 1),
                'productsCount' => 0,
                'complete'     => 0,
            ]);

        }

        /**
         * loop page-by-page
         */
        while ($this->currentPage <= $this->pagesCount) {
            $this->logger->info('Page number: ' . $this->currentPage);
            //file_put_contents(LOGS.'/pageCounter.txt', $this->currentPage . PHP_EOL, FILE_APPEND);
            
            /**
             * Add page param to url
             * If url it is landing page then add another way.
             * $section param need for getting product links in future for /landing-pages/
             */
            $urlPage = $currentURL . ($this->currentPage > 1 ? 'page=' . $this->currentPage . '/' : '');

            $section = false; 
            
            if (mb_strpos(parse_url($currentURL)['path'], "landing-pages")) {
                // Add page param another way
                $section = true;
                $urlPage = str_replace("/landing-pages/", "/landing-pages/" . ($this->currentPage > 1 ? 'page=' . $this->currentPage . ';' : ''), $currentURL);
            }

            // get page with products
            if ($urlPage !== $currentURL) {
                $htmlPage = $this->request->makeRequest($urlPage);
            }

            $this->currentPage = $this->currentPage + 1;

            try {
                if (! $htmlPage){
                    throw new \Exception('Cannot retrieve html page data');
                }
            } catch (\Exception $e) {
                http_response_code(500);
                include VIEWS_PATH . 'errors/error.php';
                $this->logger->error($e->getMessage());
                exit();
            }

            /**
             * get product links for parse every one
             */
            $items = $this->parseItemsList($htmlPage, $section);

            unset($htmlPage);
            /**
             * make request for every product
             */
            foreach ($items as $item) {
                // characteristics page
                $htmlPageProduct = $this->request->makeRequest($item . 'characteristics/');

                try {
                    if (! $htmlPageProduct){
                        throw new \Exception('Cannot retrieve html page data');
                    }
                } catch (\Exception $e) {
                    http_response_code(500);
                    include VIEWS_PATH . 'errors/error.php';
                    $this->logger->info($e->getMessage());
                    exit();
                }

                unset($items);
                
                /**
                 * Get all product data
                 */
                $product = $this->parseProductItem($htmlPageProduct, $item);
                unset($htmlPageProduct);

                $this->logger->info('Product data succesfully parsed');
                
                $this->insertProductData($product);
                
                DBQuery::raw("UPDATE config SET productsCount = productsCount+1");

                unset($product);
            }
        }
        DBQuery::raw("UPDATE config SET complete = 1");
        $this->logger->info('Parse category ended');
        $this->logger->info('Parse ended');

    }
    /**
     * Using XPath to get products list links.
     *
     * @param string $html Variable containing html code of current category page.
     */
    protected function parseItemsList(string $html, bool $section = false): array
    {   
        $this->logger->info('Start parsing product links...');

        $dom = new \DOMDocument();
        $dom->loadHtml($html);
        $xpath = new \DomXPath($dom);

        $mainBlockId = ["@name='goods_list_container'", "@id='catalog_goods_block'"];
        $queryProducts = $xpath->query("//div[" . $mainBlockId[intval($section)] . "]//div[@class='g-i-tile-i-box-desc']/div[contains(@class, 'g-i-tile-i-title')]/a");
        
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

        unset($dom, $xpath);

        return $onPageItems;
    }

    /**
     * Get all product info from page 
     * 
     * @param string $html
     * @param string $link
     * 
     * @return array
     */
    protected function parseProductItem(string $html, string $link): array
    {
        $product = [
            'category'    => '',
            'images'      => [],
            'title'       => '',
            'attributes'  => [],
            'description' => '',
            'seller'      => '',
            'topSale'     => 0,
            'price'       => 0,
            'sku'         => '',
        ];
        
        $dom = new \DOMDocument();
        $dom->loadHtml($html);
        $xpath = new \DomXPath($dom);

        unset($dom);

        $this->parseAndInsertCategoriesTree($xpath);

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

                $queryProductAttributes = $xpath->query("table[@class='chars-t']/tr[position()>1][not(@colspan)]", $q); 
    
                /**
                 * Get product characteristics from table
                 */
                if ($query->length > 0) {
                    // file_put_contents(ROOT.'/table.html', StringHelper::enRussian($dom->saveHTML($query[0])));
                    // var_dump(StringHelper::enRussian($dom->saveHTML($query[0])));
        
                    foreach ($query as $tr) {
                        // echo '-----'.PHP_EOL;
                        // foreach ($tr->childNodes as $i => $node) {
                        //     var_dump(StringHelper::enRussian($node->nodeValue));
                        //     if ($i > 0) {
                        //         var_dump(StringHelper::enRussian($dom->saveHTML($node)));
                        //     }
                        // }
                        // echo '-----'.PHP_EOL;
                        $keyTag = '';
                        $valueTag = '';
                        if (isset($tr->childNodes[0]->childNodes[1]->childNodes[1]) &&
                            $tr->childNodes[0]->childNodes[1]->childNodes[1]->tagName == 'span') {
                            $keyTag = $tr->childNodes[0]->childNodes[1]->childNodes[1]->nodeValue;
                        } else if (isset($tr->childNodes[0]->childNodes[1]->childNodes[0])) {
                            $keyTag = $tr->childNodes[0]->childNodes[1]->childNodes[0]->nodeValue;
                        }
        
        
                        if (isset($tr->childNodes[2]->childNodes) && 
            $tr->childNodes[2]->childNodes->length > 3) {
            if ($tr->childNodes[2]->childNodes[1]->childNodes->length > 3) {

                foreach ($tr->childNodes[2]->childNodes as $i => $nodeC) {

                    if (isset($nodeC->childNodes) && $nodeC->childNodes->length > 3) {

                        foreach ($nodeC->childNodes as $nodeCC) {
                            if (isset($nodeCC->tagName) && $nodeCC->tagName == 'span' && $nodeCC->attributes[0]->value == 'glossary-term') {
                                
                                $valueTag .= ' / ' . trim($nodeCC->nodeValue);
                            }
                        }  
                    } else if (isset($nodeC->childNodes)) {
                        $valueTag .= ' / ' . trim($nodeC->childNodes[1]->nodeValue);
                    }
                }

                $valueTag = substr($valueTag, 3);
            } else {
                foreach ($tr->childNodes[2]->childNodes as $i => $nodeC) {
                    if (isset($nodeC->childNodes) && $nodeC->childNodes->length > 3) {
                        foreach ($nodeC->childNodes as $nodeCC) {
                            if (isset($nodeCC->tagName) && $nodeCC->tagName == 'span' && $nodeCC->attributes[0]->value == 'glossary-term') {
                                
                                $valueTag .= ' / ' . trim($nodeCC->nodeValue);
                            }
                        }  
                    } else if (isset($nodeC->tagName) && $nodeC->tagName == 'div') {
                        $valueTag .= ' / ' . trim($nodeC->nodeValue);
                    }
                }

                $valueTag = substr($valueTag, 3);
            }
        } else {
            if (isset($tr->childNodes[2]->childNodes[1]->childNodes) &&
                $tr->childNodes[2]->childNodes[1]->childNodes->length > 2) {
                $valueTag = $tr->childNodes[2]->childNodes[1]->childNodes[1]->nodeValue;
            } else if (isset($tr->childNodes[2]->childNodes[1])) {
                $valueTag = $tr->childNodes[2]->childNodes[1]->nodeValue;
            }
        }
        
                        list($key, $value) = [
                            StringHelper::enRussian($keyTag),
                            StringHelper::enRussian($valueTag)
                        ];
                        
                        if (! empty($key) && ! empty($value))
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
     * Get product data from main product page
     * 
     * @param string $link
     * 
     * @return string
     */
    private function getProductMainData(string $link): array
    {
        $mainData = [
            'description' => '',
            'seller'      => '',
            'topSale'     => false,
            'price'       => 0,
            'sku'         => ''
        ];
        
        $dom = new \DOMDocument();
        $mainProductInfo = $this->request->makeRequest($link);
        $dom->loadHtml($mainProductInfo);
        $xpath = new \DomXPath($dom);

        /**
         * Parse product description with html tags
         */
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

    /**
     * Parse categories breadcrums and insert into db
     * 
     * @param DomXPath $xpath
     * 
     * @return void
     */
    private function parseAndInsertCategoriesTree(\DomXPath $xpath): void
    {

        $queryCategories = $xpath->query("//ul[@name='breadcrumbs']/li[position()>1]");
                
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

        $this->logger->info("Categories tree parsend and insert successfully!");
    }

    /**
     * Insert data about product into DB with log messages
     * 
     * @param array $product
     * 
     * @return bool
     */
    private function insertProductData(array $product): bool
    {
        $category_id = DBQuery::select('categories', [['name', 'like', $product['category']]], ['id']);

        if (! (bool)count($category_id)) {
            $category_id = 1; // Undefined category
        } else {
            $category_id = $category_id[0]['id'];
        }

        $product_id = DBQuery::insert('items', [
            'sku'         => $product['sku'],
            'price'       => $product['price'],
            'name'        => $product['title'],
            'category_id' => $category_id,
            'description' => $product['description'],
            'attributes'  => json_encode($product['attributes']),
            'seller'      => $product['seller'],
            'isOnTop'     => $product['topSale']
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

        return true;
    }

}