<?php
/**
 * User: Alex Litvinok
 * Date: 3/28/12
 * Time: 3:18 AM
 */
class PHPLameSuite
{
    /**
     * @var    File or directory for check
     */
    public $base;

    /**
     * @var    Options for execute tests
     */
    public $options = array();

    /**
     * @var    List checked files and class
     */
    public $resume = array();

    /**
     * Create suite for test and execute it
     *
     * @param string $base
     * @param array  $argv
     */
    function __construct( $base = '.', $argv = array() )
    {
        $this -> before();

        $this -> base = $base;
        $this -> options = $argv;

        if ( isset($argv['bootstrap']) && is_file($argv['bootstrap']) ) {
            require_once( $argv['bootstrap'] );
        }

        if ( is_dir( $this -> base )) {
            foreach ( $this -> scandir( $this -> base ) as $file ) $this -> build( $file );
        }

        elseif ( is_file( $this -> base )) {
            $this -> build( $this -> base );
        }

        $this -> after();
    }

    /**
     * Initialize class and get result testcase
     *
     * @param string $file
     */
    function build( $file )
    {
        foreach ( $this -> scanfile( $file ) as $class => $params )
        {
            $testcase = new $class( $this -> options );
            $this -> report( $class, $params, $testcase -> output );
            $this -> resume[] = "class $class :: $file";
        }
    }

    /**
     * Generage reports
     *
     * @param string $class
     * @param array  $params
     * @param array  $output
     */
    function report( $class, $params, $output )
    {
        if ( isset( $this -> options['junit']) && !empty($this -> options['junit']) )
        {
            $report = new PHPLame_JUnit( $class, $params, $output );
            $report -> document -> save( $this -> options['junit']."/TEST-$class.xml" );
        }

        if ( isset($this -> options['debug']) )
        {
            echo PHP_EOL; print_r($output);
        }
    }

    /**
     * Get all php scripts by directory.
     * Function get all files in directory O.o
     *
     * @param  string   $dir
     */
    function scandir( $dir )
    {
        $files = array();
        if ($handle = opendir($dir))
        {
            while ( false !== ($file = readdir($handle)) )
            {
                if ( $file!= "." && $file != ".." && ( $target = sprintf( "%s/%s", $dir, $file) ) )
                {
                    if ( is_dir($target) ) $files = array_merge( $files, $this -> scandir( $target ));
                    else $files[] = $target;
                }
            }
            closedir($handle);
        }
        return $files;
    }

    /**
     * Require_once file and find all class for testing
     * Used reflection class
     *
     * @param  string   $file
     */
    function scanfile( $file )
    {
        require_once( $file );

        $suites = array();
        $tokens = token_get_all( file_get_contents($file) );
        for ( $i = 2; $i < count($tokens); $i++ )
        {
            if ( $tokens[$i-2][0] == T_CLASS && $tokens[$i-1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING )
            {
                $class = $tokens[$i][1];
                $reflection = new ReflectionClass( $class );
                $comment = $reflection -> getDocComment();
                $params = array();

                if ( strlen($comment) !== FALSE )
                {
                    preg_match_all("/@(\w+)\s*(?::\s*(.*))?/x", $comment, $matchs );
                    foreach ( $matchs[1] as $key => $name) $params[ strtolower($name) ] = trim( $matchs[2][$key] );
                }

                if ( ( strpos( strtolower($class), 'test' ) !== FALSE || isset($params['suite']) )
                    && ( !isset($params['disabled']) || $params['disabled'] == 'false' ) )
                    $suites[$class] = $params;
            }
        }
        return $suites;
    }

    /**
     * Print to STDOUT name of software and version
     */
    function before()
    {
        printf( "PHPLame Benchmark | version: %s%s%s", PHPLAME_VERSION, PHP_EOL,PHP_EOL);
    }

    /**
     * Print to STDOUT checked files
     */
    function after()
    {
        global $resume;
        printf( "%s%s---%s%s%s%s", PHP_EOL,PHP_EOL,PHP_EOL,join( PHP_EOL, $this -> resume ),PHP_EOL,PHP_EOL);
    }
}
