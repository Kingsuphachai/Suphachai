<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ChargingStation;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // ฟิลเตอร์เบื้องต้น
        $request->validate([
            'status' => ['nullable','in:all,pending,resolved,rejected'],
            'q'      => ['nullable','string','max:255'],
        ]);

        $query = Report::query()->with(['user','station']);

        // สถานะ
        $status = $request->input('status','pending');
        if ($status === 'resolved')   $query->where('status', 1);
        elseif ($status === 'rejected') $query->where('status', 2);
        elseif ($status === 'pending')  $query->where('status', 0);

        // ค้นหาจากข้อความ
        if ($request->filled('q')) {
            $kw = trim($request->q);
            $query->where(function($q) use ($kw) {
                $q->where('type','like',"%$kw%")
                  ->orWhere('message','like',"%$kw%");
            });
        }

        $reports = $query->orderBy('id','desc')->paginate(15)->withQueryString();

        return view('admin.reports.index', compact('reports', 'status'));
    }

    public function show(Report $report)
    {
        $report->load(['user','station']);
        return view('admin.reports.show', compact('report'));
    }

    public function resolve(Report $report)
    {
        $report->update(['status' => 1]);
        return redirect()->route('admin.reports.show', $report)->with('success', 'ปิดงานรายงานนี้เรียบร้อย');
    }

    public function reject(Report $report)
    {
        $report->update(['status' => 2]);
        return redirect()->route('admin.reports.show', $report)->with('success', 'ปฏิเสธรายงานนี้เรียบร้อย');
    }

    public function destroy(Report $report)
    {
        $report->delete();
        return redirect()->route('admin.reports.index')->with('success', 'ลบรายงานเรียบร้อย');
    }
}
