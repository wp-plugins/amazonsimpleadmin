<?php
/**
 *
 *
 * @author    Timo Reith <timo@ifeelweb.de>
 * @copyright Copyright (c) 2014 ifeelweb.de
 * @version   $Id$
 * @package
 */

class AsaLogger
{
    const LOG_TYPE_ERROR = 1;

    /**
     * @var AsaLogger
     */
    protected static $_instance;

    /**
     * @var wpdb
     */
    protected $_db;

    /**
     * @var bool
     */
    protected $_block = false;



    /**
     * @param wpdb $db
     * @return AsaLogger
     */
    public static function getInstance(wpdb $db)
    {
        if (self::$_instance === null) {
            self::$_instance = new self($db);
        }
        return self::$_instance;
    }

    /**
     * @param wpdb $db
     */
    protected function __construct(wpdb $db)
    {
        $this->_db = $db;
    }

    /**
     * @param $error
     */
    public function logError($error)
    {
        $location = site_url($_SERVER['REQUEST_URI']);
        if (strstr($location, 'admin-ajax.php') !== false) {
            $location = $_SERVER['HTTP_REFERER'];
        }



        if ($error instanceof Asa_Service_Amazon_Error) {

            $errors = $error->getErrors();

            foreach ($errors as $k => $error) {
                $error['Location'] = $location;

                $extra = sprintf("%s\n\nASIN: %s",
                    $error['Message'],
                    $error['ASIN']
                );

                $this->log($error['Code'], self::LOG_TYPE_ERROR, $extra, $location);
            }

            $this->_triggerNotification($error, $extra);

        } elseif (is_array($error)) {

            $error['Location'] = $location;
            $extra = $error['Message'];
            $this->log($error['Code'], self::LOG_TYPE_ERROR, $extra, $location);

            $this->_triggerNotification($error, $extra);
        }

    }

    protected function _triggerNotification($error, $content)
    {
        // mail feature
        if (get_option('_asa_error_email_notification')) {

            require_once 'AsaEmail.php';
            $email = AsaEmail::getInstance();
            $email->updatePsnBridgePost($error, $content);
        }
    }

    /**
     * @param $msg
     * @param $type
     * @param string $extra
     * @param string $location
     * @return bool
     */
    public function log($msg, $type, $extra = '', $location = '')
    {
        if ($this->isBlock()) {
            return null;
        }

        $sql = '
            INSERT INTO `'. $this->_getTableName() .'`
                (`message`, `location`, `type`, `timestamp`, `extra`)
            VALUES
                ("'. esc_sql($msg) .'", "'. esc_sql($location) .'", '. esc_sql($type) .', CURRENT_TIMESTAMP(), "'. esc_sql($extra) .'")
        ';

        $sql = '
            INSERT INTO `'. $this->_getTableName() .'`
                (`message`, `location`, `type`, `timestamp`, `extra`)
            VALUES
                ("%s", "%s", %d, CURRENT_TIMESTAMP(), "%s")
        ';

        $sql = $this->_db->prepare($sql, $msg, $location, $type, $extra);

        return ($this->_db->query($sql) === 1);
    }

    public function initTable()
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = '
            CREATE TABLE `'. $this->_getTableName() .'` (
              `id` int(11) NOT NULL auto_increment,
              `message` varchar(255) NOT NULL,
              `location` varchar(255) NOT NULL,
              `type` smallint(4) NOT NULL,
              `timestamp` datetime NOT NULL,
              `extra` text NULL,
              PRIMARY KEY  (`id`)
            )
        ';
        dbDelta($sql);
    }

    public function fetchAll()
    {
        $query = 'SELECT * FROM ' . $this->_getTableName();
        return $this->_db->get_results($query, ARRAY_A);
    }

    protected function _getTableName()
    {
        return $this->_db->prefix .'asa_log';
    }

    /**
     * Clear all log entries
     *
     * @return false|int
     */
    public function clear()
    {
        $sql = 'TRUNCATE TABLE `'. $this->_getTableName() .'`';
        return $this->_db->query($sql);
    }

    /**
     * @param boolean $block
     */
    public function setBlock($block)
    {
        if (is_bool($block)) {
            $this->_block = $block;
        }
    }

    /**
     * @return boolean
     */
    public function isBlock()
    {
        return $this->_block === true;
    }


}