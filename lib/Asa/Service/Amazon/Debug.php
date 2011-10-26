<?php
/**
 * AmazonSimpleAdmin - Wordpress Plugin
 * 
 * @author Timo Reith
 * @copyright Copyright (c) 2007-2011 Timo Reith (http://www.wp-amazon-plugin.com)
 * 
 * 
 */

require_once 'Asa/Service/Amazon/Interface.php';
require_once 'Asa/Service/Amazon/Exception.php';

class Asa_Service_Amazon_Debug implements Asa_Service_Amazon_Interface
{
    /**
     * Asa_Service_Amazon object
     * @var Asa_Service_Amazon
     */
    protected $_asa;
    
    
    function __construct($asa) 
    {
        $this->_asa = $asa;
    }
    
    /**
     * (non-PHPdoc)
     * @see Asa_Service_Amazon_Interface::itemLookup()
     */
    public function itemLookup($asin, array $options=array()) 
    {
        $result = $this->_asa->itemLookup($asin, $options);
        
        if ($result != false) {
            $this->_debugResponse($this->_asa->getRequest()->getResponse());
        }
        
        return $result;
    }    

    /**
     * (non-PHPdoc)
     * @see Asa_Service_Amazon_Interface::itemSearch()
     */
    public function itemSearch(array $options)
    {
        $result = $this->_asa->itemSearch($options);
       
        if ($result != false) {
            $this->_debugResponse($this->_asa->getRequest()->getResponse());
        }
        
        return $result;
    }    
    
    
    protected function _debugResponse($response)
    {
        $path = getenv('ASA_DEBUG_PATH');
        
        if ( empty($path) || !is_dir($path) || !is_writable($path) ) {
            return false;
        }
        
        file_put_contents($path . date('YmdHis') . '.xml', substr(var_export($response, true), 1, -1));
    }
}


?>