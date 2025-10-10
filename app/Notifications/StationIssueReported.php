<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Support\Facades\Schema;

class StationIssueReported extends Notification
{
    use Queueable;

    public function __construct(public Report $report) {}

    public function via($notifiable)
    {
        // Use database channel only when the notifications table exists to prevent runtime errors
        return Schema::hasTable('notifications') ? ['database'] : [];
    }

    public function toDatabase($notifiable): DatabaseMessage
    {
        // Map type to human-readable Thai labels
        $typeLabels = [
            'offline'   => 'ตู้/ระบบล่ม',
            'broken'    => 'หัวชาร์จชำรุด',
            'payment'   => 'ชำระเงิน/บิล',
            'occupied'  => 'ที่จอดถูกกีดกัน',
            'no_power'  => 'ไม่มีไฟ / ใช้งานไม่ได้',
            'other'     => 'อื่น ๆ',
        ];

        $stationName = $this->report->station?->name ?? '-';
        $reporter    = $this->report->user?->name ?? '-';
        $typeLabel   = $typeLabels[$this->report->type] ?? $this->report->type ?? '-';

        // Prefer showing the type; append a short message snippet if available
        $messageSnippet = trim((string)($this->report->message ?? ''));
        if ($messageSnippet !== '') {
            // limit snippet length to keep notification concise
            $messageSnippet = mb_strimwidth($messageSnippet, 0, 80, '…', 'UTF-8');
        }

        $body = $stationName . ' • ' . $typeLabel . ' • โดย ' . $reporter;
        if ($messageSnippet !== '') {
            $body .= ' • ' . $messageSnippet;
        }

        return new DatabaseMessage([
            'type'   => 'station_issue',
            'title'  => 'มีรายงานปัญหาสถานี',
            'body'   => $body,
            'url'    => route('admin.reports.index'),
            'ids'    => ['report_id' => $this->report->id],
        ]);
    }
}
