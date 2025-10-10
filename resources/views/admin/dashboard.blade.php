<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800">
      แผงข้อมูลสถิติ
    </h2>
  </x-slot>

  <div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

      <!-- ส่วนสถิติ -->
      <div id="stats" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6  shadow">
          <div class="text-gray-500">จำนวนสถานีทั้งหมด</div>
          <div class="text-3xl font-bold">{{ $stats['stations_total'] ?? '-' }}</div>
        </div>
        <div class="bg-white p-6  shadow">
          <div class="text-gray-500">จำนวนผู้ใช้ทั้งหมด</div>
          <div class="text-3xl font-bold">{{ $stats['users_total'] ?? '-' }}</div>
        </div>
        <div class="bg-white p-6  shadow">
          <div class="text-gray-500">จำนวน Admin</div>
          <div class="text-3xl font-bold">{{ $stats['admins'] ?? '-' }}</div>
        </div>
      </div>

      <!-- Flash message -->
      @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded">
          {{ session('success') }}
        </div>
      @endif

      {{-- 🔻 แถบล่างแนวยาว (6 ปุ่ม: แผนที่, จัดการสถานี, จัดการผู้ใช้, รายงานปัญหา, แจ้งเตือน, สถิติ) --}}
      <div style="
        position:fixed; left:0; right:0; bottom:0; z-index:99999;
        background:#fffffff5; backdrop-filter:saturate(180%) blur(10px);
        border-top:1px solid #e5e7eb; box-shadow:0 -5px 20px rgba(0,0,0,0.1);
        padding:8px 12px;
    ">
        <div style="max-width:1200px; margin:0 auto;">
          <div style="display:grid; grid-template-columns:repeat(6,1fr); gap:12px;">

            {{-- 🗺️ แผนที่ --}}
            <a href="{{ route('stations.map') }}" style="display:flex; flex-direction:column; align-items:center; justify-content:center;
                           height:90px; border-radius:18px; background:#fff;
                           font-size:14px; color:#374151; border:1px solid #e5e7eb;
                           text-decoration:none; box-shadow:0 4px 12px rgba(0,0,0,.06);">
              <div style="font-size:22px;">🗺️</div>
              <div style="margin-top:6px;">แผนที่</div>
            </a>

            {{-- 🏭 จัดการสถานี --}}
            <a href="{{ route('admin.stations.index') }}" style="display:flex; flex-direction:column; align-items:center; justify-content:center;
                           height:90px; border-radius:18px; background:#fff;
                           font-size:14px; color:#374151; border:1px solid #e5e7eb;
                           text-decoration:none; box-shadow:0 4px 12px rgba(0,0,0,.06);">
              <div style="font-size:22px;">🏭</div>
              <div style="margin-top:6px;">จัดการสถานี</div>
            </a>

            {{-- 👤 จัดการผู้ใช้ --}}
            <a href="{{ route('admin.users.index') }}" style="display:flex; flex-direction:column; align-items:center; justify-content:center;
                           height:90px; border-radius:18px; background:#fff;
                           font-size:14px; color:#374151; border:1px solid #e5e7eb;
                           text-decoration:none; box-shadow:0 4px 12px rgba(0,0,0,.06);">
              <div style="font-size:22px;">👤</div>
              <div style="margin-top:6px;">จัดการผู้ใช้</div>
            </a>

            {{-- ⚠️ รายงานปัญหา --}}
            <a href="{{ route('admin.reports.index') }}" style="display:flex; flex-direction:column; align-items:center; justify-content:center;
                           height:90px; border-radius:18px; background:#fff;
                           font-size:14px; color:#374151; border:1px solid #e5e7eb;
                           text-decoration:none; box-shadow:0 4px 12px rgba(0,0,0,.06);">
              <div style="font-size:22px;">⚠️</div>
              <div style="margin-top:6px;">รายงานปัญหา</div>
            </a>

            {{-- 🔔 แจ้งเตือน --}}
            <a href="{{ route('admin.notifications.index') }}" style="display:flex; flex-direction:column; align-items:center; justify-content:center;
                           height:90px; border-radius:18px; background:#fff;
                           font-size:14px; color:#374151; border:1px solid #e5e7eb;
                           text-decoration:none; box-shadow:0 4px 12px rgba(0,0,0,.06);">
              <div style="font-size:22px;">🔔</div>
              <div style="margin-top:6px;">แจ้งเตือน</div>
            </a>

            {{-- 📊 สถิติ --}}
            <a href="{{ route('admin.dashboard') }}" style="display:flex; flex-direction:column; align-items:center; justify-content:center;
          height:90px; border-radius:18px; background:#fff;
          font-size:14px; color:#374151; border:1px solid #e5e7eb;
          text-decoration:none; box-shadow:0 4px 12px rgba(0,0,0,.06);">
              <div style="font-size:22px;">📊</div>
              <div style="margin-top:6px;">สถิติ</div>
            </a>


          </div>
        </div>
      </div>
    </div>
</x-app-layout>