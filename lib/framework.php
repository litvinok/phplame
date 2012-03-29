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

    function __construct( $options = array() )
    {
        $class = new ReflectionClass( $this );
        $cases = array();
        $output = array();

        foreach ( $class -> getMethods() as $method ) // for case
        {
            $params = array();
            $comment = $method -> getDocComment();

            if ( strlen($comment) !== FALSE )
            {
                preg_match_all("/@(\w+)\s*(?::\s*(.*))?/x", $comment, $matchs );
                foreach( $matchs[1] as $key => $name) $params[ strtolower($name) ] = trim( $matchs[2][$key] );
            }

            if ( strpos( $method -> name, 'test') || isset($params['test']) ) // if case
            {
                $accept = true;
                if ( !isset($params['repeat']) ) $params['repeat'] = 1;
                if ( !isset($params['thread']) ) $params['thread'] = 1;

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
    private function pretty( $pass = true ) { echo $pass ? '.' : 'F'; }

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

        if ( (int)$params['thread'] <= 1 )
        {
            $this -> thread( $method, $tmp, (int)$params['repeat'] );
        }
        else
        {
            $threads = $waits = (int)$params['thread'];
            while ( $threads --> 0 )
            {
                if ( !pcntl_fork() ) // child
                {
                    $this -> thread( $method, $tmp, (int)$params['repeat'] );
                    exit;
                }
            }
            while ( $waits --> 0) pcntl_wait($status);
        }

        fseek($tmp,0);

        // get report of threads: case | pid | time | ms | status | errmsg
        while ( ( list( $mhd, $thd, $tm, $ms, $st, $em ) = fgetcsv($tmp, 0, "\t")) !== FALSE)
        {
            if ( $mhd === $method -> name )
            {
                $this -> output[ $name ][ $thd ][] = array(
                        'ms' => $ms,
                        'ok' => $st,
                        'tm' => $tm,
                        'err' => $em
                    );
            }
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
    private function thread( &$method, &$hander, $count = 1 )
    {
        $this -> beforeThread(); // hook before thread

        for( $repeat=0; $repeat < $count; $repeat++ ) // for repeat
        {
            $this -> before(); // hook before case

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

            $this -> after(); // hook after case

        } // forend repeat

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
}