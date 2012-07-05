<?php
/**
 * User: Alex Litvinok
 * Date: 6/19/12
 * Time: 4:09 AM
 */
class PHPLameConsole
{
    /**
     * @var array global options
     */
    private static $options;

    /**
     * @var string default name config-files
     */
    private static $config_name;

    /**
     * Construct class
     *
     * @param $config_name
     * @param $opt_short
     * @param $opt_long
     */
    function __construct( $config_name, $opt_short, $opt_long )
    {
        PHPLameCollector::enable();

        self::$config_name = $config_name;
        self::$options = PHP_VERSION_ID < 50300 ? getopt( $opt_short ) : getopt( $opt_short, explode(' ', $opt_long));
        $opt = &self::$options;
        $config_path = isset($opt['c']) ? $opt['c'] : ( isset($opt['config']) ? $opt['config'] : './'. $config_name );

        $this -> load_json_config( $opt, $config_path );
        $this -> execute();

        PHPLameCollector::disable();
    }

    /**
     * Destruct class
     */
    function __destruct()
    {
        PHPLameCollector::clean();
    }

    /**
     * Load and parse json-formatted config-file
     *
     * @param $storage
     * @param $path
     * @throws Exception
     */
    private function load_json_config( &$storage, $path )
    {
        if ( is_string($path) && file_exists($path) && is_file($path) )
        {
            $config_opt = json_decode( file_get_contents( $path ), true );
            if ( empty($config_opt) ) throw new Exception("Not valid JSON");
            else $storage = PHPLameUtils::array_merge_assoc( $storage, $config_opt );
        }
        elseif ( is_array($path) )
            foreach( $path as $val ) $this -> load_json_config( $storage, $val );
    }

    /**
     * Run tests
     */
    private function execute()
    {
        foreach( $this -> get_basedir() as $basedir )
        if ( is_dir($basedir) || PHPLameUtils::is_php( $basedir ) )
        {
            $opt = self::$options;
            $this -> load_json_config( $opt, $basedir. '/'. self::$config_name );

            $suite = new PHPLameSuite( $basedir, $opt );

            unset($suite); PHPLameCollector::clean();
        }
    }

    /**
     * Return list of test directories/files
     *
     * @return array
     */
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
