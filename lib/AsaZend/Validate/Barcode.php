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
 * @package    AsaZend_Validate
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Barcode.php 22668 2010-07-25 14:50:46Z thomas $
 */

/**
 * @see AsaZend_Validate_Abstract
 */
require_once 'AsaZend/Validate/Abstract.php';

/**
 * @see AsaZend_Loader
 */
require_once 'AsaZend/Loader.php';

/**
 * @category   Zend
 * @package    AsaZend_Validate
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class AsaZend_Validate_Barcode extends AsaZend_Validate_Abstract
{
    const INVALID        = 'barcodeInvalid';
    const FAILED         = 'barcodeFailed';
    const INVALID_CHARS  = 'barcodeInvalidChars';
    const INVALID_LENGTH = 'barcodeInvalidLength';

    protected $_messageTemplates = array(
        self::FAILED         => "'%value%' failed checksum validation",
        self::INVALID_CHARS  => "'%value%' contains invalid characters",
        self::INVALID_LENGTH => "'%value%' should have a length of %length% characters",
        self::INVALID        => "Invalid type given. String expected",
    );

    /**
     * Additional variables available for validation failure messages
     *
     * @var array
     */
    protected $_messageVariables = array(
        'length' => '_length'
    );

    /**
     * Length for the set subtype
     *
     * @var integer
     */
    protected $_length;

    /**
     * Barcode adapter
     *
     * @var AsaZend_Validate_Barcode_BarcodeAdapter
     */
    protected $_adapter;

    /**
     * Generates the standard validator object
     *
     * @param  string|AsaZend_Config|
     *         AsaZend_Validate_Barcode_BarcodeAdapter $adapter Barcode adapter to use
     * @return void
     * @throws AsaZend_Validate_Exception
     */
    public function __construct($adapter)
    {
        if ($adapter instanceof AsaZend_Config) {
            $adapter = $adapter->toArray();
        }

        $options  = null;
        $checksum = null;
        if (is_array($adapter)) {
            if (array_key_exists('options', $adapter)) {
                $options = $adapter['options'];
            }

            if (array_key_exists('checksum', $adapter)) {
                $checksum = $adapter['checksum'];
            }

            if (array_key_exists('adapter', $adapter)) {
                $adapter = $adapter['adapter'];
            } else {
                require_once 'AsaZend/Validate/Exception.php';
                throw new AsaZend_Validate_Exception("Missing option 'adapter'");
            }
        }

        $this->setAdapter($adapter, $options);
        if ($checksum !== null) {
            $this->setChecksum($checksum);
        }
    }

    /**
     * Returns the set adapter
     *
     * @return AsaZend_Validate_Barcode_BarcodeAdapter
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }

    /**
     * Sets a new barcode adapter
     *
     * @param  string|AsaZend_Validate_Barcode $adapter Barcode adapter to use
     * @param  array  $options Options for this adapter
     * @return void
     * @throws AsaZend_Validate_Exception
     */
    public function setAdapter($adapter, $options = null)
    {
        $adapter = ucfirst(strtolower($adapter));
        require_once 'AsaZend/Loader.php';
        if (AsaZend_Loader::isReadable('AsaZend/Validate/Barcode/' . $adapter. '.php')) {
            $adapter = 'AsaZend_Validate_Barcode_' . $adapter;
        }

        if (!class_exists($adapter)) {
            AsaZend_Loader::loadClass($adapter);
        }

        $this->_adapter = new $adapter($options);
        if (!$this->_adapter instanceof AsaZend_Validate_Barcode_AdapterInterface) {
            require_once 'AsaZend/Validate/Exception.php';
            throw new AsaZend_Validate_Exception(
                "Adapter " . $adapter . " does not implement AsaZend_Validate_Barcode_AdapterInterface"
            );
        }

        return $this;
    }

    /**
     * Returns the checksum option
     *
     * @return boolean
     */
    public function getChecksum()
    {
        return $this->getAdapter()->getCheck();
    }

    /**
     * Sets the checksum option
     *
     * @param  boolean $checksum
     * @return AsaZend_Validate_Barcode
     */
    public function setChecksum($checksum)
    {
        $this->getAdapter()->setCheck($checksum);
        return $this;
    }

    /**
     * Defined by AsaZend_Validate_Interface
     *
     * Returns true if and only if $value contains a valid barcode
     *
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->_error(self::INVALID);
            return false;
        }

        $this->_setValue($value);
        $adapter       = $this->getAdapter();
        $this->_length = $adapter->getLength();
        $result        = $adapter->checkLength($value);
        if (!$result) {
            if (is_array($this->_length)) {
                $temp = $this->_length;
                $this->_length = "";
                foreach($temp as $length) {
                    $this->_length .= "/";
                    $this->_length .= $length;
                }

                $this->_length = substr($this->_length, 1);
            }

            $this->_error(self::INVALID_LENGTH);
            return false;
        }

        $result = $adapter->checkChars($value);
        if (!$result) {
            $this->_error(self::INVALID_CHARS);
            return false;
        }

        if ($this->getChecksum()) {
            $result = $adapter->checksum($value);
            if (!$result) {
                $this->_error(self::FAILED);
                return false;
            }
        }

        return true;
    }
}
