<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Events\BeratUpdated;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use React\EventLoop\Loop;

class ListenTimbangan extends Command
{
    protected $signature   = 'timbangan:listen';
    protected $description = 'Listen berat dari ESP32 via Reverb (channel global)';

    public function handle(): void
    {
        $this->info('[Worker] Memulai listener...');

        $loop   = Loop::get();
        $appKey = config('broadcasting.connections.reverb.key');
        $host   = config('broadcasting.connections.reverb.options.host', '127.0.0.1');
        $port   = config('broadcasting.connections.reverb.options.port', 8080);
        $url    = "ws://{$host}:{$port}/app/{$appKey}?protocol=7&client=laravel-worker&version=1.0";

        $this->info("[Worker] Target: {$url}");

        $connect = null;
        $connect = function () use (&$connect, $loop, $url) {
            $connector = new Connector($loop);

            $connector($url)->then(
                function (WebSocket $conn) use (&$connect, $loop) {
                    $this->info('[Worker] Terhubung ke Reverb!');

                    // Subscribe channel global
                    $conn->send(json_encode([
                        'event' => 'pusher:subscribe',
                        'data'  => ['channel' => 'timbangan-global'],
                    ]));
                    $this->info('[Worker] Subscribe: timbangan-global');

                    $conn->on('message', function ($msg) {
                        $this->handleMessage((string) $msg);
                    });

                    $conn->on('close', function ($code, $reason) use (&$connect, $loop) {
                        $this->warn("[Worker] Putus ({$code}), reconnect 3 detik...");
                        $loop->addTimer(3, $connect);
                    });
                },
                function (\Exception $e) use (&$connect, $loop) {
                    $this->error('[Worker] Gagal: ' . $e->getMessage());
                    $loop->addTimer(3, $connect);
                }
            );
        };

        $connect();
        $loop->run();
    }

    private function handleMessage(string $raw): void
    {
        $decoded = json_decode($raw, true);
        if (!$decoded) return;

        $event = $decoded['event'] ?? '';

        // Abaikan event internal Pusher
        if (str_starts_with($event, 'pusher:')) {
            // Balas ping
            if ($event === 'pusher:ping') {
                // wsClient tidak accessible di sini,
                // Reverb handle ping/pong otomatis
            }
            return;
        }

        // Terima berat dari ESP32
        if ($event === 'client-berat.updated') {
            $data = is_array($decoded['data'] ?? null)
                ? $decoded['data']
                : json_decode($decoded['data'] ?? '{}', true);
            if (!$data) return;

            $espId = $data['esp_id'] ?? null;
            $berat = floatval($data['berat'] ?? 0);

            if (!$espId) return;

            $this->info(sprintf(
                '[Worker] %s → %.1f gr (stable: %s)',
                $espId,
                $berat,
                ($data['is_stable'] ?? false) ? 'ya' : 'tidak'
            ));

            // Update Cache
            Cache::put("timbangan_live_{$espId}", $berat, now()->addMinutes(7));

            $currentId = Cache::get("current_id_{$espId}");
            if ($currentId) {
                Cache::put(
                    "weight_preview_{$espId}_{$currentId}",
                    $berat,
                    now()->addSeconds(30)
                );
            }

            // Broadcast ke browser
            broadcast(new BeratUpdated($espId, $berat));
            $this->info('[Worker] Broadcast ke browser ✓');
        }

        // Log heartbeat
        if ($event === 'client-heartbeat') {
            $data = is_array($decoded['data'] ?? null)
                ? $decoded['data']
                : json_decode($decoded['data'] ?? '{}', true);
            $this->line(sprintf(
                '[Heartbeat] %s — uptime: %ds, rssi: %d',
                $data['esp_id']  ?? '?',
                $data['uptime']  ?? 0,
                $data['rssi']    ?? 0
            ));
        }
    }
}
