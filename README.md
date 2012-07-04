Installation
------------

[![Master Build Status](https://secure.travis-ci.org/litvinok/phplame.png?branch=master)](http://travis-ci.org/litvinok/phplame) At this moment is clone from git.

Usage
------------

    $ phplame/phplame [options] <testcases>

Options
------------
    --junit         Path to JUnit reports.
    --json          Path to JSON reports
    --tags          Tags for select testcases
    --bootstrap     Bootstrap file
    --debug         Debug mode, print result to STDOUT
    --silent        Silent mode
    --verbose       Show status for each testcase
    --nocolor       Disallow colored bash
    --average       Enable average time for casetest of report (default: false)
    --time          Source time for check. May be user, sys or real as default
    -c | --config   Load settings from config file (json-formatted)

Features
------------

* Multithreading emulations
* Custom repeat of test and reporting average of time
* Use specific name of testsuite and testcase
* Custom hooks

Example
------------

    $ phplame/phplame --junit=reports/ --tags=aa --silent=true --average=true examples/

    $ phplame/phplame --json=reports/ --time=user examples/

    $ phplame/phplame -c=simple001.json

Example Config
------------
    {
        "bootstrap" : "bootstrap.php",
        "json" : "reports",
        "time" : "sys",
        "basedir" : [ "tests/01", "tests/02" ],

        "default" : {
            "invocations" : 100
        },

        "classes" : {
            "Tests" : {
                "Testcase 2" : { "invocation" : 10 }
            }
        }
    }