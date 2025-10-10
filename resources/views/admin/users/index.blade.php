{{-- resources/views/admin/users/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">จัดการสมาชิก</h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow sm:rounded-lg p-6">
            @if(session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-3 bg-red-100 text-red-600 rounded">{{ session('error') }}</div>
            @endif

            {{-- ฟิลเตอร์ --}}
            <form method="GET" class="mb-6 flex flex-wrap items-end gap-4">
                <!-- ค้นหา -->
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-700 mb-1">ค้นหา</label>
                    <input type="text" name="q" value="{{ request('q') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 w-48 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="ชื่อ/อีเมล">
                </div>

                <!-- บทบาท -->
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-700 mb-1">บทบาท</label>
                    <select name="role_id"
                        class="border border-gray-300 rounded-lg px-3 py-2 w-40 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">ทั้งหมด</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}" {{ request('role_id') == $r->id ? 'selected' : '' }}>
                                {{ $r->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- สถานะ -->
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                    <select name="status"
                        class="border border-gray-300 rounded-lg px-3 py-2 w-40 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งาน</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ปิดการใช้งาน
                        </option>
                    </select>
                </div>

                <!-- ปุ่มกรอง -->
                <div class="flex flex-col">
                    <label class="text-sm font-medium text-gray-700 mb-1 invisible">.</label>
                    <button
                        class="px-5 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 focus:ring-2 focus:ring-gray-400">
                        ค้นหา
                    </button>
                </div>
            </form>


            <table class="w-full border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-2 border">#</th>
                        <th class="p-2 border">ชื่อ</th>
                        <th class="p-2 border">อีเมล</th>
                        <th class="p-2 border">บทบาท</th>
                        <th class="p-2 border">สถานะ</th>
                        <th class="p-2 border">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td class="p-2 border">{{ $u->id }}</td>
                            <td class="p-2 border">{{ $u->name }}</td>
                            <td class="p-2 border">{{ $u->email }}</td>
                            <td class="p-2 border">{{ $u->role->name ?? '-' }}</td>
                            <td class="p-2 border">
                                @if($u->deleted_at)
                                    <span class="text-red-600">ปิดการใช้งาน</span>
                                @else
                                    <span class="text-green-700">ใช้งาน</span>
                                @endif
                            </td>
                            <td class="p-2 border">
                                <a href="{{ route('admin.users.edit', $u) }}" class="text-blue-600 hover:underline">แก้ไข</a>

                                <span class="mx-2 text-gray-300">|</span>

                                @if($u->deleted_at)
                                    <form action="{{ route('admin.users.restore', $u->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button class="text-green-700 hover:underline">กู้คืน</button>
                                    </form>
                                @else
                                    @if(auth()->id() !== $u->id)
                                        <form action="{{ route('admin.users.destroy', $u) }}" method="POST" class="inline"
                                            onsubmit="return confirm('ยืนยันปิดการใช้งานผู้ใช้นี้?');">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline">ปิดการใช้งาน</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center p-3">ไม่พบข้อมูล</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>