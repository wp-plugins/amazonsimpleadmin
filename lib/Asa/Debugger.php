<?php
/**
 * Debugger.php
 * 
 * @author Timo Reith (mail@timoreith.de)
 * @copyright Copyright (c) Timo Reith (http://www.wp-amazon-plugin.com)
 * 
 * Created on 12.11.11.
 */
 
class Asa_Debugger
{
    /**
     * @var Asa_Debugger_Writer_Abstract
     */
    private $_writer;

    /**
     * @var
     */
    private static $_instance;


    /**
     * @param null $writer
     */
    public function __construct($writer=null)
    {
        if ($writer != null) {
            $this->setWriter($writer);
        }
    }

    /**
     * @static
     * @return Debugger
     */
    public static function factory()
    {
        if (self::$_instance == null) {
            require_once 'Asa/Debugger/Writer/File.php';
            self::$_instance = new self(new Asa_Debugger_Writer_File());
        }
        
        return self::$_instance;
    }

    /**
     * @param Asa_Debugger_Writer_Abstract $writer
     * @return void
     */
    public function setWriter(Asa_Debugger_Writer_Abstract $writer)
    {
        if ($writer instanceof Asa_Debugger_Writer_Abstract) {
            $this->_writer = $writer;
        }
    }

    /**
     * @param $param
     * @return void
     */
    public function write($param)
    {
        if (!$this->_writer instanceof Asa_Debugger_Writer_Abstract) {
            return false;
        }

        $bt   = debug_backtrace();
        $info = pathinfo($bt[0]['file']);

        $format = '%s, %s (%s):' . PHP_EOL;
        $contents = sprintf($format, date('Y/m/d H:i:s'), $info['basename'], $bt[0]['line']);

        $contents .= $param;

        $contents .=
            PHP_EOL . PHP_EOL .
            '------------------------------------------------------------------------------------------------------'.
            PHP_EOL . PHP_EOL;

        return $this->_writer->write($contents);
    }

    /**
     * @return bool|string
     */
    public function read()
    {
        if ($this->_writer instanceof Asa_Debugger_Writer_Abstract) {
            return $this->_writer->read();
        }
        return false;
    }
    
    /**
     * @return bool|string
     */
    public function clear()
    {
        if ($this->_writer instanceof Asa_Debugger_Writer_Abstract) {
            return $this->_writer->clear();
        }
        return false;
    }
}
