<?php

namespace App\Http\Controllers;

use App\Models\ChargingStation;
use Illuminate\Http\Request;

class UserRequestController extends Controller
{
    public function create()
    {
        // dropdown ที่ใช้ในฟอร์ม (ถ้าจะทำภายหลังค่อยใส่)
        $districts = \App\Models\District::orderBy('name')->get(['id','name']);
        $subdistricts = \App\Models\Subdistrict::orderBy('name')->get(['id','name','district_id']);
        $statuses = \App\Models\StationStatus::orderBy('id')->get(['id','name']); // เผื่ออยากโชว์สี/คำอธิบาย

        return view('user.request_station', compact('districts','subdistricts','statuses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required','string','max:255'],
            'address'        => ['nullable','string'],
            'district_id'    => ['required','integer','exists:districts,id'],
            'subdistrict_id' => ['nullable','integer','exists:subdistricts,id'],
            'latitude'       => ['nullable','numeric'],
            'longitude'      => ['nullable','numeric'],
            'operating_hours'=> ['nullable','string','max:100'],
        ]);

        ChargingStation::create([
            ...$data,
            'status_id'  => 0,                 // รอตรวจสอบ
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('user.dashboard')
            ->with('success','ส่งคำขอเพิ่มสถานีเรียบร้อย รอผู้ดูแลตรวจสอบ');
    }
}
