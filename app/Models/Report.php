<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    public $timestamps = false; // ตารางนี้มีเฉพาะ created_at ตาม dump (ถ้ามี updated_at ด้วย ให้ลบบรรทัดนี้)
    protected $fillable = ['user_id', 'station_id', 'type', 'message', 'status'];

    public function station()
    {
        return $this->belongsTo(ChargingStation::class, 'station_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
        protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // ถ้ามีฟิลด์เวลาอื่นๆ ก็ใส่ได้ เช่น:
        // 'reported_at' => 'datetime',
    ];
}
