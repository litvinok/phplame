Installation
------------

At this moment is clone from git.

Usage
------------

    $ phplame/phplame [options] <testcases>

Options
------------
    --junit         Path to JUnit reports.
    --tags          Tags for select testcases
    --bootstrap     Bootstrap file
    --debug         Debug mode, print result to STDOUT
    --silent        Silent mode
    --verbose       Show status for each testcase
    --nocolor       Disallow colored bash
    --average       Enable average time for casetest of report (default: false)

Features
------------

* Multithreading emulations
* Custom repeat of test and reporting average of time
* Use specific name of testsuite and testcase
* Custom hooks

Example
------------

    $ phplame/phplame --junit=reports/ --tags=aa --silent=true --average=true examples/
