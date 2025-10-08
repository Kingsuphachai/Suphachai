<?php
// app/Http/Controllers/Admin/UserController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q'       => ['nullable','string','max:255'],
            'role_id' => ['nullable','integer'],
            'status'  => ['nullable','in:active,inactive,all'],
        ]);

        $status = $request->input('status','all');

        // base query + withTrashed() เฉพาะกรณีอยากดู inactive
        $q = User::query()->with('role');
        if ($status === 'inactive') $q->onlyTrashed();
        elseif ($status === 'active') $q->whereNull('deleted_at');

        if ($request->filled('q')) {
            $kw = $request->q;
            $q->where(function($qq) use ($kw){
                $qq->where('name','like',"%$kw%")
                   ->orWhere('email','like',"%$kw%");
            });
        }

        if ($request->filled('role_id')) {
            $q->where('role_id', $request->role_id);
        }

        $users = $q->orderBy('id','desc')->paginate(15)->withQueryString();
        $roles = Role::orderBy('id')->get(['id','name']);

        return view('admin.users.index', compact('users','roles'));
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('id')->get(['id','name']);
        return view('admin.users.edit', compact('user','roles'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'role_id'  => ['required', Rule::exists('roles','id')],
            'password' => ['nullable','string','min:8'], // กรอกเมื่ออยากเปลี่ยน
        ]);

        if (empty($data['password'])) unset($data['password']); // ไม่เปลี่ยน password ถ้าไม่ได้กรอก

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success','อัพเดตข้อมูลผู้ใช้เรียบร้อย');
    }

    // ปิดการใช้งาน (soft delete)
    public function destroy(User $user)
    {
        // ป้องกันลบตัวเอง
        if (auth()->id() === $user->id) {
            return back()->with('error','ไม่สามารถปิดการใช้งานบัญชีของคุณเองได้');
        }

        $user->delete();
        return back()->with('success','ปิดการใช้งานผู้ใช้เรียบร้อย');
    }

    // กู้คืนผู้ใช้
    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        return back()->with('success','กู้คืนผู้ใช้เรียบร้อย');
    }
}
