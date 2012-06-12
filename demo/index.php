<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('../Profiler.php');
require('../Console.php');
require('../Display.php');

$profiler = new Profiler_Profiler();

$profiler->log('test message');

$profiler->logMemory();

$profiler->logBenchmark('bench');
usleep(5000);
$profiler->logBenchmark('bench');

$console = $profiler->display(true);
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Profiler demo</title>
</head>
<body>
    <?php echo $console; ?>
</body>
</html>