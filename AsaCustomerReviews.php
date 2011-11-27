<?php
class AsaCustomerReviews {
	
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
	 * constructor
	 * @param string $iframeUrl
	 */
	public function __construct ($asin, $iframeUrl, $cache)	
	{
	    $this->iframeUrl = $iframeUrl;

	    if ($cache == null) {
            // if cache could not be initialized
            $this->_grabReview();                
        } else {

        	$reviewCacheId = 'CustomerReviews_'. $asin;        	
        	$reviewData    = $cache->load($reviewCacheId);
        
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
	            $cache->save($reviewData, $reviewCacheId);
	        
	        } else {
	            
	        	// load data from cache
	        	$this->imgTag        = $reviewData['imgTag'];
	        	$this->imgSrc        = $reviewData['imgSrc'];
	        	$this->totalReviews  = $reviewData['totalReviews'];
	        	$this->averageRating = $reviewData['averageRating'];
	        }
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
		
		$allow_url_fopen = ini_get('allow_url_fopen');
		
		if (function_exists('curl_init')) {
			
			// via curl
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->iframeUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $html = curl_exec($ch);
            curl_close($ch);
            $html = explode(PHP_EOL, $html);
            
            $saveBuffer = false;
            foreach($html as $line) {
	            if (trim($line) == '<div class="crIFrameNumCustReviews">') {
	                $saveBuffer = true;
	            }
	            if ($saveBuffer == true && trim($line) == '</div>') {
	                $saveBuffer = false;
	            }
	       
	            if ($saveBuffer == true) {
	                $contents .= $line;
	            }   
            }
            
        } else if (!empty($allow_url_fopen)) {
			
			// via fopen
			$handle = fopen($this->iframeUrl, 'r');
			
			if (!empty($handle)) {
			    			            
    			$saveBuffer = false;
    			while(!feof($handle)) { 
    			
    			       $buffer = trim(fgets($handle, 4096));
    			       if ($buffer == '<div class="crIFrameNumCustReviews">') {
    			           $saveBuffer = true;
    			       } else if ($saveBuffer == true && $buffer == '</div>') {
    			           $saveBuffer = false;
    			       }
    			       
    			       if ($saveBuffer == true) {
    			          $contents .= $buffer;
    			       }   
    			}
    			fclose($handle);
			}
			
		}
		
		return $contents;
	}

}
?>