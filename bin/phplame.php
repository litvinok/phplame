<?php
/**
 * Author: Alex Litvinok
 * Date: 3/21/12
 * Time: 7:24 AM
 */

define( 'PHPLAME_HOME', dirname(__FILE__) );
define( 'PHPLAME_VERSION', 'dev' );

require_once PHPLAME_HOME.'/../lib/autoload.php';

new PHPLameSuite( array_pop($argv), getopt('', array(
    'junit::',
    'tags::',
    'bootstrap::',
    'debug:',
)));

exit();