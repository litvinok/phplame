<?php
/**
 * Author: Alex Litvinok
 * Date: 3/21/12
 * Time: 7:24 AM
 */

class PHPLame_JUnit
{
    /**
     * @var    DOMDocument
     */
    public $document;

    /**
     * Create JUnit report
     *
     * @param  string   $name
     * @param  array    $params
     * @param  array    $data
     */
    function __construct( $name, $params, array $data )
    {
        $this -> document = new DOMDocument('1.0', 'UTF-8');
        $this->document->formatOutput = true;
        $this->document->preserveWhiteSpace = false;

        $root = $this -> document -> createElement('testsuites');
        $this -> document -> appendChild( $root );

        $suitename = isset($params['suite']) && !empty($params['suite']) ?  $params['suite'] : $name;
        $this -> testsuite( $root, $suitename, $data );
    }

    /**
     * Add atribute by node
     *
     * @param  object   $parent
     * @param  string   $name
     * @param  mixed    $value
     */
    function attribute( $parent, $name, $value )
    {
        $attribute = $this -> document -> createAttribute( $name );
        $attribute -> value = $value;
        $parent -> appendChild( $attribute );
    }

    /**
     * Add node </testsuite>
     *
     * @param  object   $parent
     * @param  string   $name
     * @param  array    $data
     */
    function testsuite( $parent, $name, $data )
    {
        $child = $this -> document -> createElement( 'testsuite' );

        $duration = 0.0;
        $tests = 0;
        $faild = 0;

        foreach( $data as $namecase => $case )
        {
            $time = 0.0;
            $count = 0;
            $status = true;
            $errmsg = false;

            $description = $case['description']; unset($case['description']);

            foreach( $case as $thread ) foreach( $thread as $repeat )
            {
                $count++;
                $time += $repeat['ms'];
                if ( $status != false ) $status = $repeat['ok'];
                if ( empty($errmsg) ) $errmsg = $repeat['err'];
            }

            $testcase = $this -> testcase( $child, array(
                'name' =>  $namecase,
                'classname' => $name,
                'time' => sprintf( '%0.6f', $GLOBALS['AVERAGE_MODE'] === true ? $time / $count : $time ),
                'status' => $status,
                'line' => $description['lines'][0],
            ));

            if ( !$status ) $faild ++;
                $tests ++; $duration += $time / $count;

            if ( !empty( $errmsg ) )
                $this -> section( $testcase, 'error', array( 'message' =>  $errmsg ));
        }

        $this -> attribute( $child, 'name', $name );
        $this -> attribute( $child, 'tests', $tests );
        $this -> attribute( $child, 'errors', $faild );
        $this -> attribute( $child, 'time', sprintf( '%0.6f', $duration ) );

        $parent -> appendChild( $child );
    }

    /**
     * Add node </testcase>
     *
     * @param  object   $parent
     * @param  array    $attributes
     */
    function testcase( $parent, $attributes = array() )
    {
        $child = $this -> document -> createElement( 'testcase' );
        foreach( $attributes as $key => $val ) $this -> attribute( $child, $key, $val );
        $parent -> appendChild( $child );
        return $child;
    }

    /**
     * Add node by parent
     *
     * @param  object   $parent
     * @param  string   $section
     * @param  array    $attributes
     */
    function section( $parent, $section, $attributes = array() )
    {
        $child = $this -> document -> createElement( $section );
        foreach( $attributes as $key => $val ) $this -> attribute( $child, $key, $val );
        $parent -> appendChild( $child );
    }
}
