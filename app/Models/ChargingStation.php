<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChargingStation extends Model
{
    use HasFactory;

    // 🔹 1) เพิ่ม image ใน fillable (เฮียมีแล้ว — ดีมาก!)
    protected $fillable = [
        'name','address','subdistrict_id','district_id','status_id',
        'latitude','longitude','operating_hours','created_by','image'
    ];

    // 🔹 2) เพิ่ม accessor ให้เรียก $station->image_url ใน Blade / API ได้
    public function getImageUrlAttribute(): ?string
    {
        // คืน URL ของไฟล์จาก storage/public หรือ null ถ้าไม่มี
        return $this->image ? Storage::url($this->image) : null;
    }

    // 🔹 (แนะนำ) ให้ Laravel serialize image_url อัตโนมัติเมื่อแปลงเป็น JSON
    protected $appends = ['image_url']; // เพิ่มตรงนี้

    // ✅ Relations
    public function status()
    {
        return $this->belongsTo(StationStatus::class,'status_id','id');
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class);
    }

    public function chargers()
    {
        return $this->belongsToMany(
            ChargerType::class,
            'station_charger_types',
            'station_id',
            'charger_type_id'
        );
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'station_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class,'created_by');
    }
}
