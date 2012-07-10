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
                'min' => number_format( $ref['min'], 6 ),
                'max' => number_format( $ref['max'], 6 ),
                'average' => number_format( $ref['avg'], 6 ),
                'median' => number_format( $ref['percent'][50], 6),
                '90percentile' => number_format( $ref['percent'][90], 6 ),
                'operationsPerSecond' => ( $ref['avg'] > 0 ? number_format( $result['repeats']['avg'] / $ref['avg'] , 6 ) : 0 ),
                'durationOfOperation' => ( $result['repeats']['avg'] > 0 ? number_format( $ref['avg'] / $result['repeats']['avg'] , 6 ) : 0 ),
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
}
