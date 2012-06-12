<?php
/**
 * Port of PHP Quick Profiler by Ryan Campbell
 * Original URL: http://particletree.com/features/php-quick-profiler
 */
class Profiler_Profiler
{
    /**
     * Holds log data collected by Profiler_Console
     *
     * @var array
     */
    protected $_output = array();

    /**
     * Holds config data passed inot the constructor
     *
     * @var array
     */
    protected $_config = array();

    /**
     * The list of query types we care about for type specific stats
     *
     * @var array
     *
     */
    protected $_queryTypes = array(
        'select',
        'update',
        'delete',
        'insert'
    );

    /**
     * @var Profiler_Console
     */
    protected $_console;

    /**
     * @var int|mixed|null
     */
    protected $_startTime;

    /**
     * @var bool
     */
    protected $_enable = true;


    /**
     * Sets the configuration options for this object and sets the start time.
     *
     * Possible configuration options include:
     * query_explain_callback - Callback used to explain queries. Follow format used by call_user_func
     *
     * @param array $config    List of configuration options
     * @param int   $startTime Time to use as the start time of the profiler
     */
    public function __construct(array $config = array(), $startTime = null)
    {
        if (is_null($startTime)) {
            $startTime = microtime(true);
        }

        $this->_startTime = $startTime;
        $this->_config = $config;

        $this->_console = new Profiler_Console();
    }

    /**
     * enable profiler
     */
    public function enable()
    {
        $this->_enabled = true;
    }

    /**
     * disable profiler
     */
    public function disable()
    {
        $this->_enabled = false;
    }

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        return $this->_enabled;
    }

    /**
     * Shortcut for setting the callback used to explain queries.
     *
     * @param string|array $callback
     */
    public function setQueryExplainCallback($callback)
    {
        $this->_config['query_explain_callback'] = $callback;
    }

    /**
     * Shortcut for setting the callback used to interact with the MySQL
     * query profiler.
     *
     * @param string|array $callback
     */
    public function setQueryProfilerCallback($callback)
    {
        $this->_config['query_profiler_callback'] = $callback;
    }

    /**
     * Collects and aggregates data recorded by Profiler_Console.
     */
    public function gatherConsoleData()
    {
        $logs = $this->_console->getLogs();
        $result = $logs;

        foreach ($logs as $type => $item) {
            // Console data will already be properly formatted.
            if ($type == 'console') {
                continue;
            }

            // Ignore empty message lists
            if (!$item['count']) {
                continue;
            }

            foreach ($item['messages'] as $message) {
                $data = $message;

                switch ($type) {
                    case 'memory':
                        $data['type'] = 'memory';
                        $data['data'] = $this->getReadableFileSize($data['data']);
                        break;
                    case 'speed':
                        $data['type'] = 'speed';
                        $data['data'] = $this->getReadableTime($message['data'] - $this->_startTime);
                        break;
                    case 'benchmarks':
                        $data['type'] = 'benchmark';
                        $data['data'] = $this->getReadableTime($message['end_time'] - $message['start_time']);
                        break;
                }

                if (isset($data['type'])) {
                    $result['console']['messages'][] = $data;
                }
            }
        }

        $this->_output['logs'] = $result;
    }

    /**
     * Gathers and aggregates data on included files such as size
     */
    public function gatherFileData()
    {
        $files = get_included_files();
        $fileList = array();
        $fileTotals = array('count' => count($files), 'size' => 0, 'largest' => 0);

        foreach ($files as $key => $file) {
            $size = filesize($file);
            $fileList[] = array('name' => $file, 'size' => $this->getReadableFileSize($size));
            $fileTotals['size'] += $size;

            if ($size > $fileTotals['largest']) {
                $fileTotals['largest'] = $size;
            }
        }

        $fileTotals['size'] = $this->getReadableFileSize($fileTotals['size']);
        $fileTotals['largest'] = $this->getReadableFileSize($fileTotals['largest']);

        $this->_output['files'] = $fileList;
        $this->_output['fileTotals'] = $fileTotals;
    }

    /**
     * Gets the peak memory usage the configured memory limit
     */
    public function gatherMemoryData()
    {
        $memoryTotals = array();
        $memoryTotals['used'] = $this->getReadableFileSize(memory_get_peak_usage());
        $memoryTotals['total'] = ini_get('memory_limit');

        $this->_output['memoryTotals'] = $memoryTotals;
    }

    /**
     * Gathers and aggregates data regarding executed queries
     */
    public function gatherQueryData()
    {
        $queries = array();
        $typeDefault = array('total' => 0, 'time' => 0, 'percentage' => 0, 'time_percentage' => 0);
        $types = array(
            'select' => $typeDefault,
            'update' => $typeDefault,
            'insert' => $typeDefault,
            'delete' => $typeDefault
        );
        $queryTotals = array('all' => 0, 'count' => 0, 'time' => 0, 'duplicates' => 0, 'types' => $types);

        foreach ($this->_output['logs']['queries']['messages'] as $entries) {
            if (count($entries) > 1) {
                $queryTotals['duplicates'] += 1;
            }

            $queryTotals['count'] += 1;
            foreach ($entries as $i => $log) {
                if (isset($log['end_time'])) {
                    $query = array('sql'       => $log['sql'],
                                   'explain'   => $log['explain'],
                                   'time'      => ($log['end_time'] - $log['start_time']),
                                   'duplicate' => $i > 0 ? true : false);

                    // Lets figure out the type of query for our counts
                    $trimmed = trim($log['sql']);
                    $type = strtolower(substr($trimmed, 0, strpos($trimmed, ' ')));

                    if (in_array($type, $this->_queryTypes) && isset($queryTotals['types'][$type])) {
                        $queryTotals['types'][$type]['total'] += 1;
                        $queryTotals['types'][$type]['time'] += $query['time'];
                    }

                    // Need to get total times and a readable format of our query time
                    $queryTotals['time'] += $query['time'];
                    $queryTotals['all'] += 1;
                    $query['time'] = $this->getReadableTime($query['time']);

                    // If an explain callback is setup try to get the explain data
                    if (isset($this->_queryTypes[$type]) && isset($this->_config['query_explain_callback'])
                        && !empty($this->_config['query_explain_callback'])
                    ) {
                        $query['explain'] = $this->_attemptToExplainQuery($query['sql']);
                    }

                    // If a query profiler callback is setup get the profiler data
                    if (isset($this->_config['query_profiler_callback'])
                        && !empty($this->_config['query_profiler_callback'])
                    ) {
                        $query['profile'] = $this->_attemptToProfileQuery($query['sql']);
                    }

                    $queries[] = $query;
                }
            }
        }

        // Go through the type totals and calculate percentages
        foreach ($queryTotals['types'] as $type => $stats) {
            $totalPerc = !$stats['total'] ? 0 : round(($stats['total'] / $queryTotals['count']) * 100, 2);
            $timePerc = !$stats['time'] ? 0 : round(($stats['time'] / $queryTotals['time']) * 100, 2);

            $queryTotals['types'][$type]['percentage'] = $totalPerc;
            $queryTotals['types'][$type]['time_percentage'] = $timePerc;
            $queryTotals['types'][$type]['time'] = $this->getReadableTime($queryTotals['types'][$type]['time']);
        }

        $queryTotals['time'] = $this->getReadableTime($queryTotals['time']);
        $this->_output['queries'] = $queries;
        $this->_output['queryTotals'] = $queryTotals;
    }

    /**
     * Calculates the execution time from the start of profiling to *now* and
     * collects the congirued maximum execution time.
     */
    public function gatherSpeedData()
    {
        $speedTotals = array();
        $speedTotals['total'] = $this->getReadableTime(microtime(true) - $this->_startTime);
        $speedTotals['allowed'] = ini_get('max_execution_time');
        $this->_output['speedTotals'] = $speedTotals;
    }

    /**
     * Converts a number of bytes to a more readable format
     *
     * @param int    $size      The number of bytes
     * @param mixed $retstring The format of the return string
     *
     * @return string
     */
    public function getReadableFileSize($size, $retString = null)
    {
        $sizes = array('bytes', 'kB', 'MB', 'GB', 'TB');

        if ($retString === null) {
            $retString = '%01.2f %s';
        }

        $lastSizeString = end($sizes);

        foreach ($sizes as $sizeString) {
            if ($size < 1024) {
                break;
            }

            if ($sizeString != $lastSizeString) {
                $size /= 1024;
            }
        }

        if ($sizeString == $sizes[0]) {
            $retString = '%01d %s';
        }

        return sprintf($retString, $size, $sizeString);
    }

    /**
     * Converts a small time format (fractions of a millisecond) to a more readable format
     *
     * @param float $time
     *
     * @return int
     */
    public function getReadableTime($time)
    {
        if ($time < 0.001) {
            //microseconds
            $units = 'µs';
            $value = $time * 1000000;
        } elseif ($time < 1) {
            //milliseconds
            $units = 'ms';
            $value = $time * 1000;
        } elseif ($time >= 1 && $time < 60) {
            //seconds
            $units = 's';
            $value = $time;
        } else {
            //minutes
            $units = 'm';
            $value = $time / 60;
        }

        $value = number_format($value, 3, '.', '') . ' ' . $units;
        return $value;
    }

    /**
     * Collects data from the console and performs various calculations on it before
     * displaying the console on screen.
     *
     * @param bool $returnAsString
     *
     * @return mixed
     */
    public function display($returnAsString = false)
    {
        $this->gatherConsoleData();
        $this->gatherFileData();
        $this->gatherMemoryData();
        $this->gatherQueryData();
        $this->gatherSpeedData();

        return Profiler_Display::display($this->_output, $returnAsString);
    }

    /**
     * Used with a callback to allow integration into DAL's to explain an executed query.
     *
     * @param string $sql The query that is being explained
     *
     * @return array
     */
    protected function _attemptToExplainQuery($sql)
    {
        try {
            $sql = 'EXPLAIN ' . $sql;
            return call_user_func_array($this->_config['query_explain_callback'], $sql);
        } catch (Exception $e) {
            return array();
        }
    }

    /**
     * Used with a callback to allow integration into DAL's to profiler an execute query.
     *
     * @param string $sql The query being profiled
     *
     * @return array
     */
    protected function _attemptToProfileQuery($sql)
    {
        try {
            return call_user_func_array($this->_config['query_profiler_callback'], $sql);
        } catch (Exception $e) {
            return array();
        }
    }

    /**
     * Logs a variable to the console
     *
     * @param mixed $data The data to log to the console
     *
     * @return void
     */
    public function log($data)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->_console->log($data);
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
        if (!$this->isEnabled()) {
            return;
        }

        $this->_console->logMemory($object, $name);
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
        if (!$this->isEnabled()) {
            return;
        }

        $this->_console->logError($exception, $message);
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
        if (!$this->isEnabled()) {
            return;
        }

        $this->_console->logSpeed($name);
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
        if (!$this->isEnabled()) {
            return;
        }

        $this->_console->logQuery($sql, $explain);
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
        if (!$this->isEnabled()) {
            return;
        }

        $this->_console->logBenchmark($name);
    }
}