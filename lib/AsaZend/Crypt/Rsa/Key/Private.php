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
 * @version    $Id: Private.php 22662 2010-07-24 17:37:36Z mabe $
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
class AsaZend_Crypt_Rsa_Key_Private extends AsaZend_Crypt_Rsa_Key
{

    protected $_publicKey = null;

    public function __construct($pemString, $passPhrase = null)
    {
        $this->_pemString = $pemString;
        $this->_parse($passPhrase);
    }

    /**
     * @param string $passPhrase
     * @throws AsaZend_Crypt_Exception
     */
    protected function _parse($passPhrase)
    {
        $result = openssl_get_privatekey($this->_pemString, $passPhrase);
        if (!$result) {
            /**
             * @see AsaZend_Crypt_Exception
             */
            require_once 'AsaZend/Crypt/Exception.php';
            throw new AsaZend_Crypt_Exception('Unable to load private key');
        }
        $this->_opensslKeyResource = $result;
        $this->_details = openssl_pkey_get_details($this->_opensslKeyResource);
    }

    public function getPublicKey()
    {
        if ($this->_publicKey === null) {
            /**
             * @see AsaZend_Crypt_Rsa_Key_Public
             */
            require_once 'AsaZend/Crypt/Rsa/Key/Public.php';
            $this->_publicKey = new AsaZend_Crypt_Rsa_Key_Public($this->_details['key']);
        }
        return $this->_publicKey;
    }

}
