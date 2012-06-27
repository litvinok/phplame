<?php
/**
 * Example test with use library PHPLame
 * Take look to keyword @suite, @test, etc.
 *
 * @suite: TDD PHPLame
 */
class Tests extends PHPLame
{
    public $count = 0;
    public $save = 0;

    /**
     * @test: Testcase 1
     * @invocation: 100
     * @before: clean_count
     */
    public function testcase_1()
    {
        if ( $this -> count === 0 ) $this -> count++;
        else throw new Exception('Count not clean');
    }

    /**
     * @test: Testcase 2
     * @invocation: 100
     * @before: unclean_count
     * @after: clean_count
     */
    public function testcase_2()
    {
        if ( $this -> count !== 0 ) $this -> count++;
        else throw new Exception('Count clean');
    }

    /**
     * @test: Testcase 3
     * @repeat: 100
     * @invocation: 10
     * @beforecase: clean_count
     */
    public function testcase_3()
    {
        $this -> count;
        if ( $this -> count++ > $this -> save ) throw new Exception('Fail structure');
        else $this -> save++;
    }


    /**
     * @test: Testcase 4
     * @invocation: 100
     * @beforecase: unclean_count
     * @before: clean_count
     */
    public function testcase_4()
    {
        if ( $this -> count === 0 ) $this -> count++;
        else throw new Exception('Count clean');
    }

    public function clean_count()
    {
        $this -> count = 0;
        $this -> save = 1;
    }

    public function unclean_count()
    {
        $this -> count = 1;
        $this -> save = 0;
    }
}