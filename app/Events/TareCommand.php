<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class TareCommand implements ShouldBroadcastNow
{
    public function __construct(
        public string $espId
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("timbangan-global"); // ← mengirim ke semua gateway, karna semua gateway subscribe ke timbangna-global
    }

    public function broadcastAs(): string
    {
        return 'timbangan.tare';
    }

    public function broadcastWith(): array  // ← tambah ini
    {
        return [
            'esp_id' => $this->espId,
        ];
    }
}
