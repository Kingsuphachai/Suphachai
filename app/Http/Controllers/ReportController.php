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
    /**
     * รายการประเภทปัญหาที่ให้ผู้ใช้เลือก
     */
    private const TYPE_OPTIONS = [
        'no_power' => 'ไม่มีไฟ / ใช้งานไม่ได้',
        'occupied' => 'มีรถจอดขวาง/ไม่ว่าง',
        'broken'   => 'เครื่องชำรุด',
        'other'    => 'อื่น ๆ',
    ];

    public function create(Request $request)
    {
        $stations = ChargingStation::where('status_id', '!=', 0)
            ->orderBy('name')
            ->get(['id', 'name']);
        $stations = ChargingStation::where('status_id', 1)
            ->orderBy('name')
            ->get(['id','name']);
        $prefillStation = $request->query('station_id') ?? $request->query('station');

        $types = self::TYPE_OPTIONS;

        return view('user.report_station', compact('stations', 'prefillStation', 'types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'station_id' => ['required', Rule::exists('charging_stations', 'id')->where(function ($query) {
                $query->where('status_id', 1);
            })],
            'type'       => ['required', Rule::in(array_keys(self::TYPE_OPTIONS))],
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
