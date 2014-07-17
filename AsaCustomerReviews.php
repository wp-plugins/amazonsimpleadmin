<?php
class AsaCustomerReviews
{
    const FIND_METHOD_DOM = 1;
    const FIND_METHOD_FALLBACK = 2;

    /**
     * the rating stars as complete html img tag
     * @var string
     */
    public $imgTag;
    
    /**
     * the rating stars image url
     * @var string
     */
    public $imgSrc;
    
    /**
     * total reviews
     * @var int
     */
    public $totalReviews;
    
    /**
     * average rating
     * @var int
     */
    public $averageRating;
    
    /**
     * the customer reviews iframe url provided by the amazon api
     * @var string
     */
    public $iframeUrl;

    /**
     * @var
     */
    protected $_asin;

    /**
     * @var
     */
    protected $_cache;

    /**
     * @var
     */
    protected $_response;

    /**
     * @var int
     */
    protected $_statusCode;

    /**
     * @var string
     */
    protected $_errorMessage;

    /**
     * @var int
     */
    protected $_findMethod = self::FIND_METHOD_DOM;



    /**
     * constructor
     * @param $asin
     * @param string $iframeUrl
     * @param $cache
     */
    public function __construct ($asin, $iframeUrl, $cache)
    {
        $this->_asin = $asin;
        $this->iframeUrl = $iframeUrl;
        $this->_cache = $cache;
    }

    public function load()
    {
        if ($this->_cache == null) {
            // if cache could not be initialized
            $this->_grabReview();

        } else {

            $reviewCacheId = 'CustomerReviews_'. $this->_asin;
            $reviewData    = $this->_cache->load($reviewCacheId);

            if (empty($reviewData)) {

                // data is not cached yet
                $this->_grabReview();
                $reviewData = array(
                    'imgTag'        => $this->imgTag,
                    'imgSrc'        => $this->imgSrc,
                    'totalReviews'  => $this->totalReviews,
                    'averageRating' => $this->averageRating
                );

                // put data in cache
                $this->_cache->save($reviewData, $reviewCacheId);

            } else {

                // load data from cache
                $this->imgTag        = $reviewData['imgTag'];
                $this->imgSrc        = $reviewData['imgSrc'];
                $this->totalReviews  = $reviewData['totalReviews'];
                $this->averageRating = $reviewData['averageRating'];
            }
        }

        if (strstr($this->averageRating, ',')) {
            $this->averageRating = str_replace(',', '.', $this->averageRating);
        }
        if (empty($this->averageRating)) {
            $this->averageRating = 0;
        }
    }
    
    /**
     * open the iframe and grab the relevant data
     */
    protected function _grabReview ()
    {
        $iframeContents = $this->_getIframeContents();
     
        if ($iframeContents != '') {
            $this->_getReviewsData($iframeContents);
        }
    }
    
    /**
     * get img tag and src from iframe html
     * @param string $contents
     */
    protected function _getReviewsData ($contents) 
    {
        $patternTag = '~<img[^>]+>~is';
        $patternSrc = '/(src)="([^"]*)"/i';
        $patternAlt = '/(alt)="([^"]*)"/i';
        $patternCnt = '~<a\b[^>]*>(.*?)</a>~is';
        
        if (preg_match($patternTag, $contents, $matchTag) == 1) {
            $this->imgTag = $matchTag[0];
            
            if (preg_match($patternSrc, $this->imgTag, $matchSrc) == 1) {
                $this->imgSrc = $matchSrc[2];                
            }
            if (preg_match($patternAlt, $this->imgTag, $matchAlt) == 1) {
                $alt = explode(' ', $matchAlt[2]);
                $this->averageRating = $alt[0];                
            }
        }      
        
        if (preg_match_all($patternCnt, $contents, $matchCnt)) {
            $count = explode(' ', $matchCnt[1][1]);
            $count = str_replace(',', '', $count);
            $count = str_replace('.', '', $count);
            $this->totalReviews = intval($count[0]);
        }
    }

    
    /**
     * read the iframe contents and grab the relevant code
     * @return string $contents
     */
    protected function _getIframeContents ()
    {
        $contents = '';

        if (!empty($this->iframeUrl)) {

            $this->_response = wp_remote_get($this->iframeUrl);

            if (!is_wp_error($this->_response)) {

                // success

                if (isset($this->_response['response']['code'])) {
                    $this->_statusCode = $this->_response['response']['code'];
                }

                $body = $this->_response['body'];


                global $asa;
                if ($asa->isDebug()) {
                    $asa->getDebugger()->write($body);
                }

                if ($this->getFindMethod() == self::FIND_METHOD_DOM) {

                    $body = preg_replace("/\r|\n/", "", $body);

                    $charset = get_bloginfo('charset');
                    if ($charset == 'UTF-8') {
                        $body = str_replace("\xC2\xA0", ' ', html_entity_decode($body, ENT_COMPAT, $charset));
                    } else {
                        $body = str_replace("\xA0", ' ', html_entity_decode($body, ENT_COMPAT, $charset));
                    }

                    $dom = new DomDocument();
                    $dom->loadHTML($body);

                    $finder = new DomXPath($dom);
                    $classname = 'crIFrameNumCustReviews';
                    $nodes = $finder->query("//*[contains(@class, '$classname')]");

                    foreach ($nodes as $node) {
                        $contents .= $dom->saveXML($node);
                    }

                } elseif ($this->getFindMethod() == self::FIND_METHOD_FALLBACK) {

                    $saveBuffer = false;

                    foreach(preg_split("/$\R?^/m", $body) as $line){

                        if (trim($line) == '<div class="crIFrameNumCustReviews">') {
                            $saveBuffer = true;
                        }
                        if ($saveBuffer === true && trim($line) == '</div>') {
                            $saveBuffer = false;
                        }

                        if ($saveBuffer === true) {
                            $contents .= $line;
                        }
                    }
                }

            } else {
                $this->_errorMessage = $this->_response->get_error_message();
            }
        }
        
        return $contents;
    }


    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->_statusCode == 200;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->_response['body'];
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @param int $findMethod
     */
    public function setFindMethod($findMethod)
    {
        if ($findMethod == self::FIND_METHOD_DOM || $findMethod == self::FIND_METHOD_FALLBACK) {
            $this->_findMethod = $findMethod;
        }
    }

    /**
     * @return int
     */
    public function getFindMethod()
    {
        return $this->_findMethod;
    }


}