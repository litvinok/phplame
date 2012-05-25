<?php
/**
 * Example test with use library PHPLame
 * Take look to keyword @suite, @test, etc.
 *
 * @suite: Example test
 */
class example_1 extends PHPLame
{
    public $aa = 111;

    /**
     * @test: a
     * @repeat: 3
     * @invocation: 2
     * @thread: 2
     * @tags: a, aa
     * @before: before_case_one
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

    public function before_case_one()
    {
        $this -> aa++;
    }
}