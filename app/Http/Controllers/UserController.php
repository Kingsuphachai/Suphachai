<?php

namespace App\Http\Controllers;

use App\Models\ChargerType;
use App\Models\ChargingStation;
use App\Models\District;
use App\Models\Subdistrict;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function dashboard()
    {
        $districts = District::orderBy('name')->get(['id', 'name']);
        $subdistricts = Subdistrict::orderBy('name')->get(['id', 'name', 'district_id']);
        $chargerTypes = ChargerType::orderBy('name')->get(['id', 'name']);
        $stations = ChargingStation::where('status_id', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'status_id']);
        $reportTypes = [
            'no_power' => 'ไม่มีไฟ / ใช้งานไม่ได้',
            'occupied' => 'มีรถจอดขวาง/ไม่ว่าง',
            'broken'   => 'เครื่องชำรุด',
            'other'    => 'อื่น ๆ',
        ];

        return view('user.dashboard', compact(
            'districts',
            'subdistricts',
            'chargerTypes',
            'stations',
            'reportTypes'
        ));
    }
}
