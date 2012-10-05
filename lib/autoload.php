<?php
/**
 * Author: Alex Litvinok <litvinok@gmail.com>
 */

define( '_LOADER_HOME_', dirname(__FILE__). DIRECTORY_SEPARATOR );

spl_autoload_register(function ($class)
{
    include_once( _LOADER_HOME_ . strtolower($class). '.class.php' );
});