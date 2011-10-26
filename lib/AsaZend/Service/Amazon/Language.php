<?php

class AsaZend_Service_Amazon_Language
{
	protected $_itemXml;
	
	protected $_result = array();
	
	protected $_resultFormat = '%s (%s)';

	
	
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
    		if ($lang->Type == 'Original Language') {
    			$this->_result[] = sprintf($this->_resultFormat, $lang->Name, $lang->AudioFormat);
    		}
    	}
    	
    	return implode(', ', $this->_result);
    }
}
