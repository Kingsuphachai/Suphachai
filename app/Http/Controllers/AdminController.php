<?php

namespace App\Http\Controllers;

use App\Models\ChargingStation;
use App\Models\User;
use App\Models\StationStatus;
use App\Models\District;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // หน้า dashboard หลัก (ลิงก์ไป 2 ตาราง)
    public function dashboard()
    {
        $now = Carbon::now();
        $stationCounts = [
            'active' => ChargingStation::where('status_id', 1)->count(),
            'broken' => ChargingStation::where('status_id', 2)->count(),
            'pending' => ChargingStation::where('status_id', 0)->count(),
        ];
        $stationsTotal = array_sum($stationCounts);

        $reportsTotal = Report::count();
        $reportsPending = Report::where('status', 0)->count();
        $reportsThisMonth = Report::whereBetween('created_at', [$now->copy()->startOfMonth(), $now])->count();
        $reportsToday = Report::whereDate('created_at', $now->toDateString())->count();

        $stats = [
            'stations_total' => $stationsTotal,
            'stations_active' => $stationCounts['active'],
            'stations_broken' => $stationCounts['broken'],
            'stations_pending' => $stationCounts['pending'],
            'stations_active_pct' => $stationsTotal ? round(($stationCounts['active'] / $stationsTotal) * 100, 1) : 0,
            'stations_broken_pct' => $stationsTotal ? round(($stationCounts['broken'] / $stationsTotal) * 100, 1) : 0,
            'users_total' => User::count(),
            'new_users_week' => User::where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'admins' => User::where('role_id', 2)->count(),
            'reports_total' => $reportsTotal,
            'reports_pending' => $reportsPending,
            'reports_this_month' => $reportsThisMonth,
            'reports_today' => $reportsToday,
        ];

        $reportTypeLabels = [
            'no_power' => 'ไม่มีไฟ / ใช้งานไม่ได้',
            'occupied' => 'มีรถจอดขวาง/ไม่ว่าง',
            'broken'   => 'เครื่องชำรุด',
            'other'    => 'อื่น ๆ',
        ];

        $reportTypeSummary = Report::select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) use ($reportTypeLabels, $reportsTotal) {
                return [
                    'type' => $row->type,
                    'label' => $reportTypeLabels[$row->type] ?? ucfirst((string) $row->type),
                    'total' => $row->total,
                    'percent' => $reportsTotal ? round(($row->total / $reportsTotal) * 100) : 0,
                ];
            });

        $topDistricts = ChargingStation::select('district_id', DB::raw('count(*) as total'))
            ->groupBy('district_id')
            ->with('district:id,name')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        $recentReports = Report::with(['station:id,name'])
            ->latest()
            ->take(6)
            ->get();

        $recentStations = ChargingStation::with(['district:id,name'])
            ->latest()
            ->take(6)
            ->get();

        $stationChart = [
            'labels' => ['พร้อมใช้งาน', 'ชำรุด', 'รอตรวจสอบ'],
            'data' => [
                $stationCounts['active'],
                $stationCounts['broken'],
                $stationCounts['pending'],
            ],
        ];

        $reportChart = [
            'labels' => $reportTypeSummary->pluck('label')->values(),
            'data' => $reportTypeSummary->pluck('total')->values(),
        ];

        return view('admin.dashboard', compact(
            'stats',
            'reportTypeSummary',
            'topDistricts',
            'recentReports',
            'recentStations',
            'stationChart',
            'reportChart'
        ));
    }

    // ตารางสถานี + ค้นหา
    public function stations(Request $request)
    {
        $request->validate([
            'q' => ['nullable','string','max:255'],
            'status_id' => ['nullable','integer'],
            'district_id' => ['nullable','integer'],
        ]);

        $query = ChargingStation::with(['status','district'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where('name','like',"%{$q}%");
        }
        if ($request->filled('status_id') || $request->status_id === '0') {
            $query->where('status_id', $request->status_id);
        }
        if ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        $stations = $query->paginate(15)->withQueryString();

        return view('admin.stations', [
            'stations' => $stations,
            'statuses' => StationStatus::orderBy('id')->get(['id','name']),
            'districts' => District::orderBy('name')->get(['id','name']),
            'filters' => $request->only(['q','status_id','district_id']),
        ]);
    }

    // ตารางผู้ใช้ + ค้นหาเบา ๆ
    public function users(Request $request)
    {
        $request->validate([
            'q' => ['nullable','string','max:255'],
            'role_id' => ['nullable','integer'],
        ]);

        $query = User::query()->orderBy('created_at','desc');

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function($sub) use ($q) {
                $sub->where('name','like',"%{$q}%")
                    ->orWhere('email','like',"%{$q}%");
            });
        }
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        $users = $query->paginate(15)->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'filters' => $request->only(['q','role_id']),
        ]);
    }
}
