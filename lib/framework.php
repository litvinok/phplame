<?php
/**
 * User: Alex Litvinok
 * Date: 3/15/12
 * Time: 5:51 AM
 */

class PHPLame
{
    /**
     * @var    Result testcases
     */
    public $output;

    /**
     * @var    Color output schema
     */
    private $color;

    function __construct( $options = array() )
    {
        $class = new ReflectionClass( $this );
        $cases = array();
        $output = array();

        if ( $GLOBALS['NOCOLOR_MODE'] !== true )
        {
            $this -> color = array(
                'name' => "\033[36m%s\033[0m",
                'pass' => "\033[32m%s\033[0m",
                'fail' => "\033[41m%s\033[0m",
            );
        }
        else $this -> color = array( 'name' => "%s", 'pass' => "%s", 'fail' => "%s" );

        foreach ( $class -> getMethods() as $method ) // for case
        {
            $comment = $method -> getDocComment();
            $params = array(
                'repeat' => 1,
                'thread' => 1,
                'usleep' => 0,
                'before' => false,
                'after' => false,
                'beforeThread' => false,
                'afterThread' => false,
                'beforeCase' => false,
                'afterCase' => false,
            );

            if ( strlen($comment) !== FALSE )
            {
                preg_match_all("/@(\w+)\s*(?::\s*(.*))?/x", $comment, $matchs );
                foreach( $matchs[1] as $key => $name) $params[ strtolower($name) ] = trim( $matchs[2][$key] );
            }

            if ( strpos( $method -> name, 'test') || isset($params['test']) ) // if case
            {
                $accept = true;

                if ( isset($params['sleep']) && !isset($params['usleep']) )
                {
                    $params['usleep'] = (int)$params['sleep'] * 1000000;
                }

                if ( isset($options['tags']) ) $accept = false;
                if ( isset($params['tags']) && isset($options['tags']) )
                {
                    $tags = array_flip( spliti(',', $params['tags']) );
                    foreach( spliti(',', $options['tags']) as $tag )
                    {
                        if ( strpos( $tag, '!' ) === 0 && !isset($tags[$tag]) ) $accept = true;
                        elseif ( strpos( $tag, '!' ) !== 0 && isset($tags[$tag]) ) $accept = true;
                    }
                }

                if ( $accept )
                {
                    $casename = isset($params['test']) && strlen($params['test']) ? $params['test'] : $method -> name;
                    $cases[ $method -> name ]['name'] = $casename;
                    $cases[ $method -> name ]['method'] = $method;
                    $cases[ $method -> name ]['params'] = $params;
                }
            }
        }

        $this -> beforeClass(); // hook before class
        foreach ( $cases as $ref ) $this -> cases($ref['name'], $ref['method'], $ref['params']);
        $this -> afterClass(); // hook after class
    }

    /**
     * Print simple resultat
     *
     * @param  boolean $pass
     */
    private function pretty( $pass = true )
    {
        if ( $GLOBALS['VERBOSE_MODE'] == true && $GLOBALS['SILENT_MODE'] !== true )
        {
            $count = getenv('PHPLAME_PRINT_STATUS');

            if ( $pass ) printf( $this -> color['pass'], "." );
            else printf( $this -> color['fail'], "F" );

            if ( $count++ >= 50 ) { $count = 0; echo PHP_EOL; }
            putenv("PHPLAME_PRINT_STATUS=$count");
        }
    }

    /**
     * Run case test
     *
     * @param  string  $name
     * @param  object  $method
     * @param  array   $params
     */
    private function cases( &$name, &$method, &$params )
    {
        $tmp = tmpfile();
        $meta = stream_get_meta_data( $tmp );

        if ( $GLOBALS['SILENT_MODE'] !== true )
        {
            printf( $this -> color['name'], "* $name" );
            if ( $GLOBALS['VERBOSE_MODE'] !== true ) echo ' .. ';
            else echo ':'. PHP_EOL;
        }

        $this -> beforeCase(); // hook before case
        if ( $params['beforeCase'] != false ) call_user_func_array( array($this, $params['beforeCase']), array());

        if ( (int)$params['thread'] <= 1 )
        {
            $this -> thread( $method, $tmp, (int)$params['repeat'], (int)$params['usleep'], $params );
        }
        else
        {
            $threads = $waits = (int)$params['thread'];
            while ( $threads --> 0 )
            {
                if ( !pcntl_fork() ) // child
                {
                    $this -> thread( $method, $tmp, (int)$params['repeat'], (int)$params['usleep'], $params );
                    exit;
                }
            }
            while ( $waits --> 0) pcntl_wait($status);
        }

        if ( $params['afterCase'] != false ) call_user_func_array( array($this, $params['afterCase']), array());
        $this -> afterCase(); // hook after case

        fseek($tmp,0);
        $passed = true;
        $time = 0;

        // get report of threads: case | pid | time | ms | status | errmsg
        while ( ( list( $mhd, $thd, $tm, $ms, $st, $em ) = fgetcsv($tmp, 0, "\t")) !== FALSE)
        {
            if ( $mhd === $method -> name )
            {
                if ( $st != true ) $passed = false;
                $time += $ms;
                $this -> output[ $name ][ $thd ][] = array(
                        'ms' => $ms,
                        'ok' => $st,
                        'tm' => $tm,
                        'err' => $em
                    );
            }
        }

        if ( $GLOBALS['SILENT_MODE'] !== true )
        {
            if ( $GLOBALS['VERBOSE_MODE'] !== true )
            {
                if ( $passed ) printf( $this -> color['pass'], "ok" );
                else printf( $this -> color['fail'], "error" );
            }
            else echo PHP_EOL.PHP_EOL;
            echo PHP_EOL;
        }

        $this -> output[ $name ]['description'] = array(
            'lines' => array( $method -> getStartLine(), $method -> getEndLine() ),
            'name' => $name,
        );

        fclose($tmp);
        if ( file_exists($meta['uri'])) unlink( $meta['uri'] );
    }

    /**
     * Run test theard
     *
     * @param  object  $method
     * @param  object  $hander
     * @param  integer $count
     */
    private function thread( &$method, &$hander, $count = 1, $ms = 0, &$params )
    {
        $this -> beforeThread(); // hook before thread
        if ( $params['beforeThread'] != false ) call_user_func_array( array($this, $params['beforeThread']), array());

        for( $repeat=0; $repeat < $count; $repeat++ ) // for repeat
        {
            if ( $ms > 0 ) usleep( $ms ); // Delays execution (ms)

            $this -> before(); // hook before case
            if ( $params['before'] != false ) call_user_func_array( array($this, $params['before']), array());

            $time = microtime( true );
            $exception = false;
            $return = null;

            try
            {
                $method -> invoke( $this, array() );
                $this -> pretty();
            }
            catch ( Exception $e )
            {
                $exception = $e -> getMessage();
                $this -> pretty( false );
            }

            fwrite($hander, sprintf (
                // case | pid | time | ms | status | errmsg
                "%s\t%d\t%0.6f\t%0.6f\t%b\t%s\n",
                $method -> name,
                getmypid(),
                $time,
                microtime( true ) - $time,
                $exception === false,
                $exception !== false ? $exception : ''
            ));

            if ( $params['after'] != false ) call_user_func_array( array($this, $params['after']), array());
            $this -> after(); // hook after case

        } // forend repeat

        if ( $params['afterThread'] != false ) call_user_func_array( array($this, $params['afterThread']), array());
        $this -> afterThread(); // hook after thread
    }

    /**
     * Hooks
     */
    public function before() {}
    public function after() {}
    public function beforeClass() {}
    public function afterClass() {}
    public function beforeThread() {}
    public function afterThread() {}
    public function beforeCase() {}
    public function afterCase() {}
}