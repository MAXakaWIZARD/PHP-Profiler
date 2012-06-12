<?php
/**
 * Port of PHP Quick Profiler by Ryan Campbell
 * Original URL: http://particletree.com/features/php-quick-profiler
 */
class Profiler_Display
{
    /**
     * Outputs the HTML, CSS and JavaScript that builds the console display
     *
     * @static
     *
     * @param      $data
     * @param bool $returnAsString
     *
     * @return mixed
     */
    public static function display($data, $returnAsString = false)
    {
        $output = self::getCssAndJavascript();

        $output .= '<div id="profiler-container" class="profiler hideDetails">';
        $output .= '<div id="profiler" class="console">';

        $output .= self::getMetricsTabs($data);

        $output .= self::getConsoleTab($data);
        $output .= self::getLoadTimeTab($data);
        $output .= self::getDatabaseTab($data);
        $output .= self::getMemoryTab($data);
        $output .= self::getFilesTab($data);
        $output .= self::getFooter();

        $output .= '</div></div>';

        if ($returnAsString) {
            return $output;
        } else {
            echo $output;
            return null;
        }
    }

    /**
     * @static
     *
     * @param $data
     *
     * @return string
     */
    public static function getMetricsTabs($data)
    {
        $logCount = count($data['logs']['console']['messages']);
        $fileCount = count($data['files']);
        $memoryUsed = $data['memoryTotals']['used'];
        $queryCount = $data['queryTotals']['all'];
        $speedTotal = $data['speedTotals']['total'];

        $tabs = array(
            'console' => array('title'=> 'Console', 'value'=> $logCount),
            'speed'   => array('title'=> 'Load Time', 'value'=> $speedTotal),
            'queries' => array('title'=> 'Database', 'value'=> $queryCount),
            'memory'  => array('title'=> 'Memory Used', 'value'=> $memoryUsed),
            'files'   => array('title'=> 'Included', 'value'=> $fileCount),
        );

        $output .= '<table id="profiler-metrics" cellspacing="0">';
        $output .= '<tr>';
        foreach ($tabs as $tabId => $tabData) {
            $output .= '<td id="' . $tabId . '" class="tab">';
            $output .= '<var>' . $tabData['value'] . '</var>';
            $output .= '<h4>' . $tabData['title'] . '</h4>';
            $output .= '</td>';
        }
        $output .= '</tr>';
        $output .= '</table>';

        return $output;
    }

    /**
     * @static
     *
     * @param $data
     *
     * @return string
     */
    public static function getConsoleTab($data)
    {
        $output .= '<div id="profiler-console" class="profiler-box">';

        if (count($data['logs']['console']['messages']) == 0) {
            $output .= '<h3>This panel has no log items.</h3>';
        } else {
            $output .= '<table class="side" cellspacing="0">';
            $output .= '<tr>';
            $output .= '<td class="console-log" id="console-log"><var>' . $data['logs']['console']['count']
                . '</var><h4>Logs</h4></td>';
            $output .= '<td class="console-errors" id="console-error"><var>' . $data['logs']['errors']['count']
                . '</var> <h4>Errors</h4></td>';
            $output .= '</tr>';
            $output .= '<tr>';
            $output .= '<td class="console-memory" id="console-memory"><var>' . $data['logs']['memory']['count']
                . '</var> <h4>Memory</h4></td>';
            $output .= '<td class="console-speed" id="console-speed"><var>' . $data['logs']['speed']['count']
                . '</var> <h4>Speed</h4></td>';
            $output .= '</tr>';
            $output .= '<tr>';
            $output
                .= '<td class="console-benchmarks" id="console-benchmark"><var>' . $data['logs']['benchmarks']['count']
                . '</var><h4>Benchmarks</h4></td>';
            $output .= '</tr>';
            $output .= '</table>';
            $output .= '<table class="main" cellspacing="0">';

            $class = '';
            foreach ($data['logs']['console']['messages'] as $log) {
                $output .= '<tr class="log-' . $log['type'] . '">';
                $output .= '<td class="type">' . $log['type'] . '</td>';
                $output .= '<td class="data ' . $class . '">';

                $output .= '<div>';

                switch ($log['type']) {
                    case 'log':
                        $output .= '<pre>' . $log['data'] . '</pre>';
                        break;
                    case 'memory':
                        $output .= '<pre>' . $log['data'] . '</pre>';
                        $output .= ' <em>' . $log['dataType'] . '</em>: ' . $log['name'];
                        break;
                    case 'benchmark':
                    case 'speed':
                        $output .= '<pre>' . $log['data'] . '</pre> <em>' . $log['name'] . '</em>';
                        break;
                    case 'error':
                        $output .= '<em>Line ' . $log['line'] . '</em> : ' . $log['data'];
                        $output .= ' <pre>' . $log['file'] . '</pre>';
                        break;
                }

                $output .= '</div></td></tr>';
                $class = ($class == '') ? 'alt' : '';
            }

            $output .= '</table>';
        }
        $output .= '</div>';

        return $output;
    }

    /**
     * @static
     *
     * @param $data
     *
     * @return string
     */
    public static function getLoadTimeTab($data)
    {
        $output .= '<div id="profiler-speed" class="profiler-box">';
        if ($data['logs']['speed']['count'] == 0) {
            $output .= '<h3>This panel has no log items.</h3>';
        } else {
            $output .= '<table class="side" cellspacing="0">';
            $output .= '<tr><td><var>' . $data['speedTotals']['total']
                . '</var><h4>Load Time</h4></td></tr>';
            $output .= '<tr><td class="alt"><var>' . $data['speedTotals']['allowed']
                . '</var> <h4>Max Execution Time</h4></td></tr>';
            $output .= '</table>';
            $output .= '<table class="main" cellspacing="0">';

            $class = '';
            foreach ($data['logs']['console']['messages'] as $log) {
                if (isset($log['type']) && $log['type'] == 'speed') {
                    $output .= '<tr class="log-speed"><td class="' . $class . '">';
                    $output .= '<div><pre>' . $log['data'] . '</pre> <em>' . $log['name'] . '</em></div>';
                    $output .= '</td></tr>';
                    $class = ($class == '') ? 'alt' : '';
                }
            }

            $output .= '</table>';
        }
        $output .= '</div>';

        return $output;
    }

    /**
     * @static
     *
     * @param $data
     *
     * @return string
     */
    public static function getDatabaseTab($data)
    {
        $output .= '<div id="profiler-queries" class="profiler-box">';
        if ($data['queryTotals']['count'] == 0) {
            $output .= '<h3>This panel has no log items.</h3>';
        } else {
            $output .= '<table class="side" cellspacing="0">';
            $output .= '<tr><td><var>' . $data['queryTotals']['count'] . '</var><h4>Total Queries</h4></td></tr>';
            $output
                .= '<tr><td class="alt"><var>' . $data['queryTotals']['time'] . '</var> <h4>Total Time</h4></td></tr>';
            $output
                .= '<tr><td><var>' . $data['queryTotals']['duplicates'] . '</var> <h4>Duplicates</h4></td></tr>';
            $output .= '<tr><td class="alt">';
            $output .= '<var>' . $data['queryTotals']['types']['select']['total'] . ' ('
                . $data['queryTotals']['types']['select']['percentage'] . '%)</var>';
            $output .= '<var>' . $data['queryTotals']['types']['select']['time'] . ' ('
                . $data['queryTotals']['types']['select']['time_percentage'] . '%)</var>';
            $output .= '<h4>Selects</h4>';
            $output .= '</td></tr>';
            $output .= '<tr><td>';
            $output .= '<var>' . $data['queryTotals']['types']['update']['total'] . ' ('
                . $data['queryTotals']['types']['update']['percentage'] . '%)</var>';
            $output .= '<var>' . $data['queryTotals']['types']['update']['time'] . ' ('
                . $data['queryTotals']['types']['update']['time_percentage'] . '%)</var>';
            $output .= '<h4>Updates</h4>';
            $output .= '</td></tr>';
            $output .= '<tr><td class="alt">';
            $output .= '<var>' . $data['queryTotals']['types']['insert']['total'] . ' ('
                . $data['queryTotals']['types']['insert']['percentage'] . '%)</var>';
            $output .= '<var>' . $data['queryTotals']['types']['insert']['time'] . ' ('
                . $data['queryTotals']['types']['insert']['time_percentage'] . '%)</var>';
            $output .= '<h4>Inserts</h4>';
            $output .= '</td></tr>';
            $output .= '<tr><td>';
            $output .= '<var>' . $data['queryTotals']['types']['delete']['total'] . ' ('
                . $data['queryTotals']['types']['delete']['percentage'] . '%)</var>';
            $output .= '<var>' . $data['queryTotals']['types']['delete']['time'] . ' ('
                . $data['queryTotals']['types']['delete']['time_percentage'] . '%)</var>';
            $output .= '<h4>Deletes</h4>';
            $output .= '</td></tr>';
            $output .= '</table>';
            $output .= '<table class="main" cellspacing="0">';

            $class = '';
            foreach ($data['queries'] as $query) {
                $output .= '<tr><td class="' . $class . '">' . $query['sql'];
                if ($query['duplicate']) {
                    $output .= '<strong style="display: block; color: #B72F09;">** Duplicate **</strong>';
                }

                if (isset($query['explain']) && $query['explain']) {
                    $explain = $query['explain'];
                    $output .= '<em>';

                    if (isset($explain['possible_keys'])) {
                        $output .= 'Possible keys: <b>' . $explain['possible_keys'] . '</b> &middot;';
                    }

                    if (isset($explain['key'])) {
                        $output .= 'Key Used: <b>' . $explain['key'] . '</b> &middot;';
                    }

                    if (isset($explain['type'])) {
                        $output .= 'Type: <b>' . $explain['type'] . '</b> &middot;';
                    }

                    if (isset($explain['rows'])) {
                        $output .= 'Rows: <b>' . $explain['rows'] . '</b> &middot;';
                    }

                    $output .= 'Speed: <b>' . $query['time'] . '</b>';
                    $output .= '</em>';
                } else {
                    if (isset($query['time'])) {
                        $output .= '<em>Speed: <b>' . $query['time'] . '</b></em>';
                    }
                }

                if (isset($query['profile']) && is_array($query['profile'])) {
                    $output .= '<div class="query-profile"><h4>&#187; Show Query Profile</h4>';
                    $output .= '<table style="display: none">';

                    foreach ($query['profile'] as $line) {
                        $output
                            .= '<tr><td><em>' . $line['Status'] . '</em></td><td>' . $line['Duration'] . '</td></tr>';
                    }

                    $output .= '</table>';
                    $output .= '</div>';
                }

                $output .= '</td></tr>';
                $class = ($class == '') ? 'alt' : '';
            }

            $output .= '</table>';
        }
        $output .= '</div>';

        return $output;
    }

    /**
     * @static
     *
     * @param $data
     *
     * @return string
     */
    public static function getMemoryTab($data)
    {
        $output .= '<div id="profiler-memory" class="profiler-box">';
        if ($data['logs']['memory']['count'] == 0) {
            $output .= '<h3>This panel has no log items.</h3>';
        } else {
            $output .= '<table class="side" cellspacing="0">';
            $output .= '<tr><td><var>' . $data['memoryTotals']['used'] . '</var><h4>Used Memory</h4></td></tr>';
            $output .= '<tr><td class="alt"><var>' . $data['memoryTotals']['total']
                . '</var> <h4>Total Available</h4></td></tr>';
            $output .= '</table>';
            $output .= '<table class="main" cellspacing="0">';

            $class = '';
            foreach ($data['logs']['console']['messages'] as $log) {
                if (isset($log['type']) && $log['type'] == 'memory') {
                    $output .= '<tr class="log-message">';
                    $output
                        .= '<td class="' . $class . '"><b>' . $log['data'] . '</b> <em>' . $log['dataType'] . '</em>: '
                        . $log['name'] . '</td>';
                    $output .= '</tr>';
                    $class = ($class == '') ? 'alt' : '';
                }
            }

            $output .= '</table>';
        }
        $output .= '</div>';

        return $output;
    }

    /**
     * @static
     *
     * @param $data
     *
     * @return string
     */
    public static function getFilesTab($data)
    {
        $output .= '<div id="profiler-files" class="profiler-box">';
        if ($data['fileTotals']['count'] == 0) {
            $output .= '<h3>This panel has no log items.</h3>';
        } else {
            $output .= '<table class="side" cellspacing="0">';
            $output .= '<tr><td><var>' . $data['fileTotals']['count']
                . '</var><h4>Total Files</h4></td></tr>';
            $output .= '<tr><td class="alt"><var>' . $data['fileTotals']['size']
                . '</var> <h4>Total Size</h4></td></tr>';
            $output .= '<tr><td><var>' . $data['fileTotals']['largest']
                . '</var> <h4>Largest</h4></td></tr>';
            $output .= '</table>';
            $output .= '<table class="main" cellspacing="0">';

            $class = '';
            foreach ($data['files'] as $file) {
                $output
                    .= '<tr><td class="' . $class . '"><b>' . $file['size'] . '</b> ' . $file['name'] . '</td></tr>';
                $class = ($class == '') ? 'alt' : '';
            }

            $output .= '</table>';
        }
        $output .= '</div>';

        return $output;
    }

    /**
     * @static
     * @return string
     */
    public static function getFooter()
    {
        $output .= '<table id="profiler-footer" cellspacing="0">';
        $output .= '<tr>';
        $output .= '<td class="credit"><a href="http://github.com/steves/PHP-Profiler" target="_blank"><strong>PHP</strong>&nbsp;Profiler</a></td>';
        $output .= '<td class="actions">';
        $output .= '<a class="detailsToggle" href="#">Details</a>';
        $output .= '<a class="heightToggle" href="#">Toggle Height</a>';
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';

        return $output;
    }

    /**
     * Outputs profiler console styles and javascript
     *
     * @static
     *
     */
    public static function getCssAndJavascript()
    {
        $baseDir = dirname(__FILE__);

        $css = file_get_contents($baseDir . '/resources/profiler.css');
        $output = '<style type="text/css">' . $css . '</style>';

        //$jqueryJs = file_get_contents($baseDir . '/resources/jquery-1.7.2.min.js');
        $profilerJs = file_get_contents($baseDir . '/resources/jquery.php-profiler.js');
        $output .= '<script type="text/javascript">';
        $output .= $jqueryJs;
        $output .= $profilerJs;
        $output .= '</script>';

        return $output;
    }
}