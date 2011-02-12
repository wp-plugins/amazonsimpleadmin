<?php

class Zend_Service_Amazon_Subtitles
{
	protected $_itemXml;
	
	protected $_result = array();
	
	protected $_resultFormat = '%s';

	
	
    /**
     * 
     */
    public function __construct(SimpleXMLElement $itemXml)
    {
        $this->_itemXml = $itemXml;
    }
    
    /**
     * Get the result
     */    
    public function getResult ()
    {
    	$result = $this->_itemXml->xpath('//Languages');
    	
    	foreach ($result[0] as $lang) {
    		if ($lang->Type == 'Subtitled') {
    			$this->_result[] = sprintf($this->_resultFormat, $lang->Name);
    		}
    	}
    	
    	return implode(', ', $this->_result);    	
    }
}
