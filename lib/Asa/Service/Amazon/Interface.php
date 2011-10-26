<?php
/**
 * AmazonSimpleAdmin - Wordpress Plugin
 * 
 * @author Timo Reith
 * @copyright Copyright (c) 2007-2011 Timo Reith (http://www.wp-amazon-plugin.com)
 * 
 * 
 */

interface Asa_Service_Amazon_Interface 
{
    /**
     * API operation ItemLookup
     * @param string $asin
     * @param array $options
     */
    public function itemLookup($asin, array $options=array()); 

    /**
     * API operation ItemSearch
     * @param array $options
     */
    public function itemSearch(array $options);
}


?>