<?php
/**
 * User: Alex Litvinok
 * Date: 6/19/12
 * Time: 4:09 AM
 */
class PHPLameConsole
{
    private static $options;

    private static $config_name;

    function __construct( $config_name, $opt_short, $opt_long )
    {
        self::$config_name = $config_name;
        self::$options = getopt( $opt_short, explode(' ', $opt_long));
        $opt = &self::$options;

        $config_path = isset($opt['c']) ? $opt['c'] : ( isset($opt['config']) ? $opt['config'] : './'. $config_name );

        $this -> load_json_config( $opt, $config_path );
        $this -> execute();
    }

    private function load_json_config( &$storage, $path )
    {
        if ( file_exists($path) && is_file($path) )
        {
            $config_opt = json_decode( file_get_contents( $path ), true );
            if ( empty($config_opt) ) throw new Exception("Not valid JSON");
            else $storage = array_merge( $storage, $config_opt );
        }
    }

    private function execute()
    {
        foreach( $this -> get_basedir() as $basedir )
        {
            $opt = self::$options;
            $cfg = $basedir. '/'. self::$config_name;
            $this -> load_json_config( $opt, $cfg );

            $suite = new PHPLameSuite( $basedir, $opt );

            unset($suite);
        }
    }

    private function get_basedir()
    {
        global $argv;
        $basedir = array();
        array_shift($argv); // shift path to phplame

        foreach( $argv as $arg ) if ( file_exists($arg) ) array_push( $basedir, $arg );

        if ( isset(self::$options['basedir']) && $ref = &self::$options['basedir'] )
        {
            if ( is_string($ref) && file_exists($ref)) array_push( $basedir, $ref );
            elseif ( is_array($ref) ) $basedir = array_merge($basedir, $ref);
        }

        return (array)$basedir;
    }
}
