<?php
/**
 * Author: Alex Litvinok
 * Date: 3/21/12
 * Time: 7:24 AM
 */

function phplame_autoload( $class = null )
{
    static $classes = null;
    static $path    = null;

    if ( $classes === null ) {
        $classes = array(
            'phplame' => '/framework.php',
            'phplamesuite' => '/suite.php',
            'phplameconsole' => '/console.php',
            'phplamecollector' => '/collector.php',
            'phplameutils' => '/utils.php',
            'phplame_junit' => '/Reports/junit.php',
            'phplame_json' => '/Reports/json.php'
        );
        $path = dirname(__FILE__);
    }

    if ( $class === null ) {
        $result = array(__FILE__);

        foreach ($classes as $file) {
            $result[] = $path . $file;
        }
        return $result;
    }

    $cn = strtolower($class);

    if ( isset($classes[$cn]) ) {
        $file = $path . $classes[$cn];
        require_once( $file );
    }
}

spl_autoload_register('phplame_autoload');