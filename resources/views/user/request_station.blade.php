<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">ขอเพิ่มสถานีชาร์จ</h2></x-slot>

    <div class="py-6 max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
            <form method="POST" action="{{ route('user.request.store') }}">
                @csrf

                <div>
                    <label class="block font-medium">ชื่อสถานี *</label>
                    <input name="name" value="{{ old('name') }}" class="w-full border rounded p-2" required>
                    @error('name')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block font-medium">ที่อยู่</label>
                    <textarea name="address" class="w-full border rounded p-2">{{ old('address') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium">อำเภอ *</label>
                        <select name="district_id" class="w-full border rounded p-2" required>
                            <option value="">-- เลือก --</option>
                            @foreach($districts as $d)
                                <option value="{{ $d->id }}" @selected(old('district_id')==$d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        @error('district_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block font-medium">ตำบล</label>
                        <select name="subdistrict_id" class="w-full border rounded p-2">
                            <option value="">-- เลือก --</option>
                            @foreach($subdistricts as $s)
                                <option value="{{ $s->id }}" @selected(old('subdistrict_id')==$s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium">Latitude</label>
                        <input name="latitude" value="{{ old('latitude') }}" class="w-full border rounded p-2">
                    </div>
                    <div>
                        <label class="block font-medium">Longitude</label>
                        <input name="longitude" value="{{ old('longitude') }}" class="w-full border rounded p-2">
                    </div>
                </div>

                <div>
                    <label class="block font-medium">เวลาทำการ</label>
                    <input name="operating_hours" value="{{ old('operating_hours') }}" class="w-full border rounded p-2">
                </div>

                <div class="pt-2">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ส่งคำขอ</button>
                    <a href="{{ route('user.dashboard') }}" class="ml-2 px-4 py-2 border rounded">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
