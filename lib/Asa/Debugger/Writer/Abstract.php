<?php
/**
 * Abstract.php
 * 
 * @author Timo Reith (mail@timoreith.de)
 * @copyright Copyright (c) Timo Reith (http://www.wp-amazon-plugin.com)
 * 
 * Created on 12.11.11.
 */
 
abstract class Asa_Debugger_Writer_Abstract
{
    public function __construct()
    {
    }

    /**
     * @abstract
     * @param $param
     * @return void
     */
    public abstract function write($param);

    /**
     * @abstract
     * @return string
     */
    public abstract function read();
    
    /**
     * @abstract
     * @return string
     */
    public abstract function clear();
}
