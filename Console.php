<?php
/**
 * Port of PHP Quick Profiler by Ryan Campbell
 * Original URL: http://particletree.com/features/php-quick-profiler
 */
class Profiler_Console
{
    /**
     * Holds the logs used when the console is displayed.
     *
     * @var array
     */
    private $_logs
        = array(
            'console'    => array('messages' => array(), 'count' => 0),
            'memory'     => array('messages' => array(), 'count' => 0),
            'errors'     => array('messages' => array(), 'count' => 0),
            'speed'      => array('messages' => array(), 'count' => 0),
            'benchmarks' => array('messages' => array(), 'count' => 0),
            'queries'    => array('messages' => array(), 'count' => 0),
        );

    /**
     * Logs a variable to the console
     *
     * @param mixed $data The data to log to the console
     *
     * @return void
     */
    public function log($data)
    {
        $logItem = array(
            'data'     => $data,
            'type'     => 'log',
        );

        $this->_logs['console']['messages'][] = $logItem;
        $this->_logs['console']['count'] += 1;
    }

    /**
     * Logs the memory usage of the provided variable, or entire script
     *
     * @param object $object Optional variable to log the memory usage of
     * @param string $name   Optional name used to group variables and scripts together
     *
     * @return void
     */
    public function logMemory($object = null, $name = 'PHP')
    {
        $memory = $object ? strlen(serialize($object)) : memory_get_usage();

        $logItem = array(
            'data'     => $memory,
            'name'     => $name,
            'dataType' => gettype($object)
        );

        $this->_logs['memory']['messages'][] = $logItem;
        $this->_logs['memory']['count'] += 1;
    }

    /**
     * Logs an exception or error
     *
     * @param Exception $exception
     * @param string    $message
     *
     * @return void
     */
    public function logError($exception, $message)
    {
        $logItem = array(
            'data' => $message,
            'type' => 'error',
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        );

        $this->_logs['errors']['messages'][] = $logItem;
        $this->_logs['errors']['count'] += 1;
    }

    /**
     * Starts a timer, a second call to this method will end the timer and cause the
     * time to be recorded and displayed in the console.
     *
     * @param string $name
     *
     * @return void
     */
    public function logSpeed($name = 'Point in Time')
    {
        $logItem = array(
            'data' => microtime(true),
            'name' => $name
        );

        $this->_logs['speed']['messages'][] = $logItem;
        $this->_logs['speed']['count'] += 1;
    }

    /**
     * Records how long a query took to run when the same query is passed in twice.
     *
     * @param      $sql
     * @param null $explain
     *
     * @return mixed
     */
    public function logQuery($sql, $explain = null)
    {
        // We use a hash of the query for two reasons. One is because for large queries the
        // hash will be considerably smaller in memory. The second is to make a dump of the
        // logs more easily readable.
        $hash = md5($sql);

        // If this query is in the log we need to see if an end time has been set. If no
        // end time has been set then we assume this call is closing a previous one.
        if (isset($this->_logs['queries']['messages'][$hash])) {
            $query = array_pop($this->_logs['queries']['messages'][$hash]);
            if (!$query['end_time']) {
                $query['end_time'] = microtime(true);
                $query['explain'] = $explain;

                $this->_logs['queries']['messages'][$hash][] = $query;
            } else {
                $this->_logs['queries']['messages'][$hash][] = $query;
            }

            $this->_logs['queries']['count'] += 1;
            return;
        }

        $logItem = array(
            'start_time' => microtime(true),
            'end_time'   => false,
            'explain'    => false,
            'sql'        => $sql
        );

        $this->_logs['queries']['messages'][$hash][] = $logItem;
    }

    /**
     * Records the time it takes for an action to occur
     *
     * @param string $name The name of the benchmark
     *
     * @return void
     *
     */
    public function logBenchmark($name)
    {
        $key = 'benchmark_ ' . $name;

        if (isset($this->_logs['benchmarks']['messages'][$key])) {
            $benchKey = md5(microtime(true));

            $this->_logs['benchmarks']['messages'][$benchKey] = $this->_logs['benchmarks']['messages'][$key];
            $this->_logs['benchmarks']['messages'][$benchKey]['end_time'] = microtime(true);
            $this->_logs['benchmarks']['count'] += 1;

            unset($this->_logs['benchmarks']['messages'][$key]);
            return;
        }

        $logItem = array(
            'start_time' => microtime(true),
            'end_time'   => false,
            'name'       => $name
        );

        $this->_logs['benchmarks']['messages'][$key] = $logItem;
    }

    /**
     * Returns all log data
     *
     * @return array
     */
    public function getLogs()
    {
        return $this->_logs;
    }
}