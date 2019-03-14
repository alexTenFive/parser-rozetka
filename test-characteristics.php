<?php 
session_start();

use App\Helpers\StringHelper;
use App\Http\Request;

$request = new Request();

//unset($_SESSION['html']);

if (! isset($_SESSION['html'])) {
    $html = $request->makeRequest("https://rozetka.com.ua/tp_link_neffos_c5a_tp703a21ua/p32642919/characteristics/");
    var_dump($html);
    $_SESSION['html'] = $html;
}

$html = $_SESSION['html'];

$dom = new \DOMDocument();
$dom->loadHTML($html);
$xpath = new \DomXPath($dom);


$query = $xpath->query("//table[@class='chars-t']/tr[position()>1][not(@colspan)]"); 

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
                //     else if (isset($nodeC->tagName) && $nodeC->tagName == 'span' && $nodeC->attributes[0]->value == 'glossary-term') {
                                
                //         $valueTag .= ' / ' . trim($nodeC->nodeValue);
                // }
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

var_dump($product);