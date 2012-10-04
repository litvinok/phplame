<?php
/**
 * User: Alex Litvinok <litvinok@gmail.com>
 * Date: 10/3/12 8:23 AM
 */

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

    /**
     * @param $paths
     */
    function __construct( $paths, $reports )
    {
        foreach( self::scan_dir_recursive($paths) as $file ) $this -> __parse( $file );

        if ( sizeof( $this -> objects ) > 0 )
        {
            self::gc( true );
            $this -> __measure( $reports );
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
            include( $file );

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
    private function __measure( $reports = false )
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
                            echo PHP_EOL. $name .' :: '. $test -> params['test'] .' .. ';

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

                            echo number_format( $real , 5 ) . 'sec /' . $params['iterations'];
                            $report -> pass( $name, $test, $params, $real, $sys ) ;

                            unset($params);
                        }
                        catch (Exception $e)
                        {
                            $report -> fail($name, $test, $e -> getMessage() );
                            echo $e -> getMessage();
                        }
                    }

                    $this -> __hook( $entity, 'afterClass' );
                    $report -> build( $name );

                    unset($entity);
                    self::gc();
                }
                catch (Exception $e) { echo $e -> getMessage() .PHP_EOL; }
            }
        }
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
     * @return float|int
     */
    private function __calibration( &$class, &$method, &$params, $expected )
    {
        list( $time ) = $this -> __benchmark( $class, $method, $params );
        $value = floor( $expected / $time );
        return $value > 0 ? $value : 1;
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

            $real += $marker_end_time - $marker_start_time;
            $sys  += ( $marker_end_usage['ru_stime.tv_sec']*1e6 + $marker_end_usage['ru_stime.tv_usec'] ) -
                     ( $marker_start_usage['ru_stime.tv_sec']*1e6 + $marker_start_usage['ru_stime.tv_usec'] );

            $this -> __hook( $class, $params['after'] );
        }

        $this -> __hook( $class, $params['after_case'] );
        self::gc();

        return array( $real / $rounds, ( $sys / $rounds ) / 1000000 );
    }

}
