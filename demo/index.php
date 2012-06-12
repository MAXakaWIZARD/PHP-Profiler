<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('../Profiler.php');
require('../Console.php');
require('../Display.php');

$profiler = new Profiler_Profiler();

$profiler->logMemory();

$profiler->logBenchmark('bench');
usleep(2000);
$profiler->logBenchmark('bench');

$console = $profiler->display(true);
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Profiler demo</title>
    <script type="text/javascript" src="jquery-1.7.2.min.js"></script>
</head>
<body>
    <?php echo $console; ?>
</body>
</html>