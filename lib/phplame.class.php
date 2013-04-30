<?php
/**
 * User: Alex Litvinok <litvinok@gmail.com>
 * Date: 10/3/12 8:23 AM
 */

define( 'DEBUG_TRACE', TRUE );
define( 'TRACE_SEPARATOR', sprintf("%'-80s", '') );

class phplame extends helper
{
    /**
     * Array of test-suites. Have format: suite( file, class, params ).
     *
     * @var array
     */
    private $objects = array();

    /**
     * @var array
     */
    private $logs = array();

    /*
     * Run micro-benchmarking
     */
    function __construct()
    {
        // Parse options
        $args = array( 'c' => 'config', 'r' => 'reports-dir', 'd' => 'tests-dir', 'b' => 'bootstrap' );
        $options = getopt( implode(':',array_keys($args)).':' );

        // Convert from the short to the long options
        foreach( $args as $from => $to ) if (isset( $options[$from] ))
        {
            $options[$to] = $options[$from];
            unset($options[$from]);
        }

        // Load json-config
        if ( isset($options['config'])  )
        {
            self::config_load( $options['config'], $options );
        }

        // Welcome message
        self::trace( '>> PHPLame\'s Micro-Benchmarking' );

        // Check for set directory with tests
        if ( !isset($options['tests-dir']) )
        {
            self::trace( PHP_EOL.'Please, choose directory for test..'.PHP_EOL );
            return;
        }

        // Load bootstrap
        if ( isset($options['bootstrap']) && is_file($options['bootstrap']) )
        {
            include_once( $options['bootstrap'] );
        }

        // Parse chosen directory
        foreach( self::scan_dir_recursive( $options['tests-dir'] ) as $file )
        {
            $this -> __parse( $file );
        }

        // Executing benchmarks
        if ( sizeof( $this -> objects ) > 0 )
        {
            self::gc( true );
            $this -> __measure( $options['reports-dir'] );
            self::gc( false );
        }
    }

    /**
     * Checks file for search test-classes
     *
     * @param $file
     */
    private function __parse( $file )
    {
        try
        {
            include_once( $file );

            foreach( self::token_classes( $file ) as $class )
            {
                $reflection = new ReflectionClass( $class );
                $params = self::make_params( $reflection -> getDocComment() );

                if ( !empty($params['suite']) && ( !isset($params['disabled']) || $params['disabled'] == 'false' ) )
                {
                    $methods = array();

                    foreach( $reflection -> getMethods() as $method )
                    {
                        $opt = self::make_params( $method -> getDocComment() );
                        if ( !empty($opt['test']) && ( !isset($opt['disabled']) || $opt['disabled'] == 'false' ) )
                        {
                            $methods[] = (object)array( 'method' => $method, 'params' => $opt );
                        }
                    }

                    if ( sizeof( $methods ) > 0 )
                    {
                        $this -> objects[ $params['suite'] ][] = (object)array
                        (
                            'file' => $file,
                            'name' => $class,
                            'params' => $params,
                            'methods' => $methods
                        );
                    }
                }
            }
        }
        catch (Exception $e) { echo $e -> getMessage() .PHP_EOL; }
    }

    /**
     * @param bool $reports
     */
    private function __measure( &$reports = false )
    {
        $report = new report( $reports );

        foreach( $this -> objects as $name => &$items )
        {
            foreach( $items as &$class )
            {
                try
                {
                    $entity = new $class -> name();

                    $this -> __hook( $entity, 'beforeClass' );

                    foreach( $class -> methods as &$test )
                    {
                        try
                        {
                            self::trace( PHP_EOL.TRACE_SEPARATOR );
                            self::trace( $name .' :: '. $test -> params['test'] );
                            self::trace( TRACE_SEPARATOR );

                            $this -> __hook( $entity, 'before' );

                            $params = array_merge(
                                array( 'rounds' => 1, 'iterations' => 1, 'target_time' => 0, 'warmupRounds' => 0 ),
                                array( 'before' => null, 'after' => null, 'before_case' => null, 'after_case' => null ),
                                $test -> params
                            );

                            if ( $params['warmupRounds'] > 0 ) $this -> __benchmark(
                                $entity, $test -> method, $params, $params['warmupRounds']
                            );

                            if ( $params['target_time'] > 0 ) $params['iterations'] = $this -> __calibration(
                                $entity, $test -> method, $params, $params['target_time']
                            );

                            list( $real, $sys ) = $this -> __benchmark(
                                $entity, $test -> method, $params, $params['rounds'], $params['iterations']
                            );

                            $this -> __hook( $entity, 'after' );

                            self::trace( 'Rounds: '.$params['rounds'] );
                            self::trace( 'Iterations: '.$params['iterations'] );
                            self::trace( 'Average time: '.number_format($real, 4). ' sec.' );
                            self::trace( 'Average CPU time: '.number_format($sys, 4). ' sec.' );

                            $report -> pass( $name, $test, $params, $real, $sys ) ;

                            unset($params);
                        }
                        catch (Exception $e)
                        {
                            $report -> fail($name, $test, $e -> getMessage() );
                            self::trace( $e -> getMessage() );
                        }
                    }

                    $this -> __hook( $entity, 'afterClass' );
                    $report -> build( $name );

                    unset($entity);
                    self::gc();
                }
                catch (Exception $e) { self::trace( $e -> getMessage() ); }
            }
        }

        self::trace( PHP_EOL.TRACE_SEPARATOR );
    }

    /**
     * Class's hook-handler
     *
     * @param $class
     * @param $event
     */
    private function __hook( &$class, $event = '' )
    {
        if ( !empty($event) && method_exists( $class, $event) ) $class -> $event();
    }

    /**
     * Self-Calibration
     *
     * @param $class
     * @param $method
     * @param $params
     * @param $expected
     * @param int $iterations
     * @param int $limit
     * @return float|int
     */
    private function __calibration( &$class, &$method, &$params, $expected, $iterations = 1, $limit = 100 )
    {
        $trying = 0;

        do {
            if (isset($time)) $iterations = ( $time !== 0 ? floor( $iterations * $expected / $time ) : $iterations *2 );
            list( $time ) = $this -> __benchmark( $class, $method, $params, 1, $iterations );

            if ( ++$trying > $limit ) { self::trace( 'Failed calibration'); return 0; }
            self::trace( '[Try #'.($trying).'] Calibration for '.$iterations.' iterations = '.number_format($time, 6).' sec.');
        }
        while( ceil($time) < $expected );
        return $iterations > 0 ? $iterations : 1;
    }

    /**
     * Benchmarking
     *
     * @param $class
     * @param $method
     * @param $params
     * @param int $rounds
     * @param int $iterations
     * @return array
     */
    private function __benchmark( &$class, &$method, &$params, $rounds = 1, $iterations = 1 )
    {
        $real = $sys = 0;

        $this -> __hook( $class, 'beforeCase' );
        $this -> __hook( $class, $params['before_case'] );

        for( $r = 0; $r < $rounds; $r++ )
        {
            $this -> __hook( $class, $params['before'] );

            $marker_start_time = microtime( true );
            $marker_start_usage = getrusage();

            for( $i = 0; $i < $iterations; $i++ )
            {
                $method -> invoke( $class, array() );
            }

            $marker_end_time = microtime( true );
            $marker_end_usage = getrusage();

            $ru_stime_start = $marker_start_usage['ru_stime.tv_sec']*1e6 + $marker_start_usage['ru_stime.tv_usec'];
            $ru_stime_end = $marker_end_usage['ru_stime.tv_sec']*1e6 + $marker_end_usage['ru_stime.tv_usec'];

            $real += $marker_end_time - $marker_start_time;
            $sys  += $ru_stime_start === $ru_stime_end ? $ru_stime_end : $ru_stime_end - $ru_stime_start;

            $this -> __hook( $class, $params['after'] );
        }

        $this -> __hook( $class, $params['after_case'] );
        $this -> __hook( $class, 'afterCase' );

        self::gc();

        return array( $real / $rounds, ( $sys / $rounds ) / 1000000 );
    }

}
