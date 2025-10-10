<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">แจ้งปัญหาสถานีชาร์จ</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">

                @if ($errors->any())
                    <div class="mb-4 p-3 rounded bg-red-50 text-red-700">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('user.reports.store') }}" class="space-y-4">
                    @csrf

                    {{-- เลือกสถานี --}}
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">สถานีที่มีปัญหา</label>
                        <select name="station_id" class="w-full border rounded px-3 py-2" required>
                            <option value="">-- เลือกสถานี --</option>
                            @foreach ($stations as $s)
                                <option value="{{ $s->id }}" @selected(old('station_id') == $s->id)>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ประเภทปัญหา --}}
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">ประเภทปัญหา</label>
                        <select name="type" class="w-full border rounded px-3 py-2" required>
                            <option value="">-- เลือกประเภท --</option>
                            @foreach ($types as $k => $label)
                                <option value="{{ $k }}" @selected(old('type') == $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- รายละเอียดเพิ่มเติม --}}
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">รายละเอียด</label>
                        <textarea name="message" rows="5" class="w-full border rounded px-3 py-2" required
                                  placeholder="อธิบายอาการที่พบ เวลาเกิดเหตุ ฯลฯ">{{ old('message') }}</textarea>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 border rounded hover:bg-blue-700">
                            ส่งรายงาน
                        </button>
                        <a href="{{ route('user.dashboard') }}" class="px-4 py-2 border rounded">ยกเลิก</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
