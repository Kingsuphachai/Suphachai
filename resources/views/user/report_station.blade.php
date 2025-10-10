<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">แจ้งปัญหาสถานีชาร์จ</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">

                <form method="POST" action="{{ route('user.reports.store') }}" class="space-y-4">
                    @csrf

                    {{-- เลือกสถานี --}}
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">เลือกสถานี</label>
                        <select name="station_id" class="w-full border rounded px-3 py-2" required>
                            <option value="">-- เลือกสถานี --</option>
                            @foreach($stations as $st)
                                <option value="{{ $st->id }}" @selected(old('station_id', $prefillStation) == $st->id)>
                                    {{ $st->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('station_id') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                    </div>

                    {{-- ประเภทปัญหา --}}
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ประเภทปัญหา</label>
                        <select name="type" class="w-full border rounded px-3 py-2" required>
                            <option value="">-- เลือกประเภท --</option>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                    </div>

                    {{-- รายละเอียด --}}
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">รายละเอียด</label>
                        <textarea name="message" rows="5" class="w-full border rounded px-3 py-2" required
                            placeholder="อธิบายปัญหาที่พบ เช่น หัวชาร์จไม่ล็อก สแกนแล้วขึ้น error รอคิวเกิน 30 นาที ฯลฯ">{{ old('message') }}</textarea>
                        @error('message') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-2">
                        <button class="px-4 py-2 bg-blue-600 border rounded hover:bg-blue-700">ส่งรายงาน</button>
                        <a href="{{ url()->previous() }}" class="px-4 py-2 border rounded">ยกเลิก</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>