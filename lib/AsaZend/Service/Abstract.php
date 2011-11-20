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
 * @package    AsaZend_Service
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Abstract.php 20096 2010-01-06 02:05:09Z bkarwin $
 */


/**
 * AsaZend_Http_Client
 */
require_once 'AsaZend/Http/Client.php';


/**
 * @category   Zend
 * @package    AsaZend_Service
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class AsaZend_Service_Abstract
{
    /**
     * HTTP Client used to query all web services
     *
     * @var AsaZend_Http_Client
     */
    protected static $_httpClient = null;


    /**
     * Sets the HTTP client object to use for retrieving the feeds.  If none
     * is set, the default AsaZend_Http_Client will be used.
     *
     * @param AsaZend_Http_Client $httpClient
     */
    final public static function setHttpClient(AsaZend_Http_Client $httpClient)
    {
        self::$_httpClient = $httpClient;
    }


    /**
     * Gets the HTTP client object.
     *
     * @return AsaZend_Http_Client
     */
    final public static function getHttpClient()
    {
        if (!self::$_httpClient instanceof AsaZend_Http_Client) {
            self::$_httpClient = new AsaZend_Http_Client();
        }

        return self::$_httpClient;
    }
}

