<?php

namespace App\Http\Controllers;

use App\Models\ChargingStation;
use App\Models\Report;
use App\Models\User; // ✅ เพิ่ม
use App\Notifications\StationIssueReported; // ✅ เพิ่ม
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function create(Request $request)
    {
        $stations = ChargingStation::orderBy('name')->get(['id','name']);
        $prefillStation = $request->query('station');

        $types = [
            'offline'   => 'ตู้/ระบบล่ม',
            'broken'    => 'หัวชาร์จชำรุด',
            'payment'   => 'ชำระเงิน/บิล',
            'occupied'  => 'ที่จอดถูกกีดกัน',
            'other'     => 'อื่น ๆ',
        ];

        return view('user.report_station', compact('stations', 'prefillStation', 'types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'station_id' => ['required', Rule::exists('charging_stations', 'id')],
            'type'       => ['required', Rule::in(['offline','broken','payment','occupied','other'])],
            'message'    => ['required','string','max:2000'],
        ]);

        $payload = [
            'user_id'    => Auth::id(),
            'station_id' => $data['station_id'],
            'type'       => $data['type'],
            'message'    => $data['message'],
        ];

        if (Schema::hasColumn('reports', 'status')) {
            $payload['status'] = 0; // รอตรวจสอบ
        }

        // ✅ เก็บตัวแปร $report
        $report = Report::create($payload);

        // แจ้งเตือนแอดมิน; ป้องกัน error จาก notification ไม่ให้กระทบผู้ใช้
        try {
            User::admins()->get()->each(function ($admin) use ($report) {
                $admin->notify(new StationIssueReported($report));
            });
        } catch (\Throwable $e) {
            Log::warning('Failed to notify admins for report ID '.$report->id.': '.$e->getMessage());
        }

        return redirect()
            ->route('user.dashboard')
            ->with('success', 'ส่งรายงานปัญหาเรียบร้อย ทีมงานจะตรวจสอบต่อไป');
    }

    public function my()
    {
        $reports = Report::with('station:id,name')
            ->where('user_id', Auth::id())
            ->orderByDesc('id')
            ->paginate(15);

        return view('user.my_reports', compact('reports'));
    }
}
