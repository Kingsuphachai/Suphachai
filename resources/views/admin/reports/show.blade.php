<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800">รายละเอียดรายงาน #{{ $report->id }}</h2>
  </x-slot>

  <div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
      @if(session('success'))
        <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
      @endif

      <div class="bg-white p-6 rounded shadow space-y-3">
        <div><b>ผู้ส่ง:</b> {{ $report->user->name ?? '-' }} ({{ $report->user->email ?? '-' }})</div>
        <div><b>สถานี:</b> {{ $report->station->name ?? '-' }}</div>
        <div><b>ประเภท:</b>
          @php $types = ['no_power' => 'ไม่มีไฟ', 'occupied' => 'ไม่ว่าง', 'broken' => 'ชำรุด', 'other' => 'อื่น ๆ']; @endphp
          {{ $types[$report->type] ?? $report->type }}
        </div>
        <div><b>ข้อความ:</b> {{ $report->message }}</div>
        <div><b>สถานะ:</b>
          @php
            $label = ['0' => 'รอตรวจสอบ', '1' => 'ปิดงานแล้ว', '2' => 'ปฏิเสธ'][$report->status] ?? '-';
          @endphp
          {{ $label }}
        </div>
        <div><b>เวลาแจ้ง:</b> {{ $report->created_at?->format('Y-m-d H:i') }}</div>
      </div>

      <div class="flex gap-2">
        @if($report->status !== 1)
          <form method="POST" action="{{ route('admin.reports.resolve', $report) }}">
            @csrf
            <button class="px-4 py-2 border rounded" onclick="return confirm('ยืนยันปิดงานรายงานนี้?')">
              ปิดงาน
            </button>
          </form>
        @endif

        @if($report->status !== 2)
          <form method="POST" action="{{ route('admin.reports.reject', $report) }}">
            @csrf
            <button class="px-4 py-2 border rounded" onclick="return confirm('ยืนยันปฏิเสธรายงานนี้?')">
              ปฏิเสธ
            </button>
          </form>
        @endif

        <a href="{{ route('admin.reports.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded">กลับ</a>

        <form method="POST" action="{{ route('admin.reports.destroy', $report) }}" class="ml-auto"
          onsubmit="return confirm('ยืนยันลบรายงานนี้?')">
          @csrf @method('DELETE')
          <button class="px-4 py-2 bg-red-600 text-white rounded">ลบ</button>
        </form>
      </div>
    </div>
  </div>
</x-app-layout>