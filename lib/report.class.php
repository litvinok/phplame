<?php
/**
 * User: Alex Litvinok <litvinok@gmail.com>
 * Date: 10/4/12 7:04 AM
 */

class report
{
    /**
     * @var array
     */
    private static $pass = array();

    /**
     * @var array
     */
    private static $fail = array();

    /**
     * @var
     */
    private static $path;

    /**
     * @param $path
     * @throws Exception
     */
    function __construct( $path )
    {
        self::$path = $path;

        // Try to create directory for reports
        if ((!empty( $path ) && !file_exists($path) && is_writable( dirname($path) )) && !mkdir( $path ))
        {
            throw new Exception('Please, create directory for reports!');
        }
    }

    /**
     * @param $class
     * @param $test
     * @param $params
     * @param $real
     * @param $sys
     */
    public function pass( &$class, &$test, &$params, &$real, &$sys )
    {
        self::$pass[ $class ][]  = array
        (
            'title'         => $test -> params['test'],
            'class'         => $class,
            'rounds'        => $params['rounds'],
            'iterations'    => $params['iterations'],
            'runTime'       => $real,
            'runTimeCPU'    => $sys
        );
    }

    /**
     * @param $class
     * @param $test
     * @param $message
     */
    public function fail( &$class, &$test, $message )
    {
        self::$fail[$class][] = array
        (
            'title'         => $test -> params['test'],
            'class'         => $class,
            'message'       => $message
        );
    }

    /**
     * @param $class
     */
    public function build( &$class )
    {
        if ( self::$path != FALSE )
        {
            $file = self::$path.DIRECTORY_SEPARATOR.preg_replace('/[^A-Za-z0-9_]/x','_', trim($class) );

            $this -> __json ( $file.'.json', self::$pass[ $class ] );
            $this -> __xml ( $file.'.xml', $class, self::$pass[ $class ], self::$fail[ $class ] );
        }
    }

    /**
     * @param $file
     * @param $array
     * @return int
     */
    private function __json( $file, &$array )
    {
        return file_put_contents( $file, json_encode($array), LOCK_EX );
    }

    /**
     * @param $file
     * @param $name
     * @param $passed
     * @param $failed
     * @return int
     */
    private function __xml( $file, $name, &$passed, &$failed )
    {
        $xml = new DOMDocument('1.0', 'UTF-8');

        $xml -> formatOutput = true;
        $xml -> preserveWhiteSpace = false;

        $root = $xml -> createElement('testsuites');
        $xml -> appendChild( $root );

        $child = $xml -> createElement( 'testsuite' );
        $time = 0;
        if (isset($passed)) foreach( $passed as &$item ) $time += $item['runTime'];

        $child -> appendChild( $this -> __xml_attribute($xml, 'name', $name ) );
        $child -> appendChild( $this -> __xml_attribute($xml, 'tests', sizeof($passed) + sizeof($failed) ) );
        $child -> appendChild( $this -> __xml_attribute($xml, 'errors', sizeof($failed)) );
        $child -> appendChild( $this -> __xml_attribute($xml, 'time', number_format( $time, 6) ) );

        if (isset($passed))
        {
            foreach( $passed as &$item )
            {
                $element = $xml -> createElement( 'testcase' );

                $element -> appendChild( $this -> __xml_attribute( $xml, 'name', $item['title'] ) );
                $element -> appendChild( $this -> __xml_attribute( $xml, 'classname', $item['class'] ) );
                $element -> appendChild( $this -> __xml_attribute( $xml, 'time', $item['runTime'] ) );
                $element -> appendChild( $this -> __xml_attribute( $xml, 'status', true ) );

                $child -> appendChild( $element );
            }
        }

        if ( isset($failed) )
        {
            foreach( $failed as &$item )
            {
                $element = $xml -> createElement( 'testcase' );

                $err = $xml -> createElement( 'error' );
                $err -> appendChild( $this -> __xml_attribute( $xml, 'message', $item['message'] ) );

                $element -> appendChild( $err );
                $child -> appendChild( $element );
            }
        }

        $root -> appendChild( $child );

        return $xml -> save( $file );
    }

    /**
     * @param $xml
     * @param $name
     * @param $value
     * @return mixed
     */
    private function __xml_attribute( &$xml, $name, $value )
    {
        $attr = $xml -> createAttribute( $name );
        $attr -> value = $value;
        return $attr;
    }
}
