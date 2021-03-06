# PHP Profiler #

by MAXakaWIZARD
<maxakawizard@gmail.com>

## Introduction ##
PHP-Profiler is a fork of [PHP-Profiler](https://github.com/steves/PHP-Profiler) by Steven Surowiec

## Installation ##
Setup is very easy and straight-forward. There are five primary steps that need to be done.

1. Checkout the code into your libraries directory so that the classes can be auto loaded.
2. Instantiate Profiler_Profiler
3. At the end of your application after all else is done call the display() method on Profiler_Profiler.

## Setup and Usage ##
Setting up PHP Profiler is quite simple. Below is a short code sample of the latest version.

    $profiler = new Profiler_Profiler();
    $profiler->logSpeed('Start Sample run');
    $profiler->logVarMemory($object);
    $profiler->logSpeed('End Sample run');
    $profiler->display();

Exceptions can also be logged:

    try {
      // Some code goes here
      throw new Exception('Some exception');
    }
    catch (Exception $e) {
      $profiler->logError($e);
    }

Database queries can be logged as well:

    $profiler->logQuery($sql);  // Starts timer for query
    $res = mysql_query($sql);
    $profiler->logQuery($sql);  // Ends timer for query

Using a custom callback to explain queries for console

    $profiler = Profiler_Profiler(array('query_explain_callback' => array('My_Class', 'someMethod')));
    $profiler->logQuery($sql); // Starts timer for query
    $res = mysql_query($sql);
    $profiler->logQuery($sql); // Ends timer for query
    $profiler->display();

    class My_Class {
      // $sql gets passed in with 'EXPLAIN' already added.
      public static function someMethod($sql) {
        $res = mysql_query($sql);
        return mysql_fetch_assoc($res);
      }
    }

## Configuration ##
PHP Profiler lets you pass in some configuration options to help allow it to suit your own needs.

- **query_explain_callback** is the callback used to explain SQL queries to get additional information on them. The format used should be the same as that used by PHP's [call_user_func](http://us2.php.net/call_user_func) function.
- **query_profiler_callback** is used to integrate an extended query profiler such as [MySQL's query profiler](http://wiki.github.com/steves/PHP-Profiler/the-extended-query-profiler).

For additional documentation and code samples see the wiki.

## Features ##
Below are some of the features of PHP Profiler

- Log any string, array or object to the console
- Log all queries and find out how long they took to run, individually and total
- Learn which queries are being run more than once with duplicate query counting
- Allows integration with your DAL to explain executed queries
- Displays all included files
- Displays total memory usage of page load
- Log memory usage of any string, variable or object
- Log specific points in your script to see how long it takes to get to them
- See how many queries on a given page are inserts, updates, selects and deletes with query type counting