<?php

namespace App\Http\Controllers;

use App\Models\ChargingStation;
use App\Models\District;
use App\Models\Subdistrict;
use App\Models\ChargerType;
use App\Models\User; // ✅ เพิ่มบรรทัดนี้
use App\Notifications\StationRequested; // ✅ เพิ่มบรรทัดนี้
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserRequestController extends Controller
{
    public function create(Request $request)
    {
        return view('user.request_station', [
            'districts'    => District::orderBy('name')->get(['id','name']),
            'subdistricts' => Subdistrict::orderBy('name')->get(['id','name','district_id']),
            'chargers'     => ChargerType::orderBy('name')->get(['id','name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => ['required','string','max:255'],
            'address'         => ['nullable','string'],
            'district_id'     => ['required','integer', Rule::exists('districts','id')],
            'subdistrict_id'  => ['nullable','integer', Rule::exists('subdistricts','id')],
            'operating_hours' => ['nullable','string','max:100'],
            'latitude'        => ['nullable','numeric'],
            'longitude'       => ['nullable','numeric'],
            'charger_type_ids'=> ['array'],
            'charger_type_ids.*' => [Rule::exists('charger_types','id')],
            'image'           => ['nullable','image','mimes:jpg,jpeg,png,webp','max:3072'],
        ]);

        $station = ChargingStation::create([
            'name'            => $data['name'],
            'address'         => $data['address'] ?? null,
            'district_id'     => $data['district_id'],
            'subdistrict_id'  => $data['subdistrict_id'] ?? null,
            'status_id'       => 0, // รอตรวจสอบ
            'latitude'        => $data['latitude'] ?? null,
            'longitude'       => $data['longitude'] ?? null,
            'operating_hours' => $data['operating_hours'] ?? null,
            'created_by'      => auth()->id(),
            // ถ้ามีคอลัมน์ image ด้วย ให้เพิ่มได้ หรือ set ทีหลัง
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('stations', 'public');
            $station->image = $path;
            $station->save();
        }

        if (!empty($data['charger_type_ids'])) {
            $station->chargers()->sync($data['charger_type_ids']);
        }

        // ✅ แจ้งเตือนแอดมินทุกคน (role_id = 2)
        User::admins()->get()->each(function ($admin) use ($station) {
            $admin->notify(new StationRequested($station, auth()->user()));
        });

        return redirect()
            ->route('user.dashboard')
            ->with('success','ส่งคำขอเพิ่มสถานีเรียบร้อย รอแอดมินตรวจสอบ');
    }
}
