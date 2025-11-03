<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800">จัดการรายงานปัญหา</h2>
  </x-slot>
  {{-- 🔻 แถบล่างแนวยาว (6 ปุ่ม: แผนที่, จัดการสถานี, จัดการผู้ใช้, รายงานปัญหา, แจ้งเตือน, สถิติ) --}}
  <style>
    /* === โหมดพื้นฐาน: ล่าง-กึ่งกลางจอ === */
    .floating-actions {
      position: fixed;
      inset: auto 0 14px 0;
      /* left:0; right:0; bottom:14px */
      z-index: 99999;
      display: flex;
      justify-content: center;
      /* กึ่งกลางแนวนอน */
      pointer-events: none;
      /* ให้คลิกผ่าน wrapper ได้ */
      padding: 0 12px;
    }

    .floating-actions__inner {
      pointer-events: auto;
      /* รับคลิกเฉพาะกล่องด้านใน */
      background: #7c3aed;
      color: #111827;
      padding: 12px;
      border-radius: 20px;
      box-shadow: 0 10px 28px rgba(124, 58, 237, .22);
      width: min(840px, 96vw);
      /* กว้างพอดีและกึ่งกลาง */
    }

    .floating-actions__list {
      display: grid;
      grid-template-columns: repeat(6, minmax(0, 1fr));
      /* 6 ปุ่มเรียงแนวนอน */
      gap: 10px;
    }

    .floating-actions__item {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 10px 8px;
      background: #fff;
      border: 1px solid #7c3aed;
      border-radius: 14px;
      text-decoration: none;
      font-size: 12px;
      box-shadow: 0 6px 18px rgba(124, 58, 237, .14);
      transition: transform .2s, box-shadow .2s, background .2s;
    }

    .floating-actions__item:hover {
      transform: translateY(-2px);
      background: #f9f5ff;
    }

    /* จอแคบมาก ให้แตกเป็น 3x2 แต่ยังอยู่ล่าง-กึ่งกลาง */
    @media (max-width: 560px) {
      .floating-actions__list {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }
    }

    /* === โหมด Desktop: ขวากลางจอ (แนวตั้ง) === */
    @media (min-width: 1024px) {
      .floating-actions {
        top: 50%;
        right: 12px;
        left: auto;
        bottom: auto;
        transform: translateY(-50%);
        /* จัดกึ่งกลางแนวตั้ง */
        padding: 0;
        justify-content: flex-end;
        /* ชิดขวา */
      }

      .floating-actions__inner {
        width: 100px;
        border-radius: 24px;
        padding: 8px 6px;
      }

      .floating-actions__list {
        display: flex;
        flex-direction: column;
        /* เรียงแนวตั้ง */
        gap: 5px;
      }
    }
  </style>


  <div class="floating-actions">
    <div class="floating-actions__inner">
      <div class="floating-actions__list">

        {{-- 🗺️ แผนที่ --}}
        <a href="{{ route('stations.map') }}" class="floating-actions__item">
          <div class="floating-actions__icon">🗺️</div>
          <div class="floating-actions__label">แผนที่</div>
        </a>

        {{-- 🏭 จัดการสถานี --}}
        <a href="{{ route('admin.stations.index') }}" class="floating-actions__item">
          <div class="floating-actions__icon">🏭</div>
          <div class="floating-actions__label">จัดการสถานี</div>
        </a>

        {{-- 👤 จัดการผู้ใช้ --}}
        <a href="{{ route('admin.users.index') }}" class="floating-actions__item">
          <div class="floating-actions__icon">👤</div>
          <div class="floating-actions__label">จัดการผู้ใช้</div>
        </a>

        {{-- ⚠️ รายงานปัญหา --}}
        <a href="{{ route('admin.reports.index') }}" class="floating-actions__item">
          <div class="floating-actions__icon">⚠️</div>
          <div class="floating-actions__label">รายงานปัญหา</div>
        </a>

        {{-- 🔔 แจ้งเตือน --}}
        <a href="{{ route('admin.notifications.index') }}" class="floating-actions__item">
          <div class="floating-actions__icon">🔔</div>
          <div class="floating-actions__label">แจ้งเตือน</div>
        </a>

        {{-- 📊 สถิติ --}}
        <a href="{{ route('admin.dashboard') }}" class="floating-actions__item">
          <div class="floating-actions__icon">📊</div>
          <div class="floating-actions__label">สถิติ</div>
        </a>

      </div>
    </div>
  </div>
  <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white shadow sm:rounded-lg p-6">

      @if(session('success'))
        <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
      @endif

      {{-- ฟิลเตอร์ --}}
      <form method="GET" class="mb-6 flex flex-wrap items-end gap-4">
        <div class="flex flex-col">
          <label class="text-sm font-medium text-gray-700 mb-1">สถานะ</label>
          <select name="status"
            class="border border-gray-300 rounded-lg px-3 py-2 w-48 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @php $sel = request('status', 'all'); @endphp
            <option value="all" @selected($sel === 'all')>ทั้งหมด</option>
            <option value="0" @selected($sel === '0')>รอตรวจสอบ</option>
            <option value="1" @selected($sel === '1')>ปิดงานแล้ว</option>
            <option value="2" @selected($sel === '2')>ปฏิเสธ</option>
          </select>
        </div>

        <div class="flex flex-col">
          <label class="text-sm font-medium text-gray-700 mb-1">สถานี</label>
          <select name="station_id"
            class="border border-gray-300 rounded-lg px-3 py-2 w-48 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">ทั้งหมด</option>
            @foreach($stations as $st)
              <option value="{{ $st->id }}" @selected((int) request('station_id') === $st->id)>
                {{ $st->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="flex flex-col">
          <label class="text-sm font-medium text-gray-700 mb-1">ค้นหา</label>
          <input type="text" name="q" value="{{ request('q') }}"
            class="border border-gray-300 rounded-lg px-3 py-2 w-48 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="ข้อความ/ประเภท">
        </div>

        <div class="flex flex-col">
          <label class="text-sm font-medium text-gray-700 mb-1 invisible">.</label>
          <button
            class="px-5 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 focus:ring-2 focus:ring-gray-400">ค้นหา</button>
        </div>
      </form>

      {{-- ตาราง --}}

      <table class="w-full border">
        <thead>
          <tr class="bg-gray-100">
            <th class="p-2 border">ลำดับ</th>
            <th class="p-2 border">วันที่</th>
            <th class="p-2 border">ผู้ส่ง</th>
            <th class="p-2 border">สถานี</th>
            <th class="p-2 border">ประเภทปัญหา</th>
            <th class="p-2 border">รายละเอียด</th>
            <th class="p-2 border">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          @forelse($reports as $r)
            <tr>
              <td class="p-2 border">
                <div class="flex items-center justify-center h-full">
                  {{ ($reports->currentPage() - 1) * $reports->perPage() + $loop->iteration }}
                </div>
              </td>
              <td class="p-2 border">{{ $r->created_at?->format('Y-m-d H:i') }}</td>
              <td class="p-2 border">{{ $r->user->name ?? '-' }}</td>
              <td class="p-2 border">{{ $r->station->name ?? '-' }}</td>
              <td class="p-2 border">
                @php $types = ['no_power' => 'ไม่มีไฟ', 'occupied' => 'ไม่ว่าง', 'broken' => 'ชำรุด', 'other' => 'อื่น ๆ']; @endphp
                {{ $types[$r->type] ?? $r->type }}
              </td>
              <td class="p-2 border">
                {{ \Illuminate\Support\Str::limit($r->message, 80) }}
              </td>
              <td class="p-2 border">

                  <form method="POST" action="{{ route('admin.reports.confirm', $r) }}" class="inline-block"
                    onsubmit="return confirm('ยืนยันรายงานนี้?');">
                  @csrf
                  <button type="submit"
                    class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100 hover:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-1">
                    ยืนยัน
                  </button>
                </form>

                <form action="{{ route('admin.reports.destroy', $r) }}" method="POST" class="inline-block"
                  onsubmit="return confirm('ยืนยันลบรายงานนี้?');">
                  @csrf @method('DELETE')
                  <button type="submit"
                    class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-100 hover:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1">
                    ลบ
                  </button>
                </form>

              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-3 py-6 text-center text-gray-500">ไม่มีรายการ</td>
            </tr>
          @endforelse
        </tbody>
      </table>


      <div>{{ $reports->links() }}</div>
    </div>
  </div>
</x-app-layout>
