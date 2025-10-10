<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800">จัดการรายงานปัญหา</h2>
  </x-slot>

  <div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

      @if(session('success'))
        <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
      @endif

      {{-- ฟิลเตอร์ --}}
      <form method="GET" class="bg-white p-4 rounded shadow flex flex-wrap gap-3 items-end">
        <div>
          <label class="block text-sm text-gray-600">สถานะ</label>
          <select name="status" class="border rounded px-3 py-2">
            @php $sel = request('status','all'); @endphp
            <option value="all" @selected($sel==='all')>ทั้งหมด</option>
            <option value="0"   @selected($sel==='0')>รอตรวจสอบ</option>
            <option value="1"   @selected($sel==='1')>ปิดงานแล้ว</option>
            <option value="2"   @selected($sel==='2')>ปฏิเสธ</option>
          </select>
        </div>

        <div>
          <label class="block text-sm text-gray-600">สถานี</label>
          <select name="station_id" class="border rounded px-3 py-2">
            <option value="">ทั้งหมด</option>
            @foreach($stations as $st)
              <option value="{{ $st->id }}" @selected((int)request('station_id')===$st->id)>
                {{ $st->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-sm text-gray-600">ค้นหา</label>
          <input type="text" name="q" value="{{ request('q') }}" class="border rounded px-3 py-2" placeholder="ข้อความ/ประเภท">
        </div>

        <div>
          <button class="px-4 py-2 bg-indigo-600 text-white rounded">ค้นหา</button>
          @if(request()->hasAny(['status','station_id','q']) && (request('status')!=='all' || request('station_id') || request('q')))
            <a href="{{ route('admin.reports.index') }}" class="px-4 py-2 border rounded">ล้างตัวกรอง</a>
          @endif
        </div>
      </form>

      {{-- ตาราง --}}
      <div class="bg-white rounded shadow overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left">วันที่</th>
              <th class="px-3 py-2 text-left">ผู้ส่ง</th>
              <th class="px-3 py-2 text-left">สถานี</th>
              <th class="px-3 py-2 text-left">ประเภท</th>
              <th class="px-3 py-2 text-left">สถานะ</th>
              <th class="px-3 py-2 text-left">จัดการ</th>
            </tr>
          </thead>
          <tbody>
          @forelse($reports as $r)
            <tr class="border-t">
              <td class="px-3 py-2">{{ $r->created_at?->format('Y-m-d H:i') }}</td>
              <td class="px-3 py-2">{{ $r->user->name ?? '-' }}</td>
              <td class="px-3 py-2">{{ $r->station->name ?? '-' }}</td>
              <td class="px-3 py-2">
                @php $types = ['no_power'=>'ไม่มีไฟ', 'occupied'=>'ไม่ว่าง', 'broken'=>'ชำรุด', 'other'=>'อื่น ๆ']; @endphp
                {{ $types[$r->type] ?? $r->type }}
              </td>
              <td class="px-3 py-2">
                @php
                  $badge = ['0'=>'bg-yellow-100 text-yellow-800','1'=>'bg-green-100 text-green-800','2'=>'bg-red-100 text-red-800'][$r->status] ?? 'bg-gray-100';
                  $label = ['0'=>'รอตรวจสอบ','1'=>'ปิดงานแล้ว','2'=>'ปฏิเสธ'][$r->status] ?? '-';
                @endphp
                <span class="px-2 py-1 rounded text-xs {{ $badge }}">{{ $label }}</span>
              </td>
              <td class="px-3 py-2">
                <a href="{{ route('admin.reports.show', $r) }}" class="px-3 py-1 bg-slate-600 text-white rounded">ดู</a>
                <form action="{{ route('admin.reports.destroy', $r) }}" method="POST" class="inline" onsubmit="return confirm('ยืนยันลบรายงานนี้?');">
                  @csrf @method('DELETE')
                  <button class="px-3 py-1 bg-red-600 text-white rounded">ลบ</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">ไม่มีรายการ</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>

      <div>{{ $reports->links() }}</div>
    </div>
  </div>
</x-app-layout>
