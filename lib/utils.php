<?php
/**
 * User: Alex Litvinok
 * Date: 7/5/12
 * Time: 6:39 AM
 */
class PHPLameUtils
{
    /**
     * Merge array. Saves all exist keys in arrays.
     * For example: { v: { a:2, b:4} } + { v: { a:1, c:3} } = { v: { a:1, b:4, c:3} }
     *
     * @static
     * @param array $A
     * @param array $B
     * @return array
     */
    public static function array_merge_assoc( array &$A, array &$B )
    {
        foreach( $A as $key => &$value ) if ( isset($B[$key]) )
            if ( is_array($value) && is_array($B[$key]) ) $value = self::array_merge_assoc($value, $B[$key]);
            else  $value = $B[$key];
        return array_merge( array_diff_key($B, $A), $A );
    }

    /**
     * Checks extension of file is PHP or not
     *
     * @param $file
     * @return boolean
     */
    public static function is_php( $file )
    {
        return in_array( pathinfo($file,PATHINFO_EXTENSION), array('php', 'php5', 'phplame', 'phpt') );
    }
}
