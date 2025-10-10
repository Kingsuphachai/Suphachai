<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ChargingStation;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    // หน้าแบบฟอร์ม
    public function create()
    {
        // ดึงสถานีเฉพาะที่ "มองเห็นได้" (เช่น status_id != 0) หรือทั้งหมดก็ได้
        $stations = ChargingStation::with('district')
            ->orderBy('name')
            ->get(['id','name','district_id']);

        // ประเภทปัญหาไว้เลือกใน <select>
        $types = [
            'no_power' => 'ไม่มีไฟ / ใช้งานไม่ได้',
            'occupied' => 'มีรถจอดขวาง/ไม่ว่าง',
            'broken'   => 'เครื่องชำรุด',
            'other'    => 'อื่น ๆ',
        ];

        return view('user.reports.create', compact('stations','types'));
    }

    // รับฟอร์มและบันทึก
    public function store(Request $request)
    {
        $data = $request->validate([
            'station_id' => ['required','integer', Rule::exists('charging_stations','id')],
            'type'       => ['required', Rule::in(['no_power','occupied','broken','other'])],
            'message'    => ['required','string','max:2000'],
        ],[
            'station_id.required' => 'กรุณาเลือกสถานี',
            'type.required'       => 'กรุณาเลือกประเภทปัญหา',
            'message.required'    => 'กรุณากรอกรายละเอียด',
        ]);

        Report::create([
            'user_id'    => auth()->id(),
            'station_id' => $data['station_id'],
            'type'       => $data['type'],
            'message'    => $data['message'],
            'status'     => 0, // 0 = รอตรวจสอบ
        ]);

        return redirect()
            ->route('user.dashboard')
            ->with('success','ส่งรายงานปัญหาเรียบร้อย เราจะตรวจสอบให้เร็วที่สุด');
    }
}
