<?php
/**
 * File.php
 * 
 * @author Timo Reith (mail@timoreith.de)
 * @copyright Copyright (c) Timo Reith (http://www.wp-amazon-plugin.com)
 * 
 * Created on 12.11.11.
 */

require_once 'Asa/Debugger/Writer/Abstract.php';

class Asa_Debugger_Writer_File extends Asa_Debugger_Writer_Abstract
{
    /**
     * @var string
     */
    protected $_filename;


    /**
     * 
     */
    public function __construct($filename=null)
    {
        parent::__construct();

        // init the filename
        $this->setFilename($filename);
        $this->_initFile();
    }

    /**
     * @param $param
     * @return void
     */
    public function write($param, $clear=false)
    {
        if (empty($this->_filename)) {
            return false;
        }

        $flag = FILE_APPEND;
        if ($clear == true) {
            $flag = null;
        }

        return @file_put_contents($this->_filename, $param, $flag) !== false;
    }

    /**
     * @return void
     */
    public function read()
    {
        $contents = file_get_contents($this->_filename);

        $contents = substr($contents, strpos($contents, '?>')+2);
        return $contents;
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->write('', true);
        $this->_initFile();
    }

    /**
     * @return void
     */
    protected function _initFile()
    {
        if (empty($this->_filename) || !is_writable($this->_filename)) {
            require_once 'Asa/Debugger/Exception.php';
            throw new Asa_Debugger_Exception('Debug file could not be initialized');
        }
        
        if (!$contents = @file_get_contents($this->_filename)) {

            $contents = '<?php' . PHP_EOL;
            $contents .= 'if (!defined(ASA_DEBUG_READ)) exit("invalid access");' . PHP_EOL;
            $contents .= '?>' . PHP_EOL;

            file_put_contents($this->_filename, $contents);
        }
    }
    
	/**
     * @return the $_filename
     */
    public function getFilename ()
    {
        return $this->_filename;
    }

	/**
     * @param string $_filename
     */
    public function setFilename ($filename=null)
    {
        if ($filename == null) {
            $filename = 'asa_debug.php';
        }
        
        $filename = get_temp_dir() . $filename;
        
        if (!file_exists($filename) && file_put_contents($filename, '') === false) {
            require_once 'Asa/Debugger/Exception.php';
            throw new Asa_Debugger_Exception('Debug filename could not be set');
        }
        
        $this->_filename = $filename;
    }


}
