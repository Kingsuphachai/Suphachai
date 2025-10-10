<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
public function index(Request $request)
{
    $notifications = $request->user()
        ->notifications()       // ✅ Laravel built-in
        ->latest()
        ->paginate(20);

    return view('admin.notifications.index', compact('notifications'));
}

public function readAll(Request $request)
{
    $request->user()->unreadNotifications->markAsRead();
    return back()->with('success', 'ทำเครื่องหมายว่าอ่านแล้วทั้งหมด');
}

}
