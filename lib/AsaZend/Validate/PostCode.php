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
 * @version    $Id: PostCode.php 22668 2010-07-25 14:50:46Z thomas $
 */

/**
 * @see AsaZend_Validate_Abstract
 */
require_once 'AsaZend/Validate/Abstract.php';

/**
 * @see AsaZend_Locale_Format
 */
require_once 'AsaZend/Locale/Format.php';

/**
 * @category   Zend
 * @package    AsaZend_Validate
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class AsaZend_Validate_PostCode extends AsaZend_Validate_Abstract
{
    const INVALID  = 'postcodeInvalid';
    const NO_MATCH = 'postcodeNoMatch';

    /**
     * @var array
     */
    protected $_messageTemplates = array(
        self::INVALID  => "Invalid type given. String or integer expected",
        self::NO_MATCH => "'%value%' does not appear to be a postal code",
    );

    /**
     * Locale to use
     *
     * @var string
     */
    protected $_locale;

    /**
     * Manual postal code format
     *
     * @var unknown_type
     */
    protected $_format;

    /**
     * Constructor for the integer validator
     *
     * Accepts either a string locale, a AsaZend_Locale object, or an array or
     * AsaZend_Config object containing the keys "locale" and/or "format".
     *
     * @param string|AsaZend_Locale|array|AsaZend_Config $options
     * @throws AsaZend_Validate_Exception On empty format
     */
    public function __construct($options = null)
    {
        if ($options instanceof AsaZend_Config) {
            $options = $options->toArray();
        }

        if (empty($options)) {
            require_once 'AsaZend/Registry.php';
            if (AsaZend_Registry::isRegistered('AsaZend_Locale')) {
                $this->setLocale(AsaZend_Registry::get('AsaZend_Locale'));
            }
        } elseif (is_array($options)) {
            // Received
            if (array_key_exists('locale', $options)) {
                $this->setLocale($options['locale']);
            }

            if (array_key_exists('format', $options)) {
                $this->setFormat($options['format']);
            }
        } elseif ($options instanceof AsaZend_Locale || is_string($options)) {
            // Received Locale object or string locale
            $this->setLocale($options);
        }

        $format = $this->getFormat();
        if (empty($format)) {
            require_once 'AsaZend/Validate/Exception.php';
            throw new AsaZend_Validate_Exception("A postcode-format string has to be given for validation");
        }
    }

    /**
     * Returns the set locale
     *
     * @return string|AsaZend_Locale The set locale
     */
    public function getLocale()
    {
        return $this->_locale;
    }

    /**
     * Sets the locale to use
     *
     * @param string|AsaZend_Locale $locale
     * @throws AsaZend_Validate_Exception On unrecognised region
     * @throws AsaZend_Validate_Exception On not detected format
     * @return AsaZend_Validate_PostCode  Provides fluid interface
     */
    public function setLocale($locale = null)
    {
        require_once 'AsaZend/Locale.php';
        $this->_locale = AsaZend_Locale::findLocale($locale);
        $locale        = new AsaZend_Locale($this->_locale);
        $region        = $locale->getRegion();
        if (empty($region)) {
            require_once 'AsaZend/Validate/Exception.php';
            throw new AsaZend_Validate_Exception("Unable to detect a region for the locale '$locale'");
        }

        $format = AsaZend_Locale::getTranslation(
            $locale->getRegion(),
            'postaltoterritory',
            $this->_locale
        );

        if (empty($format)) {
            require_once 'AsaZend/Validate/Exception.php';
            throw new AsaZend_Validate_Exception("Unable to detect a postcode format for the region '{$locale->getRegion()}'");
        }

        $this->setFormat($format);
        return $this;
    }

    /**
     * Returns the set postal code format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->_format;
    }

    /**
     * Sets a self defined postal format as regex
     *
     * @param string $format
     * @throws AsaZend_Validate_Exception On empty format
     * @return AsaZend_Validate_PostCode  Provides fluid interface
     */
    public function setFormat($format)
    {
        if (empty($format) || !is_string($format)) {
            require_once 'AsaZend/Validate/Exception.php';
            throw new AsaZend_Validate_Exception("A postcode-format string has to be given for validation");
        }

        if ($format[0] !== '/') {
            $format = '/^' . $format;
        }

        if ($format[strlen($format) - 1] !== '/') {
            $format .= '$/';
        }

        $this->_format = $format;
        return $this;
    }

    /**
     * Defined by AsaZend_Validate_Interface
     *
     * Returns true if and only if $value is a valid postalcode
     *
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_setValue($value);
        if (!is_string($value) && !is_int($value)) {
            $this->_error(self::INVALID);
            return false;
        }

        $format = $this->getFormat();
        if (!preg_match($format, $value)) {
            $this->_error(self::NO_MATCH);
            return false;
        }

        return true;
    }
}
