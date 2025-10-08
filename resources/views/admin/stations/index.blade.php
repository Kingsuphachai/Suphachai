<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">จัดการสถานีชาร์จ</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <div class="mb-4">
                    {{-- แถบเครื่องมือ: ฟิลเตอร์ (ซ้าย) + ปุ่มเพิ่ม (ขวา) --}}
                    <div class="bg-white p-4 rounded shadow">
                        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">

                            {{-- ฟอร์มฟิลเตอร์ (ซ้าย) --}}
                            <form method="GET" class="flex flex-wrap gap-3 items-end">
                                <div>
                                    <label class="block text-sm text-gray-600">ค้นหาชื่อสถานี</label>
                                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                                        class="border rounded px-3 py-2" placeholder="เช่น ปตท., กฟภ.">
                                </div>

                                <div>
                                    <label class="block text-sm text-gray-600">สถานะ</label>
                                    <select name="status_id" class="border rounded px-3 py-2">
                                        <option value="">ทั้งหมด</option>
                                        @foreach ($statuses as $s)
                                            <option value="{{ $s->id }}" @selected(($filters['status_id'] ?? '') !== '' && (int) ($filters['status_id']) === $s->id)>
                                                {{ $s->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm text-gray-600">อำเภอ</label>
                                    <select name="district_id" class="border rounded px-3 py-2">
                                        <option value="">ทั้งหมด</option>
                                        @foreach ($districts as $d)
                                            <option value="{{ $d->id }}" @selected(($filters['district_id'] ?? '') !== '' && (int) ($filters['district_id']) === $d->id)>
                                                {{ $d->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex gap-2">
                                    <button class="px-4 py-2 bg-indigo-600 text-white rounded">ค้นหา</button>
                                    @if(($filters['q'] ?? '') !== '' || ($filters['status_id'] ?? '') !== '' || ($filters['district_id'] ?? '') !== '')
                                        <a href="{{ route('admin.stations.index') }}"
                                            class="px-4 py-2 border rounded">ล้างตัวกรอง</a>
                                    @endif
                                </div>
                            </form>

                            {{-- ปุ่มเพิ่มสถานี (ขวา) --}}
                            <div class="md:self-end">
                                <a href="{{ route('admin.stations.create') }}"
                                    class="inline-block px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-900">
                                    + เพิ่มสถานี
                                </a>
                            </div>

                        </div>
                    </div>

                </div>

                <table class="w-full border">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 border">ชื่อสถานี</th>
                            <th class="p-2 border">ที่อยู่</th>
                            <th class="p-2 border">สถานะ</th>
                            <th class="p-2 border">อำเภอ</th>
                            <th class="p-2 border">ตำบล</th>
                            <th class="p-2 border">รูปสถานี</th>
                            <th class="p-2 border">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stations as $station)
                            <tr>
                                <td class="p-2 border">{{ $station->name }}</td>
                                <td class="p-2 border">{{ $station->address }}</td>
                                <td class="p-2 border">{{ $station->status->name ?? '-' }}</td>
                                <td class="p-2 border">{{ $station->district->name ?? '-' }}</td>
                                <td class="p-2 border">{{ $station->subdistrict->name ?? '-' }}</td>
                                <td class="p-2 border text-center">
                                    <div class="flex items-center justify-center">
                                        @if ($station->image)
                                            <a href="{{ $station->image_url }}" target="_blank">
                                                <img src="{{ $station->image_url }}" alt="รูปสถานี {{ $station->name }}"
                                                    class="h-16 w-24 object-cover rounded border hover:scale-110 transition-transform duration-200">
                                            </a>
                                        @else
                                            <span class="text-gray-400">ไม่มีรูป</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-2 border">
                                    {{-- แก้ไข --}}
                                    <a href="{{ route('admin.stations.edit', $station) }}"
                                        class="text-blue-600 hover:underline">
                                        แก้ไข
                                    </a>

                                    <span class="mx-2 text-gray-300">|</span>

                                    {{-- ลบ (ต้องใช้ฟอร์ม + method DELETE) --}}
                                    <form action="{{ route('admin.stations.destroy', $station) }}" method="POST"
                                        class="inline" onsubmit="return confirm('ยืนยันลบสถานีนี้?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">
                                            ลบ
                                        </button>
                                    </form>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center p-3">ไม่มีข้อมูล</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
@if (session('success'))
    <script>
        alert(@json(session('success')));
    </script>
@endif