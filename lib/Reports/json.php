<?php
/**
 * Author: Alex Litvinok
 * Date: 3/21/12
 * Time: 7:24 AM
 */

class PHPLame_JSON
{
    /**
     * @var    DOMDocument
     */
    public $document;

    /**
     * Create JSON report
     *
     * @param  string   $name
     * @param  array    $params
     * @param  array    $data
     */
    function __construct( $name, $params, array $data )
    {
        foreach( $data as $title => $result )
        {
            $ref = &$result['time'][ $GLOBALS['TIME_SPEC_USER'] ];
            $this -> document[] = array
            (
                'title' => $title,
                'executionTime' => $result['time']['real']['total'],
                'invocations' => $result['count'],
                'min' => $this -> number( $ref['min'] ),
                'max' => $this -> number( $ref['max'] ),
                'average' => $this -> number( $ref['avg']),
                'median' => $this -> number( $ref['percent'][50] ),
                '90percentile' => $this -> number( $ref['percent'][90] ),
                'operationsPerSecond' => ( $ref['avg'] > 0 ? $this -> number( $result['repeats']['avg'] / $ref['avg'] ) : 0 ),
                'durationOfOperation' => ( $result['repeats']['avg'] > 0 ? $this -> number( $ref['avg'] / $result['repeats']['avg'] ) : 0 ),
            );
        }
    }

    /**
     * Destruct class
     */
    function __destruct()
    {
        unset($this -> document );
        PHPLameCollector::clean();
    }

    /**
     * Save JSON
     *
     * @param $file
     */
    public function save( $file )
    {
        file_put_contents( $file, json_encode($this -> document) );
    }

    /**
     * Return float number format
     *
     * @param $value
     */
    private function number( $value )
    {
        return floatval(number_format( $value, 6, '.', '' ));
    }
}
