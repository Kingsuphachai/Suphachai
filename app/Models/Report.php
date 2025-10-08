<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'user_id', 'station_id', 'type', 'message', 'status',
    ];

    // ความสัมพันธ์
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function station()
    {
        return $this->belongsTo(ChargingStation::class, 'station_id');
    }

    // helper ง่าย ๆ
    public function getStatusTextAttribute()
    {
        return match ((int) $this->status) {
            1 => 'ปิดงานแล้ว',
            2 => 'ปฏิเสธ',
            default => 'รอตรวจสอบ',
        };
    }
}
