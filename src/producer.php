<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPConnection('localhost', 5672, 'admin', '1111');
$channel = $connection->channel();

$channel->exchange_declare('direct_logs', 'direct', false, true, false);

$severities = ["info", "error", "warning"];

$data = "Hello World!";
$sleep = (int)($argv[1] * 1000000);
echo $sleep;
$count = isset($argv[2])?$argv[2]:100;
for($i=0; $i<=$count; $i++){
    $messageSeverity = $severities[array_rand($severities)];
    $msg = new AMQPMessage($data, array('delivery_mode' => 2));
    $channel->basic_publish($msg, 'direct_logs', $messageSeverity);

    echo " [x] Sent ", $messageSeverity, " \n";
    usleep($sleep);
}


$channel->close();
$connection->close();