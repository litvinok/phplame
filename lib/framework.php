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

    function __construct( $options = array(), &$class_options = array() )
    {
        $class = new ReflectionClass( $this );
        $cases = array();
        $output = array();

        if ( $GLOBALS['NOCOLOR_MODE'] !== true )
        {
            $this -> color = array(
                'name' => "* \033[1;32m[%s]\033[0m %s",
                'pass' => "\033[1;32m%s\033[0m",
                'fail' => "\033[1;41m%s\033[0m",
            );
        }
        else $this -> color = array( 'name' => "[%s] %s", 'pass' => "%s", 'fail' => "%s" );

        foreach ( $class -> getMethods() as $method ) // for case
        {
            $comment = $method -> getDocComment();
            $params = array();

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

                    $tags = array_flip( preg_split('/\s*,\s*/', $params['tags'], -1, PREG_SPLIT_NO_EMPTY) );

                    foreach( preg_split('/\s*,\s*/', $options['tags'], -1, PREG_SPLIT_NO_EMPTY) as $tag )
                    {
                        if ( strpos( $tag, '~' ) === 0 && !isset($tags[$tag]) ) $accept = true;
                        elseif ( strpos( $tag, '~' ) !== 0 && isset($tags[$tag]) ) $accept = true;
                    }
                }

                if ( isset($params['disabled']) && $params['disabled'] !== 'false' ) $accept = false;

                if ( $accept )
                {
                    $template = array(
                        'invocations' => 1,
                        'repeats' => 1,
                        'threads' => 1,
                        'duration' => 0,
                        'warmup' => 0,
                        'usleep' => 0,
                        'before' => false,
                        'after' => false,
                        'beforethread' => false,
                        'afterthread' => false,
                        'beforecase' => false,
                        'aftercase' => false,
                    );

                    $casename = isset($params['test']) && strlen($params['test']) ? $params['test'] : $method -> name;
                    $this -> load_params( $template, $options, $class_options["suite"], $casename );
                    $params = array_merge($template, $params);

                    $cases[ $method -> name ]['name'] = $casename;
                    $cases[ $method -> name ]['method'] = $method;
                    $cases[ $method -> name ]['params'] = $params;
                }
            }
        }

        $this -> beforeClass(); // hook before class
        foreach ( $cases as $ref )
        {
            PHPLameCollector::clean();
            $this -> cases($ref['name'], $ref['method'], $ref['params']);
        }
        $this -> afterClass(); // hook after class
    }

    /**
     * Destruct class
     */
    function __destruct()
    {
        unset($this -> output);
        unset($this -> color );
        PHPLameCollector::clean();
    }

    /**
     * Load custom params for each testcase
     *
     * @param $setParams
     * @param $getParams
     * @param string $className
     * @param string $methodName
     */
    private function load_params( &$setParams, &$getParams, $className = null, $methodName = null )
    {
        // Load default section
        if ( isset( $getParams["default"] ) && is_array( $getParams["default"] ) )
        {
            $setParams = array_merge( $setParams, $getParams["default"] );
        }

        // Load params for class method
        if ( empty($className) && isset( $getParams[ $methodName ] ) )
        {
            $setParams = array_merge( $setParams, $getParams[ $methodName ] );
        }

        // Load default params for class
        if ( !empty($className) && isset( $getParams["classes"][ $className ] ) )
        {
            $this -> load_params( $setParams, $getParams["classes"][ $className ], null, $methodName );
        }
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
            printf( $this -> color['name'], date('d M Y T H:i:s'), $name );
            if ( $GLOBALS['VERBOSE_MODE'] !== true ) echo ' .. ';
            else echo ':'. PHP_EOL;
        }

        $this -> beforeCase(); // hook before case
        if ( $params['beforecase'] != false ) call_user_func_array( array($this, $params['beforecase']), array());

        if ( (int)$params['threads'] <= 1 )
        {
            $this -> thread( $method, $tmp, (int)$params['invocations'], (int)$params['repeats'], (int)$params['duration'], (int)$params['usleep'], $params );
        }
        else
        {
            $threads = $waits = (int)$params['threads'];
            while ( $threads --> 0 )
            {
                if ( !pcntl_fork() ) // child
                {
                    $this -> thread( $method, $tmp, (int)$params['invocations'], (int)$params['repeats'], (int)$params['duration'], (int)$params['usleep'], $params );
                    exit;
                }
            }
            while ( $waits --> 0) pcntl_wait($status);
        }

        if ( $params['aftercase'] != false ) call_user_func_array( array($this, $params['aftercase']), array());
        $this -> afterCase(); // hook after case

        fseek($tmp,0);

        $template_time = array
        (
            'total' => 0,
            'percent' => array(),
            'invocations' => array(),
        );

        $status = array
        (
            'ok' => true,
            'err' => '',
            'count' => 0,
            'time' => array( 'real' => $template_time, 'user' => $template_time, 'sys' => $template_time ),
            'description' => array( 'lines' => array( $method -> getStartLine(), $method -> getEndLine() )
            )
        );

        // get report of threads: case | pid | time | rtime | utime | stime | status | errmsg
        while ( ( list( $mhd, $thd, $tm, $rtm, $utm, $stm, $st, $em ) = fgetcsv($tmp, 0, "\t")) !== FALSE)
        {
            if ( $mhd === $method -> name )
            {
                if ( $st != true ) $status['ok'] = false;
                elseif( !empty($em) ) $status['err'] = $em;

                $status['count'] ++;

                foreach( array( 'real' => $rtm, 'user' => $utm, 'sys' => $stm ) as $what => $tm )
                {
                    $ref = &$status['time'][ $what ];

                    $ref['invocations'][] = $tm;
                    $ref['total'] += $tm;

                    if ( !isset($ref['min']) || $ref['min'] > $tm ) $ref['min'] = $tm;
                    if ( !isset($ref['max']) || $ref['max'] < $tm ) $ref['max'] = $tm;

                    unset($ref);
                }
            }
        }

        foreach ( $status['time'] as $key => &$ref )
        {
            $ref['avg'] = round( $ref['total'] / $status['count'] );
            foreach ( array( 10, 50, 90 ) as $percentile )
            {
                $targetCount = $percentile * $status['count'] / 100;
                $ref['percent'][$percentile] = $ref['max'];
                $count = 0;

                for ($value = $ref['min']; $value <= $ref['max']; $value++ )
                {
                    $count += count( array_search( $value, $ref['invocations'], true ) );
                    if ( $count >= $targetCount ) { $ref['percent'][$percentile] = $value; break; }
                }
                $ref['percent'][$percentile] /= 1000000;
            }

            foreach( array( 'avg', 'min', 'max', 'total' ) as $e ) $ref[$e] /= 1000000;
            unset($ref['invocations']);
        }

        $this -> output[ $name ] = $status;

        if ( $GLOBALS['SILENT_MODE'] !== true )
        {
            if ( $GLOBALS['VERBOSE_MODE'] !== true )
            {
                if ( $status['ok'] ) printf( $this -> color['pass'], "ok" );
                else printf( $this -> color['fail'], "error" );
            }
            else echo PHP_EOL.PHP_EOL;
            echo PHP_EOL;
        }

        unset($status); fclose($tmp);
        if ( file_exists($meta['uri'])) unlink( $meta['uri'] );
    }

    /**
     * Run test theard
     *
     * @param  object  $method
     * @param  object  $hander
     * @param  integer $count
     */
    private function thread( &$method, &$hander, $count = 1, $repeats = 1, $duration = 0, $ms = 0, &$params )
    {
        $this -> beforeThread(); // hook before thread
        if ( $params['beforethread'] != false ) call_user_func_array( array($this, $params['beforethread']), array());

        for( $invocation=0; $invocation < $count; $invocation++ ) // for invocation
        {
            PHPLameCollector::clean(); // gc clean
            if ( $ms > 0 ) usleep( $ms ); // Delays execution (ms)

            $this -> before(); // hook before case
            if ( $params['before'] != false ) call_user_func_array( array($this, $params['before']), array());

            $repeat = $repeats;
            $exception = false;
            $return = null;
            $usage[0] = getrusage();
            $time = microtime( true );

            try
            {
                while( $repeat --> 0 ) $method -> invoke( $this, array() );
                $this -> pretty();
            }
            catch ( Exception $e )
            {
                $exception = $e -> getMessage();
                $this -> pretty( false );
            }

            $usage[1] = getrusage();

            $systime = ( $usage[1]['ru_stime.tv_sec']*1e6 + $usage[1]['ru_stime.tv_usec'] )
                     - ( $usage[0]['ru_stime.tv_sec']*1e6 + $usage[0]['ru_stime.tv_usec'] );

            $usrtime = ( $usage[1]['ru_utime.tv_sec']*1e6 + $usage[1]['ru_utime.tv_usec'] )
                     - ( $usage[0]['ru_utime.tv_sec']*1e6 + $usage[0]['ru_utime.tv_usec'] );

            unset( $usage );

            fwrite($hander, sprintf (
                // case | pid | time | rtime | utime | stime | status | errmsg
                "%s\t%d\t%d\t%d\t%d\t%d\t%b\t%s\n",
                $method -> name,
                getmypid(),
                $time,
                ( microtime( true ) - $time ) * 1000000,
                $usrtime,
                $systime,
                $exception === false,
                $exception !== false ? $exception : ''
            ));

            if ( $params['after'] != false ) call_user_func_array( array($this, $params['after']), array());
            $this -> after(); // hook after case

        } // forend invocation

        if ( $params['afterthread'] != false ) call_user_func_array( array($this, $params['afterthread']), array());
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