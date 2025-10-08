<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">รายงานปัญหาสถานีชาร์จ</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- ฟิลเตอร์ --}}
            <form method="GET" class="mb-4 flex gap-2 items-center">
                <select name="status" class="border rounded p-2">
                    <option value="all" {{ request('status','pending')=='all'?'selected':'' }}>ทั้งหมด</option>
                    <option value="pending" {{ request('status','pending')=='pending'?'selected':'' }}>รอตรวจสอบ</option>
                    <option value="resolved" {{ request('status')=='resolved'?'selected':'' }}>ปิดงานแล้ว</option>
                    <option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>ปฏิเสธ</option>
                </select>

                <input type="text" name="q" value="{{ request('q') }}" placeholder="ค้นหา (ประเภท/ข้อความ)" class="border rounded p-2 w-64">
                <button class="px-4 py-2 border rounded">ค้นหา</button>
            </form>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <table class="w-full border">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 border">วันที่</th>
                            <th class="p-2 border">ผู้แจ้ง</th>
                            <th class="p-2 border">สถานี</th>
                            <th class="p-2 border">ประเภท</th>
                            <th class="p-2 border">สถานะ</th>
                            <th class="p-2 border">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $r)
                            <tr>
                                <td class="p-2 border">{{ $r->created_at }}</td>
                                <td class="p-2 border">{{ $r->user->name ?? '-' }}</td>
                                <td class="p-2 border">{{ $r->station->name ?? '-' }}</td>
                                <td class="p-2 border">{{ $r->type ?? '-' }}</td>
                                <td class="p-2 border">
                                    @php
                                        $badge = match((int)$r->status){
                                            1 => 'bg-green-100 text-green-800',
                                            2 => 'bg-red-100 text-red-800',
                                            default => 'bg-yellow-100 text-yellow-800',
                                        };
                                    @endphp
                                    <span class="px-2 py-1 rounded text-sm {{ $badge }}">
                                        {{ $r->status_text }}
                                    </span>
                                </td>
                                <td class="p-2 border">
                                    <a href="{{ route('admin.reports.show', $r) }}" class="text-blue-600 hover:underline">
                                        ดูรายละเอียด
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-3 text-center text-gray-500">ไม่มีรายงาน</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $reports->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
