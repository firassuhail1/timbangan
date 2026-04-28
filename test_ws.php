<?php

// test_ws.php
require 'vendor/autoload.php';

use Ratchet\Client\Connector;
use React\EventLoop\Loop;

$loop = Loop::get();
$connector = new Connector($loop);

$appKey = 'e3aujdygmblsanrlfsfu';
$url = "ws://127.0.0.1:8080/app/{$appKey}?protocol=7&client=test&version=1.0";

$connector($url)->then(function($conn) use ($loop) {
    echo "Connected!\n";

    $conn->send(json_encode([
        'event' => 'pusher:subscribe',
        'data'  => ['channel' => 'timbangan-global']
    ]));

    $i = 0;
    $loop->addPeriodicTimer(1, function() use ($conn, &$i) {
        $i++;
        $payload = json_encode([
            'event'   => 'berat.updated',
            'channel' => 'timbangan-global',
            'data'    => json_encode([
                'esp_id' => 'Test-01',
                'berat'  => $i * 100.0,
            ])
        ]);
        $conn->send($payload);
        echo "Sent #{$i}: " . ($i * 100) . "gr\n";
    });

    $conn->on('close', function($code, $reason) {
        echo "CLOSED! code=$code reason=$reason\n";
    });
});

$loop->run();