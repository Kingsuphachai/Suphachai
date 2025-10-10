<?php

namespace App\Notifications;

use App\Models\ChargingStation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class StationRequested extends Notification
{
    use Queueable;

    public function __construct(
        public ChargingStation $station,
        public ?User $requester
    ) {}

    public function via($notifiable)
    {
        return ['database']; // เก็บลงตาราง notifications
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type'   => 'station_requested',
            'title'  => 'คำขอเพิ่มสถานีใหม่',
            'body'   => $this->station->name . ' โดย ' . ($this->requester?->name ?? '-'),
            'url'    => route('admin.stations.pending'),
            'ids'    => ['station_id' => $this->station->id],
        ]);
    }
}
