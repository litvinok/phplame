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
        $this -> base = $base;
        $this -> options = $argv;

        putenv("PHPLAME_PRINT_STATUS=0"); // storage of env for threads

        $GLOBALS['SILENT_MODE'] = isset($argv['silent']) ? true : false;
        $GLOBALS['VERBOSE_MODE'] = isset($argv['verbose']) ? true : false;
        $GLOBALS['AVERAGE_MODE'] = isset($argv['average']) ? true : false;
        $GLOBALS['NOCOLOR_MODE'] = isset($argv['nocolor']) ? true : false;

        $GLOBALS['TIME_SPEC_USER'] = isset($argv['time']) && preg_match('/(real|user|sys)/i', $argv['time']) ?
            strtolower(trim($argv['time'])) : 'real';

        $this -> check();
        $this -> before();

        if ( isset($argv['bootstrap']) && is_file($argv['bootstrap']) ) {
            require_once( $argv['bootstrap'] );
        }

        if ( is_dir( $this -> base )) {
            foreach ( $this -> scandir( $this -> base ) as $file ) $this -> build( $file );
        }

        elseif ( is_file( $this -> base ) && PHPLameUtils::is_php( $this -> base) ) {
            $this -> build( $this -> base );
        }

        $this -> after();
    }

    /**
     * Destruct class
     */
    function __destruct()
    {
        unset($this -> base);
        unset($this -> resume );
        unset($this -> options );
        PHPLameCollector::clean();
    }

    /**
     * Initialize class and get result testcase
     *
     * @param string $file
     */
    private function build( $file )
    {
        foreach ( $this -> scanfile( $file ) as $class => $params )
        {
            try
            {
                $testcase = new $class( $this -> options, $params );
                $this -> report( $class, $params, $testcase -> output );
                $this -> resume[] = "class $class :: $file";

                unset($testcase);  PHPLameCollector::clean();
            }
            catch( Exception $e )
            {
                echo $e -> getMessage();
            }
        }
    }

    /**
     * Generage reports
     *
     * @param string $class
     * @param array  $params
     * @param array  $output
     */
    private function report( $class, $params, $output )
    {
        if ( empty($output)) return false;

        if ( isset( $this -> options['junit']) && !empty($this -> options['junit']) )
        {
            try {
                $report = new PHPLame_JUnit( $class, $params, $output );
                $report -> document -> save( $this -> options['junit']."/TEST-$class.xml" );
            } catch ( Exception $e ) { printf($e); }
        }

        if ( isset( $this -> options['json']) && !empty($this -> options['json']) )
        {
            try {
                $report = new PHPLame_JSON( $class, $params, $output );
                $report -> save( $this -> options['json']."/$class.json" );
            } catch ( Exception $e ) { printf($e); }
        }

        $send_report_url = null;

        foreach( array('send', 's') as $s )
            if ( isset( $this -> options[$s]) && !empty($this -> options[$s]) )
                $send_report_url = $this -> options[$s];

        if ( $send_report_url !== null )
        {
            try {
                $report = new PHPLame_Sender( $class, $params, $output );
                $report -> send( $send_report_url );
            } catch ( Exception $e ) { printf($e); }
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
    private function scandir( $dir )
    {
        $files = array();
        if ($handle = opendir($dir))
        {
            while ( false !== ($file = readdir($handle)) )
            {
                if ( $file!= "." && $file != ".." && ( $target = sprintf( "%s/%s", $dir, $file) ) )
                {
                    if ( is_dir($target) ) $files = array_merge( $files, $this -> scandir( $target ));
                    elseif ( PHPLameUtils::is_php( $file ) ) $files[] = $target;
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
    private function scanfile( $file )
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
     * Check options for run
     */
    private function check()
    {
        if ( isset($this -> options['junit']) && !file_exists( $this -> options['junit'] ) )
        {
            if ( mkdir( $this -> options['junit'] ) !== true )
            {
                $this -> error( "Can't find directory for JUnit reports" );
            }
        }
    }

    /**
     * Print to STDOUT name of software and version
     */
    private function before()
    {
        if ( $GLOBALS['SILENT_MODE'] !== true )
        {
            if ( $GLOBALS['NOCOLOR_MODE'] !== true )
                printf( "\033[1mPHPLame Benchmark | version: %s\n\033[0m", PHPLAME_VERSION );
            else printf( "PHPLame Benchmark | version: %s\n", PHPLAME_VERSION );

            printf("Scanning %s\n\n", $this -> base);
        }
    }

    /**
     * Print to STDOUT checked files
     */
    private function after()
    {
        if ( $GLOBALS['SILENT_MODE'] !== true )
        {
            if ( $GLOBALS['NOCOLOR_MODE'] !== true )
                printf( "\n\n---\nChecked %d files:\n%s\n\n\033[0m", count($this -> resume), join( PHP_EOL, $this -> resume ) );
            else printf( "\n\n---\nChecked %d files:\n%s\n\n", count($this -> resume), join( PHP_EOL, $this -> resume ) );
        }
    }

    /**
     * Throw error message
     *
     * @param $message
     */
    private function error( $message )
    {
        die( "PHPLame: $message ".PHP_EOL );
    }

}
