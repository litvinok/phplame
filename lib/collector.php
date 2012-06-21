<?php
/**
 * User: Alex Litvinok
 * Date: 6/21/12
 * Time: 10:14 AM
 */
class PHPLameCollector
{
    /**
     * @static
     *
     * Enable Garbage Collector if exists
     */
    public static function enable()
    {
        if ( function_exists('gc_enable')) gc_enable();
    }

    /**
     * @static
     *
     * Disable  Garbage Collector
     */
    public static function disable()
    {
        if ( function_exists('gc_disable')) gc_disable();
    }

    /**
     * @static
     *
     * Cleaned up
     */
    public static function clean()
    {
        if ( function_exists('gc_collect_cycles') && function_exists('gc_enabled') && gc_enabled()) gc_collect_cycles();
    }
}
