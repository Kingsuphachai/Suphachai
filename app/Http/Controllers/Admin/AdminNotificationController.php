<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

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
public function redirect($id)
{
    // หาแจ้งเตือน
    $n = DatabaseNotification::find($id);
    if (!$n) {
        return redirect()->route('admin.notifications.index')
            ->with('error', 'ไม่พบการแจ้งเตือนนี้แล้ว');
    }

    // ดึง URL และ normalize กัน backslash/รูปแบบเพี้ยน
    $url = (string) data_get($n->data, 'url', '/admin/dashboard');
    $url = str_replace('\\', '/', $url);
    $url = preg_replace('#^https?:///+#', 'http://', $url);

    // ลบทันทีที่กด
    $n->delete();

    // ยิงออกนอกโดเมนใช้ away, ในระบบใช้ to
    return Str::startsWith($url, ['http://', 'https://'])
        ? redirect()->away($url)
        : redirect()->to($url);
}

}
