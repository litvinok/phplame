PHPLame
=======

Installation
------------

At this moment is clone from git.

Usage
------------

    $ phplame/phplame [options] <testcases>

Supported options
------------
    --junit         Path to JUnit reports.
    --tags          Tags for select testcases
    --bootstrap     Bootstrap file
    --debug         Debug mode, print result to STDOUT

Features
------------

* Multithreading
* Custom repeat of test and reporting average of time
* Use specific name of testsuite and testcase

Example
------------

    $ phplame/phplame --junit=reports/ --tags=aa examples/
