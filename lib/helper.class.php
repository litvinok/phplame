<?php
/**
 * User: Alex Litvinok <litvinok@gmail.com>
 * Date: 10/3/12 8:31 AM
 */

class helper
{
    /**
     * Get all php scripts by directory.
     * Function get all files in directory O.o
     *
     * @param $path
     * @return array
     */
    protected static function scan_dir_recursive( $path )
    {
        $files = array();

        if ( is_array($path) )
        {
            foreach( $path as $item )
            {
                $files = array_merge( $files, self::scan_dir_recursive( $item ));
            }
            return $files;
        }
        elseif ( is_file($path) ) return array($path);

        if ( is_dir($path) && $handle = opendir($path))
        {
            while ( false !== ($file = readdir($handle)) )
            {
                if ( $file!= '.' && $file != '..' && ( $target = $path .DIRECTORY_SEPARATOR. $file ) )
                {
                    if ( is_dir($target) ) $files = array_merge( $files, self::scan_dir_recursive( $target ));
                    elseif ( self::is_php( $file ) ) $files[] = $target;
                }
            }
            closedir($handle);
        }
        return $files;
    }

    /**
     * Merge array. Saves all exist keys in arrays.
     * For example: { v: { a:2, b:4} } + { v: { a:1, c:3} } = { v: { a:1, b:4, c:3} }
     *
     * @static
     * @param array $A
     * @param array $B
     * @return array
     */
    protected static function array_merge_assoc( array &$A, array &$B )
    {
        foreach( $A as $key => &$value )
        {
            if ( isset($B[$key]) )
            {
                if ( is_array($value) && is_array($B[$key]) )
                {
                    $value = self::array_merge_assoc($value, $B[$key]);
                }
                else $value = $B[$key];
            }
        }
        return array_merge( array_diff_key($B, $A), $A );
    }

    /**
     * Checks extension of file is PHP or not
     *
     * @param $file
     * @return boolean
     */
    protected static function is_php( $file )
    {
        return in_array( pathinfo($file,PATHINFO_EXTENSION), array('php', 'php5', 'phplame', 'phpt') );
    }

    /**
     * Returns array of classes from a file
     *
     * @param $file
     * @return array
     */
    protected static function token_classes( $file )
    {
        $tokens = token_get_all( file_get_contents($file) );
        $classes = array();

        for ( $idn = 2; $idn < count($tokens); $idn++ )
        {
            if ( $tokens[$idn-2][0] === T_CLASS  &&
                 $tokens[$idn-1][0] === T_WHITESPACE &&
                 $tokens[$idn][0]   === T_STRING )
            {
                $classes[] = $tokens[$idn][1];
            }
        }

        return $classes;
    }

    /**
     * Returns params from the string by format @key:value
     *
     * @param $string
     * @return array
     */
    protected static function make_params( $string )
    {
        $params = array();

        if ( strlen($string) !== FALSE && preg_match_all('/@(\w+)\s*(?::\s*(.*))?/x', $string, $match ) )
        {
            foreach ( $match[1] as $key => $name)
            {
                $params[ strtolower($name) ] = trim( $match[2][$key] );
            }
        }

        return $params;
    }

    /**
     * GC handler. TRUE MODE is enable, FALSE is disable and NULL is clear.
     *
     * @param null $mode
     * @return bool|int|void
     */
    protected static function gc( $mode = NULL )
    {
        if ( $mode === TRUE && function_exists('gc_enable') )
        {
            return gc_enable();
        }
        elseif( $mode === FALSE && function_exists('gc_disable'))
        {
            return gc_disable();
        }
        elseif( function_exists('gc_collect_cycles') && function_exists('gc_enabled') && gc_enabled() )
        {
            return gc_collect_cycles();
        }

        return false;
    }

    /**
     * @param $string
     */
    protected static function trace( $string )
    {
        if ( DEBUG_TRACE === TRUE ) echo $string.PHP_EOL;
    }

    /**
     * @param $path
     * @param $options
     */
    protected static function config_load( $path, &$options )
    {
        if ( is_array($path))
        {
            foreach( $path as &$item )
            {
                $options = self::array_merge_assoc($options, self::config_load($item, $options ));
            }
        }

        if ( is_file($path) )
        {
            $json = (array)json_decode(file_get_contents($path));
            self::option_replace( $json, '%DIR%', dirname($path) );

            $options = self::array_merge_assoc( $options, $json );
            unset($json);
        }


    }

    /**
     * @param $values
     * @param $find
     * @param $set
     */
    protected static function option_replace( &$values, $find, $set )
    {
        foreach( $values as &$item )
        {
            if ( is_array($item) || is_object($item)) self::option_replace($item, $find, $set);
            else $item = str_replace( $find, $set, $item);
        }
    }
}
