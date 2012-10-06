Introduction
------------

PHPLame [![Build Status](https://secure.travis-ci.org/litvinok/phplame.png?branch=v2)](http://travis-ci.org/litvinok/phplame) - library for micro-benchmarging in PHP.

Features
------------

* Measurement execution time and calculate CPU time.
* Self-Calibration to expected time

Installation
------------

The clone or [download](https://github.com/litvinok/phplame/downloads "download") from git.

Usage
------------
	$ phplame <options>

Options
------------

Command line arguments:

    -c		Loads settings from the config file (json-formatted)
	-b		Path to bootstrap file
    -d		Path to directory with tests
    -r		Path to save reports

Options of config file:

    bootstrap		Path to bootstrap file
	config			Path to other config files
	reports-dir		Path to save reports
	tests-dir		Path to directory with tests

* You can use an array of arguments
* %DIR% - this is directory where located current config file

Examples
------------

	$ phplame -c config.json
	$ phplame -c config_01.json -c config_02.json
	$ phplame -d tests -r ../reports -c tests/config.json

Example of config file:

	{
		"bootstrap"   : "%DIR%/libs/bootstrap.php",
    	"tests-dir"   : "%DIR%/tests",
    	"reports-dir" : "%DIR%/reports"
	}

Example of test:

	/**
	 * @suite:		<required name suite>
	 * @disabled:	[true/false]
	 */
	class Test
	{
		/**
	     * @test:			<required name test>
	     * @warmupRounds:	<count iterations for warmUp>
	     * @rounds:			<count rounds>
		 * @iterations:		<count iterations>
	     * @target_time:	<time for self-calibrations>
	     * @before_case:	<method name for call before the benchmark>
		 * @after_case:		<method name for call after the benchmark>
		 * @before:			<method name for call before each a iteration>
		 * @after:			<method name for call after each a iteration>
	     */
	    public function test_1(){}

		// The calls before benchmark method
		public function before() {}

		// The calls after benchmark
		public function after() {}

		// The calls before class
		public function beforeClass() {}

		// The calls after class
		public function afterClass() {}
	}

*Required for run: @suite and @test*


Reports
------------

Each the suite has two report files: [JUnit](http://www.junit.org/ "JUnit") and JSON.

The JSON report has following format:

	[
		{
			title:		String,
			class:		String,
			rounds:		Number,
			iterations:	Number,
			runTime:	Number,
			runTimeCPU:	Number
		},
		...
	]

License
------------
Apache License, Version 2.0

[http://www.apache.org/licenses/LICENSE-2.0.html](http://www.apache.org/licenses/LICENSE-2.0.html "http://www.apache.org/licenses/LICENSE-2.0.html")