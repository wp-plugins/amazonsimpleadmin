<?php
/**
 * ifeelweb.de WordPress Plugin Framework
 * For more information see http://www.ifeelweb.de/wp-plugin-framework
 * 
 * 
 *
 * @author    Timo Reith <timo@ifeelweb.de>
 * @version   $Id$
 * @package   
 */ 
class Asa_Service_Amazon_Error 
{
    /**
     * @var DOMNodeList
     */
    protected $_errors;

    /**
     * @var string
     */
    protected $_asin;

    /**
     * @var array
     */
    protected $_errorStore = array();



    /**
     * @param DOMNodeList $errors
     * @param $asin
     */
    public function __construct(DOMNodeList $errors, $asin)
    {
        $this->_errors = $errors;
        $this->_asin = $asin;

        $this->_init();
    }

    protected function _init()
    {
        foreach ($this->_errors as $error) {

            $errorTmp = array();
            $errorTmp['ASIN'] = $this->_asin;

            foreach ($error->childNodes as $errorParam) {
                $errorTmp[$errorParam->nodeName] = $errorParam->textContent;
            }
            array_push($this->_errorStore, $errorTmp);
        }
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errorStore;
    }

}
