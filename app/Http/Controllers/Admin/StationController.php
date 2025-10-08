<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChargingStation;
use App\Models\District;
use App\Models\Subdistrict;
use App\Models\ChargerType;
use App\Models\StationStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;


class StationController extends Controller
{
    public function pending()
    {
        $stations = \App\Models\ChargingStation::where('status_id', 0)
            ->with(['district','subdistrict','creator'])
            ->orderBy('created_at','desc')
            ->paginate(20);

        return view('admin.stations.pending', compact('stations'));
    }

    public function approve($id)
    {
        $station = \App\Models\ChargingStation::findOrFail($id);
        $station->update(['status_id' => 1]);

        return back()->with('success','อนุมัติสถานีเรียบร้อย');
    }

    public function reject($id)
    {
        $station = \App\Models\ChargingStation::findOrFail($id);
        $station->delete();

        return back()->with('success','ปฏิเสธและลบสถานีเรียบร้อย');
    }

    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable','string','max:255'],
            'district_id' => ['nullable','integer'],
            'status_id' => ['nullable','integer'],
        ]);

        $q = ChargingStation::query()
            ->with(['status','district','subdistrict','chargers']);

        if ($request->filled('q')) {
            $kw = trim($request->q);

            $q->where(function($sub) use ($kw) {
                $sub->where('name','like',"%{$kw}%")
                    ->orWhereHas('district', function($d) use ($kw) {
                        $d->where('name','like',"%{$kw}%");
                    });
            });
        }
        if ($request->filled('district_id')) {
            $q->where('district_id', $request->district_id);
        }
        if ($request->filled('status_id') || $request->status_id === '0') {
            $q->where('status_id', $request->status_id);
        }

        $stations = $q->orderBy('id','desc')->paginate(15)->withQueryString();

        return view('admin.stations.index', [
            'stations' => $stations,
            'districts' => District::orderBy('name')->get(['id','name']),
            'statuses'  => StationStatus::orderBy('id')->get(['id','name']),
            'filters'   => $request->only('q','district_id','status_id'),
        ]);
    }

    public function create()
    {
        return view('admin.stations.create', [
            'districts'    => District::orderBy('name')->get(['id','name']),
            'subdistricts' => Subdistrict::orderBy('name')->get(['id','name','district_id']),
            'statuses'     => StationStatus::orderBy('id')->get(['id','name']),
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
            'status_id'       => ['required','integer', Rule::exists('station_statuses','id')],
            'latitude'        => ['nullable','numeric'],
            'longitude'       => ['nullable','numeric'],
            'operating_hours' => ['nullable','string','max:100'],
            'charger_type_ids'=> ['array'],
            'charger_type_ids.*' => [Rule::exists('charger_types','id')],
            'image'           => ['nullable','image','mimes:jpeg,jpg,png,webp','max:4096'], // 4MB
        ]);

        // อัปโหลดรูป (ถ้ามี)
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('stations', 'public'); // storage/app/public/stations
        }

        $station = ChargingStation::create([
            'name'            => $data['name'],
            'address'         => $data['address'] ?? null,
            'district_id'     => $data['district_id'],
            'subdistrict_id'  => $data['subdistrict_id'] ?? null,
            'status_id'       => $data['status_id'],
            'latitude'        => $data['latitude'] ?? null,
            'longitude'       => $data['longitude'] ?? null,
            'operating_hours' => $data['operating_hours'] ?? null,
            'created_by'      => auth()->id(),
            'image'           => $imagePath, // ⬅️ เก็บ path ลงคอลัมน์ image
        ]);

        if (!empty($data['charger_type_ids'])) {
            $station->chargers()->sync($data['charger_type_ids']);
        }

return redirect()->route('admin.stations.index')->with('success','เพิ่มสถานีเรียบร้อย');

    }

    public function edit(ChargingStation $station)
    {
        $station->load('chargers');

        return view('admin.stations.edit', [
            'station'         => $station,
            'districts'       => District::orderBy('name')->get(['id','name']),
            'subdistricts'    => Subdistrict::orderBy('name')->get(['id','name','district_id']),
            'statuses'        => StationStatus::orderBy('id')->get(['id','name']),
            'chargers'        => ChargerType::orderBy('name')->get(['id','name']),
            'selectedChargers'=> $station->chargers->pluck('id')->all(),
        ]);
    }

public function update(Request $request, ChargingStation $station)
{
    $data = $request->validate([
        'name'             => ['required','string','max:255'],
        'address'          => ['nullable','string'],
        'district_id'      => ['required','integer', Rule::exists('districts','id')],
        'subdistrict_id'   => ['nullable','integer', Rule::exists('subdistricts','id')],
        'status_id'        => ['required','integer', Rule::exists('station_statuses','id')],
        'latitude'         => ['nullable','numeric'],
        'longitude'        => ['nullable','numeric'],
        'operating_hours'  => ['nullable','string','max:100'],
        'charger_type_ids' => ['array'],
        'charger_type_ids.*' => [Rule::exists('charger_types','id')],
        'image'            => ['nullable','image','mimes:jpeg,jpg,png,webp','max:4096'],
        'remove_image'     => ['nullable','boolean'], // 👈 เพิ่ม
    ]);

    // ลบรูปปัจจุบันถ้าเช็กบ็อกซ์
    if (!empty($data['remove_image']) && $data['remove_image']) {
        if ($station->image && Storage::disk('public')->exists($station->image)) {
            Storage::disk('public')->delete($station->image);
        }
        $station->image = null;
    }

    // ถ้ามีอัปโหลดรูปใหม่ ให้แทนที่ (จะทับผล "ลบรูป" ด้วยรูปใหม่)
    if ($request->hasFile('image')) {
        if ($station->image && Storage::disk('public')->exists($station->image)) {
            Storage::disk('public')->delete($station->image);
        }
        $station->image = $request->file('image')->store('stations','public');
    }

    // อัปเดตฟิลด์อื่น
    $station->fill([
        'name'            => $data['name'],
        'address'         => $data['address'] ?? null,
        'district_id'     => $data['district_id'],
        'subdistrict_id'  => $data['subdistrict_id'] ?? null,
        'status_id'       => $data['status_id'],
        'latitude'        => $data['latitude'] ?? null,
        'longitude'       => $data['longitude'] ?? null,
        'operating_hours' => $data['operating_hours'] ?? null,
    ])->save();

    $station->chargers()->sync($data['charger_type_ids'] ?? []);

    return redirect()->route('admin.stations.index')->with('success','อัปเดตสถานีเรียบร้อย');

}


    public function destroy(ChargingStation $station)
    {
        // ลบรูปหลักออกจาก storage ด้วย (ถ้ามี)
        if ($station->image && Storage::disk('public')->exists($station->image)) {
            Storage::disk('public')->delete($station->image);
        }

        $station->chargers()->detach();
        $station->delete();

        return back()->with('success','ลบสถานีเรียบร้อย');
    }
        /** ลบเฉพาะรูปหลัก (คงสถานี) */
    public function destroyImage(ChargingStation $station)
    {
        if ($station->image && Storage::disk('public')->exists($station->image)) {
            Storage::disk('public')->delete($station->image);
        }
        $station->update(['image' => null]);

        return back()->with('success','ลบรูปสถานีเรียบร้อย');
    }
}
