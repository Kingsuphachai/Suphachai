<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            รายงาน #{{ $report->id }} — {{ $report->status_text }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-3">
                <div><strong>วันที่แจ้ง:</strong> {{ $report->created_at }}</div>
                <div><strong>ผู้แจ้ง:</strong> {{ $report->user->name ?? '-' }} ({{ $report->user->email ?? '-' }})</div>
                <div><strong>สถานี:</strong> {{ $report->station->name ?? '-' }}</div>
                <div><strong>ประเภทปัญหา:</strong> {{ $report->type ?? '-' }}</div>
                <div><strong>รายละเอียด:</strong><br>{{ $report->message ?? '-' }}</div>
                <div><strong>สถานะ:</strong> {{ $report->status_text }}</div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6 flex gap-2">
                @if((int)$report->status === 0)
                    <form method="POST" action="{{ route('admin.reports.resolve', $report) }}">
                        @csrf
                        <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                                onclick="return confirm('ยืนยันปิดงานรายงานนี้?')">
                            ✅ ปิดงาน
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.reports.reject', $report) }}">
                        @csrf
                        <button class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                                onclick="return confirm('ยืนยันปฏิเสธรายงานนี้?')">
                            ❌ ปฏิเสธ
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.reports.destroy', $report) }}" class="ml-auto">
                    @csrf @method('DELETE')
                    <button class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                            onclick="return confirm('ลบรายงานนี้ถาวร?')">
                        🗑️ ลบรายงาน
                    </button>
                </form>

                <a href="{{ route('admin.reports.index') }}" class="px-4 py-2 border rounded">
                    ← กลับไปยังรายการ
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
