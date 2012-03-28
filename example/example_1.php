<?php
/**
 * Author: Alex Litvinok
 * Date: 3/15/12
 * Time: 6:02 AM
 * @suite: Example test
 */
class example_1 extends PHPLame
{
    public $aa = 111;

    /**
     * @test: a
     * @repeat: 3
     * @thread: 2
     * @tags: a, aa
     */
    public function case_one( $a )
    {
        sleep( rand(1,3));
        //echo ++$this -> aa;
    }

    public function case_too_test()
    {
        //echo ++$this -> aa;
        throw new Exception('W');
    }

}