<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">สถานีรอตรวจสอบ</h2></x-slot>

    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">{{ session('success') }}</div>
        @endif

        <div class="bg-white shadow sm:rounded-lg p-6">
            <table class="w-full border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 border">ชื่อสถานี</th>
                        <th class="p-2 border">ผู้ขอ</th>
                        <th class="p-2 border">ที่อยู่</th>
                        <th class="p-2 border">พิกัด</th>
                        <th class="p-2 border">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stations as $s)
                        <tr>
                            <td class="p-2 border">{{ $s->name }}</td>
                            <td class="p-2 border">{{ $s->creator->name ?? '-' }}</td>
                            <td class="p-2 border">{{ $s->address ?? '-' }}</td>
                            <td class="p-2 border">
                                {{ $s->latitude ?? '-' }}, {{ $s->longitude ?? '-' }}
                            </td>
                            <td class="p-2 border">
                                <a href="{{ route('admin.stations.edit', $s->id) }}" class="text-blue-600 hover:underline">ตรวจสอบ/แก้ไข</a>

                                <form method="POST" action="{{ route('admin.stations.approve', $s->id) }}" class="inline ml-2">
                                    @csrf
                                    <button class="text-green-700 hover:underline">อนุมัติ</button>
                                </form>

                                <form method="POST" action="{{ route('admin.stations.reject', $s->id) }}" class="inline ml-2"
                                      onsubmit="return confirm('ปฏิเสธคำขอนี้ใช่ไหม?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-700 hover:underline">ปฏิเสธ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center p-3">ไม่มีรายการรอตรวจสอบ</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-4">{{ $stations->links() }}</div>
        </div>
    </div>
</x-app-layout>
