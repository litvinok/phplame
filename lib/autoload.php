<?php
/**
 * Author: Alex Litvinok
 * Date: 3/21/12
 * Time: 7:24 AM
 */

function phplame_autoload( $class = NULL )
{
    static $classes = NULL;
    static $path    = NULL;

    if ($classes === NULL) {
        $classes = array(
            'phplame' => '/framework.php',
            'phplamesuite' => '/suite.php',
            'phplame_junit' => '/Reports/junit.php',
        );
        $path = dirname(__FILE__);
    }

    if ($class === NULL) {
        $result = array(__FILE__);

        foreach ($classes as $file) {
            $result[] = $path . $file;
        }
        return $result;
    }

    $cn = strtolower($class);

    if (isset($classes[$cn])) {
        $file = $path . $classes[$cn];
        require $file;
    }
}

spl_autoload_register('phplame_autoload');