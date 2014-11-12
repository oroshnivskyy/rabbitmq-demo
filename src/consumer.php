<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPConnection('localhost', 5672, 'admin', '1111');
$channel = $connection->channel();

$channel->exchange_declare('direct_logs', 'direct', false, true, false);
$channel->basic_qos(0, 1, true);

list($queue_name, ,) = $channel->queue_declare("", false, false, false, false);

$sleep = (int)($argv[1] * 1000000);
$severities = array_slice($argv, 2);
if (empty($severities)) {
    file_put_contents('php://stderr', "Usage: $argv[0] 1 [info] [warning] [error]\n");
    exit(1);
}

foreach ($severities as $severity) {
    $channel->queue_bind($queue_name, 'direct_logs', $severity);
}

echo ' [*] Waiting for logs. Severities: ',join(', ', $severities), ' To exit press CTRL+C', "\n";

$callback = function (AMQPMessage $msg) use($sleep) {
    echo ' [x] ', $msg->delivery_info['routing_key'], ':', $msg->body, "\n";
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    usleep($sleep);
};

$channel->basic_consume($queue_name, '', false, false, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
