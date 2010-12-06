<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Amazon
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Item.php 21883 2010-04-16 14:57:07Z dragonbe $
 */


/**
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Amazon
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Service_Amazon_Item
{
    /**
     * @var string
     */
    public $ASIN;

    /**
     * @var string
     */
    public $DetailPageURL;

    /**
     * @var int
     */
    public $SalesRank;

    /**
     * @var int
     */
    public $TotalReviews;

    /**
     * @var int
     */
    public $AverageRating;

    /**
     * @var string
     */
    public $SmallImage;

    /**
     * @var string
     */
    public $MediumImage;

    /**
     * @var string
     */
    public $LargeImage;

    /**
     * @var string
     */
    public $Subjects;

    /**
     * @var Zend_Service_Amazon_OfferSet
     */
    public $Offers;

    /**
     * @var Zend_Service_Amazon_CustomerReview[]
     */
    public $CustomerReviews = array();
    
    /**
     * @var Zend_Service_Amazon_EditorialReview[]
     */
    public $EditorialReviews = array();

    /**
     * @var Zend_Service_Amazon_SimilarProducts[]
     */
    public $SimilarProducts = array();

    /**
     * @var Zend_Service_Amazon_Accessories[]
     */
    public $Accessories = array();

    /**
     * @var array
     */
    public $Tracks = array();

    /**
     * @var Zend_Service_Amazon_ListmaniaLists[]
     */
    public $ListmaniaLists = array();
    
    protected $_dom;
    protected $_xpath;
    protected $_xml;


    /**
     * Parse the given <Item> element
     *
     * @param  null|DOMElement $dom
     * @return void
     * @throws	Zend_Service_Amazon_Exception
     * 
     * @group ZF-9547
     */
    public function __construct($dom, $xml_response)
    {
    	if (null === $dom) {
    		require_once 'Zend/Service/Amazon/Exception.php';
    		throw new Zend_Service_Amazon_Exception('Item element is empty');
    	}
    	if (!$dom instanceof DOMElement) {
    		require_once 'Zend/Service/Amazon/Exception.php';
    		throw new Zend_Service_Amazon_Exception('Item is not a valid DOM element');
    	}
    	
    	$this->_xml = str_replace('xmlns=', 'ns=', $xml_response);
    	
        $xpath = new DOMXPath($dom->ownerDocument);
        $xpath->registerNamespace('az', 'http://webservices.amazon.com/AWSECommerceService/2010-10-01');
        $this->_xpath = $xpath;
        $this->_dom   = $dom;
        
        $this->ASIN = $xpath->query('./az:ASIN/text()', $dom)->item(0)->data;

        $result = $xpath->query('./az:DetailPageURL/text()', $dom);
        if ($result->length == 1) {
            $this->DetailPageURL = $result->item(0)->data;
        }

        if ($xpath->query('./az:ItemAttributes/az:ListPrice', $dom)->length >= 1) {
            $this->CurrencyCode = (string) $xpath->query('./az:ItemAttributes/az:ListPrice/az:CurrencyCode/text()', $dom)->item(0)->data;
            $this->Amount = (int) $xpath->query('./az:ItemAttributes/az:ListPrice/az:Amount/text()', $dom)->item(0)->data;
            $this->FormattedPrice = (string) $xpath->query('./az:ItemAttributes/az:ListPrice/az:FormattedPrice/text()', $dom)->item(0)->data;
        }

        $result = $xpath->query('./az:ItemAttributes/az:*/text()', $dom);
        
        if ($result->length >= 1) {
            foreach ($result as $v) {
            	
                if (isset($this->{$v->parentNode->tagName})) {
                    if (is_array($this->{$v->parentNode->tagName})) {
                        array_push($this->{$v->parentNode->tagName}, (string) $v->data);
                    } else {
                        $this->{$v->parentNode->tagName} = array($this->{$v->parentNode->tagName}, (string) $v->data);
                    }
                } else {
                    $this->{$v->parentNode->tagName} = (string) $v->data;
                }
            }
        }

        foreach (array('SmallImage', 'MediumImage', 'LargeImage') as $im) {
            $result = $xpath->query("./az:ImageSets/az:ImageSet[position() = 1]/az:$im", $dom);
            if ($result->length == 1) {
                /**
                 * @see Zend_Service_Amazon_Image
                 */
                require_once 'Zend/Service/Amazon/Image.php';
                $this->$im = new Zend_Service_Amazon_Image($result->item(0));
            }
        }

        $result = $xpath->query('./az:SalesRank/text()', $dom);
        if ($result->length == 1) {
            $this->SalesRank = (int) $result->item(0)->data;
        }

//        $result = $xpath->query('./az:CustomerReviews/az:IFrameURL', $dom);
                        
//        if ($result->length >= 1) { 	
//        	
//            /**
//             * @see Zend_Service_Amazon_CustomerReview
//             */
//            require_once 'Zend/Service/Amazon/CustomerReview.php';
//            foreach ($result as $review) {
//                $this->CustomerReviews[] = new Zend_Service_Amazon_CustomerReview($review);
//            }
//            $this->AverageRating = (float) $xpath->query('./az:CustomerReviews/az:AverageRating/text()', $dom)->item(0)->data;
//            $this->TotalReviews = (int) $xpath->query('./az:CustomerReviews/az:TotalReviews/text()', $dom)->item(0)->data;
//        }

        // custommization
        $result = $xpath->query('./az:CustomerReviews/az:IFrameURL/text()', $dom);
        if ($result->length == 1) {
            $this->CustomerReviewsIFrameURL = $result->item(0)->data;            
        }
        
        $result = $xpath->query('./az:EditorialReviews/az:*', $dom);
        
        if ($result->length >= 1) {
            /**
             * @see Zend_Service_Amazon_EditorialReview
             */
        	
            require_once 'Zend/Service/Amazon/EditorialReview.php';
            foreach ($result as $r) {
                $this->EditorialReviews[] = new Zend_Service_Amazon_EditorialReview($r);                
            }            
        }

        $result = $xpath->query('./az:SimilarProducts/az:*', $dom);
        if ($result->length >= 1) {
            /**
             * @see Zend_Service_Amazon_SimilarProduct
             */
            require_once 'Zend/Service/Amazon/SimilarProduct.php';
            foreach ($result as $r) {
                $this->SimilarProducts[] = new Zend_Service_Amazon_SimilarProduct($r);
            }
        }

        $result = $xpath->query('./az:ListmaniaLists/*', $dom);
        if ($result->length >= 1) {
            /**
             * @see Zend_Service_Amazon_ListmaniaList
             */
            require_once 'Zend/Service/Amazon/ListmaniaList.php';
            foreach ($result as $r) {
                $this->ListmaniaLists[] = new Zend_Service_Amazon_ListmaniaList($r);
            }
        }

        $result = $xpath->query('./az:Tracks/az:Disc', $dom);
        if ($result->length > 1) {
            foreach ($result as $disk) {
                foreach ($xpath->query('./*/text()', $disk) as $t) {
                    // TODO: For consistency in a bugfix all tracks are appended to one single array
                    // Erroreous line: $this->Tracks[$disk->getAttribute('number')] = (string) $t->data;
                    $this->Tracks[] = (string) $t->data;
                }
            }
        } else if ($result->length == 1) {
            foreach ($xpath->query('./*/text()', $result->item(0)) as $t) {
                $this->Tracks[] = (string) $t->data;
            }
        }

        $result = $xpath->query('./az:Offers', $dom);
        $resultSummary = $xpath->query('./az:OfferSummary', $dom);
        if ($result->length > 1 || $resultSummary->length == 1) {
            /**
             * @see Zend_Service_Amazon_OfferSet
             */
            require_once 'Zend/Service/Amazon/OfferSet.php';
            $this->Offers = new Zend_Service_Amazon_OfferSet($dom);            
        }

        $result = $xpath->query('./az:Accessories/*', $dom);
        if ($result->length > 1) {
            /**
             * @see Zend_Service_Amazon_Accessories
             */
            require_once 'Zend/Service/Amazon/Accessories.php';
            foreach ($result as $r) {
                $this->Accessories[] = new Zend_Service_Amazon_Accessories($r);
            }
        }    
        
        
    }
    
    /**
     * 
     * Enter description here ...
     * @param $name
     */
    public function __get ($name)
    {
    	if (in_array($name, array(
            'ASIN', 'SmallImageUrl', 'SmallImageWidth', 'SmallImageHeight',
            'MediumImageUrl', 'MediumImageWidth', 'MediumImageHeight',
            'LargeImageUrl', 'LargeImageWidth', 'LargeImageHeight', 'Label',
            'Manufacturer', 'Publisher', 'Studio', 'Title', 'AmazonUrl',
            'TotalOffers', 'LowestOfferPrice', 'LowestOfferCurrency',
            'LowestOfferFormattedPrice', 'AmazonPrice', 'AmazonCurrency',
            'AmazonAvailability', 'AmazonLogoSmallUrl', 'AmazonLogoLargeUrl',
            'DetailPageURL', 'Platform', 'ISBN', 'EAN', 'NumberOfPages',
            'ReleaseDate', 'Binding', 'Author', 'Creator', 'Edition',
            'AverageRating', 'TotalReviews', 'RatingStars', 'RatingStarsSrc',
            'Director', 'Actors', 'Actor', 'RunningTime', 'Format', 'Studio',
            'CustomRating', 'ProductDescription', 'AmazonDescription', 
            'EditorialReviews', 'Artist'
        ))) {
	    	if (isset($this->$name)) {
	    		return $this->$name;
	    	}
        } else {
        
	        $itemXml = new SimpleXMLElement($this->_xml);
	        $result = $this->_searchValue($itemXml, $name);
        
	        if (!empty($result)) {
	            return $result;
	        }
	        
	        return '';
        }

    }
    
    
	protected function _searchValue ($itemXml, $s) 
	{
	    $result = $itemXml->xpath('//'.$s);
	    
	    if (count($result) == 1) {
	        if (count($result[0]) > 0) {
	            return $this->_showResultArray($result[0]);
	        }
	        return (string)$result[0];
	    } else if (count($result) > 1) {
	        return $this->_showResultArray($result);
	    }   
	}
    
	protected function _showResultArray ($a) 
	{	    
	    $show = '';
	    foreach ($a as $k => $v) {
	        if (is_string($k)) {
	            $show .= $k .': ';
	        }
	        $show .= $v . ', ';
	    }
	    return substr(trim($show), 0, -1);
	}    


    /**
     * Returns the item's original XML
     *
     * @return string
     */
    public function asXml()
    {
        return $this->_dom->ownerDocument->saveXML($this->_dom);
    }
}
