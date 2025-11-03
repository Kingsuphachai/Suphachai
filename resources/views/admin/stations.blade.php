<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">จัดการสถานี</h2>
    </x-slot>

    <div class="py-6">
      
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        
        <form method="GET" class="bg-white p-4 rounded shadow flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-sm text-gray-600">ค้นหาชื่อสถานี</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm text-gray-600">สถานะ</label>
                <select name="status_id" class="border rounded px-3 py-2">
                    <option value="">ทั้งหมด</option>
                    @foreach($statuses as $s)
                      <option value="{{ $s->id }}" @selected(($filters['status_id'] ?? '') !== '' && (int)$filters['status_id']===$s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-600">อำเภอ</label>
                <select name="district_id" class="border rounded px-3 py-2">
                    <option value="">ทั้งหมด</option>
                    @foreach($districts as $d)
                      <option value="{{ $d->id }}" @selected(($filters['district_id'] ?? '') !== '' && (int)$filters['district_id']===$d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded">ค้นหา</button>
            </div>
        </form>

        <div class="bg-white rounded shadow overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left">ชื่อสถานี</th>
                <th class="px-3 py-2 text-left">อำเภอ</th>
                <th class="px-3 py-2 text-left">สถานะ</th>
                <th class="px-3 py-2 text-left">พิกัด</th>
                <th class="px-3 py-2 text-left">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              @forelse($stations as $st)
                <tr class="border-t">
                  <td class="px-3 py-2">
                    <a href="{{ route('stations.show', $st->id) }}" class="text-indigo-600 underline">
                      {{ $st->name }}
                    </a>
                  </td>
                  <td class="px-3 py-2">{{ $st->district->name ?? '-' }}</td>
                  <td class="px-3 py-2">{{ $st->status->name ?? '-' }}</td>
                  <td class="px-3 py-2">
                    @if($st->latitude && $st->longitude)
                      {{ $st->latitude }}, {{ $st->longitude }}
                    @else
                      -
                    @endif
                  </td>
                  <td class="px-3 py-2">
                    {{-- ปุ่ม (ไว้ทำต่อ: edit/approve/delete) --}}
                    <a href="{{ route('stations.show', $st->id) }}" class="px-3 py-1 bg-slate-600 text-white rounded">ดู</a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">ไม่พบข้อมูล</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div>{{ $stations->links() }}</div>
      </div>
    </div>
    
</x-app-layout>
