<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('../Profiler.php');
require('../Console.php');
require('../Display.php');

$profiler = new Profiler_Profiler();

$profiler->log('Test message');

try {
    throw new Exception('Some exception');
} catch (Exception $e) {
    $profiler->logError($e);
}

$profiler->logMemory('Memory consumption at this point');

$profiler->logBenchmark('Bench');
usleep(5000);
$profiler->logBenchmark('Bench');

$profiler->logPeakMemory();

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