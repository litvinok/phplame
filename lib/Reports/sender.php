<?php
/**
 * User: Alex Litvinok
 * Date: 8/13/12
 * Time: 4:14 AM
 */
class PHPLame_Sender
{
    /**
     * @var    request data
     */
    public $requests;

    /**
     * Create JSON report
     *
     * @param  string   $name
     * @param  array    $params
     * @param  array    $data
     */
    function __construct( $name, $params, array $data )
    {
        $label = sprintf( "PHP %s", substr(phpversion(),0,strpos(phpversion(), '-')) );
        $time = time();
        $this -> requests = array();

        foreach( $data as $title => $result )
        {
            $ref = &$result['time'][ $GLOBALS['TIME_SPEC_USER'] ];
            $this -> requests[] = array
            (
                'name' => strtoupper( $title ),
                'label' => $label,
                'time' => $time,
                'type' => $GLOBALS['TIME_SPEC_USER'],
                'class' => $name,
                'report' => array(
                    'executionTime' => $result['time']['real']['total'],
                    'invocations' => $result['count'],
                    'min' => $this -> number( $ref['min'] ),
                    'max' => $this -> number( $ref['max'] ),
                    'average' => $this -> number( $ref['avg']),
                    'median' => $this -> number( $ref['percent'][50] ),
                    '90percentile' => $this -> number( $ref['percent'][90] ),
                    'operationsPerSecond' => ( $ref['avg'] > 0 ? $this -> number( $result['repeats']['avg'] / $ref['avg'] ) : 0 ),
                    'durationOfOperation' => ( $result['repeats']['avg'] > 0 ? $this -> number( $ref['avg'] / $result['repeats']['avg'] ) : 0 ),
                ));
        }
    }

    /**
     * Destruct class
     */
    function __destruct()
    {
        unset($this -> requests );
        PHPLameCollector::clean();
    }

    /**
     * Send to server
     *
     * @param $server
     */
    public function send( $server, $method = "POST" )
    {
        $curl = curl_init( $server );
        curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $method );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true);

        foreach( $this -> requests as $data )
        {
            $json = json_encode($data);
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $json);
            curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json', 'Content-Length: ' . strlen($json)) );
            $result = curl_exec($curl);
        }

        return curl_close($curl);
    }

    /**
     * Return float number format
     *
     * @param $value
     */
    private function number( $value )
    {
        return (float)number_format( $value, 6, '.', '' );
    }
}
