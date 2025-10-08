<?php

namespace App\Http\Controllers;

use App\Models\ChargingStation;
use App\Models\District;
use App\Models\Subdistrict;
use App\Models\ChargerType;
use App\Models\StationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChargingStationController extends Controller
{
    public function navigate($id)
{
    $station = \App\Models\ChargingStation::with('status')->findOrFail($id);

    abort_unless($station->latitude && $station->longitude, 404, 'No coordinates');

    return view('stations.navigate', compact('station'));
}

    public function apiList()
{
    // ดึงเฉพาะสถานีที่มีพิกัด และสถานะที่ให้แสดง (is_visible = 1)
    $stations = \App\Models\ChargingStation::query()
        ->with(['status'])
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->whereHas('status', fn($q) => $q->where('is_visible', 1))
        ->get(['id','name','address','latitude','longitude','status_id']);

    return $stations->map(fn($s) => [
        'id'      => $s->id,
        'name'    => $s->name,
        'address' => $s->address,
        'lat'     => (float) $s->latitude,
        'lng'     => (float) $s->longitude,
        'status'  => $s->status?->name ?? '-',
    ]);
}

        /** หน้าแผนที่รวม */
    public function map()
    {
        return view('stations.map');
    }

    /** API ส่งพิกัดสถานีทั้งหมด (เฉพาะที่มี lat/lng) */
    public function apiStations(): JsonResponse
    {
        $stations = ChargingStation::query()
            ->with(['status:id,name', 'district:id,name,province_name,zipcode', 'subdistrict:id,name,district_id', 'chargers:id,name',])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get([
                'id','name','address','latitude','longitude',
            'status_id','district_id','subdistrict_id',
            'image','operating_hours',
            ])
            ->map(function ($s) {
                            return [
                'id'              => $s->id,
                'name'            => $s->name,
                'address'         => $s->address,
                'lat'             => (float) $s->latitude,
                'lng'             => (float) $s->longitude,

                'status'          => $s->status->name ?? '-',

                // 👉 ที่อยู่ย่อยครบ
                'subdistrict'     => $s->subdistrict->name ?? null,           // ตำบล
                'district'        => $s->district->name ?? null,              // อำเภอ
                'province'        => $s->district->province_name ?? null,     // จังหวัด
                'postcode'        => $s->district->zipcode ?? null,           // รหัสไปรษณีย์

                'operating_hours' => $s->operating_hours,                     // เวลาทำการ
                'image_url'       => $s->image_url
                    ?? ($s->image ? asset('storage/'.$s->image) : null),
                'chargers'        => $s->chargers?->pluck('name')->values() ?? [],
            ];
            });

        return response()->json($stations);
    }
    /**
     * แสดงรายการสถานี + ค้นหา/กรอง
     */
    public function index(Request $request)
    {
        // validate พารามิเตอร์ค้นหาแบบเบา ๆ
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],          // ชื่อสถานี
            'district_id' => ['nullable', 'integer'],
            'subdistrict_id' => ['nullable', 'integer'],
            'status_id' => ['nullable', 'integer'],
            'charger_type_id' => ['nullable', 'integer'],
        ]);

        $query = ChargingStation::query()
            ->with(['status', 'district', 'subdistrict', 'chargers']);

        // ค้นหาตามชื่อสถานี
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where('name', 'like', "%{$q}%");
        }

        // กรองตามอำเภอ/ตำบล/สถานะ/ประเภทหัวชาร์จ
        if ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        if ($request->filled('subdistrict_id')) {
            $query->where('subdistrict_id', $request->subdistrict_id);
        }

        if ($request->filled('status_id') || $request->status_id === '0') {
            // รองรับค่า 0 (รอตรวจสอบ) ด้วย
            $query->where('status_id', $request->status_id);
        }

        if ($request->filled('charger_type_id')) {
            // relation ใน model ชื่อ "chargers"
            $query->whereHas('chargers', function ($q) use ($request) {
                $q->where('charger_types.id', $request->charger_type_id);
            });
        }

        $stations = $query->orderBy('name')->paginate(15)->withQueryString();

        // ถ้าอยากเอาไปใช้กับหน้าแผนที่แบบ Ajax/JSON ก็รองรับด้วย
        if ($request->wantsJson()) {
            return response()->json($stations);
        }

        // dropdown data สำหรับฟิลเตอร์ใน view
        $districts = District::orderBy('name')->get(['id','name']);
        $subdistricts = Subdistrict::orderBy('name')->get(['id','name','district_id']);
        $chargerTypes = ChargerType::orderBy('name')->get(['id','name']);
        $statuses = StationStatus::orderBy('id')->get(['id','name','is_visible']);

        return view('stations.index', compact(
            'stations', 'districts', 'subdistricts', 'chargerTypes', 'statuses'
        ));
    }

    /**
     * แสดงรายละเอียดสถานีเดียว
     */
    public function show($id, Request $request)
    {
        $station = ChargingStation::with(['status', 'district', 'subdistrict', 'chargers', 'creator'])
            ->findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json($station);
        }

        return view('stations.show', compact('station'));
    }
}
