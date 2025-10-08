{{-- resources/views/admin/stations/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            แก้ไขสถานีชาร์จ #{{ $station->id }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                {{-- แจ้ง Error รวม --}}
                @if ($errors->any())
                    <div class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-red-700">
                        <div class="font-semibold mb-1">กรุณาตรวจสอบข้อมูลที่กรอก:</div>
                        <ul class="list-disc list-inside text-sm">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif>

                <form method="POST" action="{{ route('admin.stations.update', $station) }}">
                    @csrf
                    @method('PUT')

                    {{-- ชื่อสถานี --}}
                    <div class="mb-4">
                        <label class="block font-medium">ชื่อสถานี <span class="text-red-600">*</span></label>
                        <input type="text" name="name"
                               value="{{ old('name', $station->name) }}"
                               class="w-full border rounded p-2" required>
                        @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- ที่อยู่ --}}
                    <div class="mb-4">
                        <label class="block font-medium">ที่อยู่</label>
                        <textarea name="address" class="w-full border rounded p-2" rows="3">{{ old('address', $station->address) }}</textarea>
                        @error('address') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- อำเภอ --}}
                    <div class="mb-4">
                        <label class="block font-medium">อำเภอ <span class="text-red-600">*</span></label>
                        <select name="district_id" class="w-full border rounded p-2" required>
                            <option value="">-- เลือกอำเภอ --</option>
                            @foreach($districts as $d)
                                <option value="{{ $d->id }}"
                                    {{ (string)old('district_id', $station->district_id) === (string)$d->id ? 'selected' : '' }}>
                                    {{ $d->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('district_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- ตำบล --}}
                    <div class="mb-4">
                        <label class="block font-medium">ตำบล</label>
                        <select name="subdistrict_id" class="w-full border rounded p-2">
                            <option value="">-- เลือกตำบล --</option>
                            @foreach($subdistricts as $s)
                                <option value="{{ $s->id }}"
                                    {{ (string)old('subdistrict_id', $station->subdistrict_id) === (string)$s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subdistrict_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- สถานะ --}}
                    <div class="mb-4">
                        <label class="block font-medium">สถานะ <span class="text-red-600">*</span></label>
                        <select name="status_id" class="w-full border rounded p-2" required>
                            @foreach($statuses as $st)
                                <option value="{{ $st->id }}"
                                    {{ (string)old('status_id', $station->status_id) === (string)$st->id ? 'selected' : '' }}>
                                    {{ $st->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('status_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- เวลาทำการ --}}
                    <div class="mb-4">
                        <label class="block font-medium">เวลาทำการ</label>
                        <input type="text" name="operating_hours"
                               value="{{ old('operating_hours', $station->operating_hours) }}"
                               class="w-full border rounded p-2">
                        @error('operating_hours') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- พิกัด --}}
                    <div class="mb-4">
                        <label class="block font-medium">พิกัด (Lat, Lng)</label>
                        <div class="flex gap-2">
                            <input type="text" name="latitude"
                                   value="{{ old('latitude', $station->latitude) }}"
                                   placeholder="Latitude" class="w-1/2 border rounded p-2">
                            <input type="text" name="longitude"
                                   value="{{ old('longitude', $station->longitude) }}"
                                   placeholder="Longitude" class="w-1/2 border rounded p-2">
                        </div>
                        @error('latitude') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        @error('longitude') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- ประเภทหัวชาร์จ (many-to-many) --}}
                    <div class="mb-6">
                        <label class="block font-medium">ประเภทหัวชาร์จ</label>
                        <div class="flex flex-wrap gap-4">
                            @php
                                $checked = old('charger_type_ids', $selectedChargers ?? []);
                            @endphp
                            @foreach($chargers as $c)
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="charger_type_ids[]"
                                           value="{{ $c->id }}"
                                           {{ in_array($c->id, $checked) ? 'checked' : '' }}>
                                    <span>{{ $c->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('charger_type_ids') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            อัปเดต
                        </button>
                        <a href="{{ route('admin.stations.index') }}"
                           class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                            ยกเลิก
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- แสดง alert หลังอัปเดต (redirect มาหน้า index แล้วแจ้งอยู่แล้ว) --}}
    @if (session('success'))
        <script> alert(@json(session('success'))); </script>
    @endif
</x-app-layout>
