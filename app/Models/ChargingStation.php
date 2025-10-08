<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChargingStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','address','subdistrict_id','district_id','status_id',
        'latitude','longitude','operating_hours','created_by','image'
    ];
    public function getImageUrlAttribute(): ?string
    {
    return $this->image ? Storage::url($this->image) : null;
    }

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
