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
 * @package    AsaZend_Crypt
 * @subpackage Rsa
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Public.php 20096 2010-01-06 02:05:09Z bkarwin $
 */

/**
 * @see AsaZend_Crypt_Rsa_Key
 */
require_once 'AsaZend/Crypt/Rsa/Key.php';

/**
 * @category   Zend
 * @package    AsaZend_Crypt
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class AsaZend_Crypt_Rsa_Key_Public extends AsaZend_Crypt_Rsa_Key
{

    protected $_certificateString = null;

    public function __construct($string)
    {
        $this->_parse($string);
    }

    /**
     * @param string $string
     * @throws AsaZend_Crypt_Exception
     */
    protected function _parse($string)
    {
        if (preg_match("/^-----BEGIN CERTIFICATE-----/", $string)) {
            $this->_certificateString = $string;
        } else {
            $this->_pemString = $string;
        }
        $result = openssl_get_publickey($string);
        if (!$result) {
            /**
             * @see AsaZend_Crypt_Exception
             */
            require_once 'AsaZend/Crypt/Exception.php';
            throw new AsaZend_Crypt_Exception('Unable to load public key');
        }
        //openssl_pkey_export($result, $public);
        //$this->_pemString = $public;
        $this->_opensslKeyResource = $result;
        $this->_details = openssl_pkey_get_details($this->_opensslKeyResource);
    }

    public function getCertificate()
    {
        return $this->_certificateString;
    }

}
