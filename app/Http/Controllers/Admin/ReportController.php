<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ChargingStation;
use App\Models\StationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // รายการ + ฟิลเตอร์เบื้องต้น
    public function index(Request $request)
    {
        $request->validate([
            'status'     => ['nullable', 'in:all,0,1,2'],
            'station_id' => ['nullable', 'integer'],
            'q'          => ['nullable', 'string', 'max:255'],
        ]);

        $q = Report::query()
            ->with(['user:id,name,email', 'station:id,name']);

        // ฟิลเตอร์สถานะ (0=รอตรวจสอบ,1=ปิดงานแล้ว,2=ปฏิเสธ)
        if (($request->status ?? 'all') !== 'all') {
            $q->where('status', (int)$request->status);
        }

        // ฟิลเตอร์ตามสถานี
        if ($request->filled('station_id')) {
            $q->where('station_id', $request->station_id);
        }

        // ค้นหาจากข้อความแจ้ง (message) หรือประเภท (type)
        if ($request->filled('q')) {
            $kw = trim($request->q);
            $q->where(function($sub) use ($kw) {
                $sub->where('message','like',"%{$kw}%")
                    ->orWhere('type','like',"%{$kw}%");
            });
        }

        $reports  = $q->orderByDesc('id')->paginate(15)->withQueryString();
        $stations = ChargingStation::orderBy('name')->get(['id','name']);

        return view('admin.reports.index', compact('reports','stations'));
    }

    // รายละเอียดรายงาน
    public function show(Report $report)
    {
        $report->load(['user:id,name,email', 'station']);
        return view('admin.reports.show', compact('report'));
    }

    // ปิดงาน (แก้ไขเสร็จแล้ว)
    public function resolve(Report $report)
    {
        if ($report->status !== 1) {
            $report->status = 1; // ปิดงานแล้ว
            $report->save();
        }
        return redirect()->route('admin.reports.show', $report)->with('success', 'ปิดงานรายงานนี้เรียบร้อย');
    }

    // ปฏิเสธรายงาน
    public function reject(Report $report)
    {
        if ($report->status !== 2) {
            $report->status = 2; // ปฏิเสธ
            $report->save();
        }
        return redirect()->route('admin.reports.show', $report)->with('success', 'ปฏิเสธรายงานนี้เรียบร้อย');
    }

    // ลบรายงาน (ถ้าต้องการ)
    public function destroy(Report $report)
    {
        $report->delete();
        return redirect()->route('admin.reports.index')->with('success', 'ลบรายงานเรียบร้อย');
    }

    // ยืนยันรายงาน -> เปลี่ยนสถานีเป็นสถานะชำรุด
    public function confirm(Report $report)
    {
        $report->load('station');

        DB::transaction(function () use ($report) {
            if ($report->station) {
                $brokenStatusId = StationStatus::query()
                    ->where('name', 'like', '%ชำรุด%')
                    ->orWhere('name', 'like', '%เสีย%')
                    ->value('id');

                if (!$brokenStatusId) {
                    $brokenStatusId = 2; // fallback สำหรับข้อมูลเดิม
                }

                if ($report->station->status_id !== $brokenStatusId) {
                    $report->station->status_id = $brokenStatusId;
                    $report->station->save();
                }
            }

            $report->delete();
        });

        return redirect()
            ->route('admin.reports.index')
            ->with('success', 'ยืนยันรายงานเรียบร้อย และเปลี่ยนสถานะสถานีเป็นชำรุดแล้ว');
    }
}
