<?php
/**
 * Example test with use library PHPLame
 * Take look to keyword @suite, @test, etc.
 *
 * @suite: TDD PHPLame
 */
class Tests
{
    private $count = 0;

    /**
     * @test: Simple Test 1
     * @rounds: 3
     * @target_time: 40
     * @before_case: before_case
     */
    public function test_1()
    {
        if ($this -> count !== 2 ) throw new Exception('FAIL');

        sleep(1);
    }

    public function before()
    {
        $this -> count = 1;
    }

    public function before_case()
    {
        $this -> count = 2;
    }
}