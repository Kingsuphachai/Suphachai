<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800">
      แผงควบคุมผู้ดูแลระบบ
    </h2>
  </x-slot>

  <div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

      <!-- ส่วนสถิติ -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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

      <!-- ลิงก์แก้จัดการณ์ -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="{{ route('admin.stations.index') }}"
          class="block bg-gray-800 text-white p-6  shadow hover:bg-gray-900 transition">
          <div class="text-lg font-semibold">จัดการสถานี</div>
          <div class="mt-1 text-gray-200 text-sm">เพิ่ม แก้ไข หรือลบสถานี</div>
        </a>
        <a href="{{ route('admin.users.index') }}"
          class="block bg-gray-800 text-white p-6  shadow hover:bg-gray-900 transition">
          <div class="text-lg font-semibold">จัดการผู้ใช้</div>
          <div class="mt-1 text-gray-200 text-sm">แก้ไขข้อมูลสมาชิก</div>
        </a>
        <a href="{{ route('admin.reports.index') }}"
          class="block bg-gray-800 text-white p-6 shadow hover:bg-gray-900 transition">
          <div class="text-lg font-semibold">รายงานปัญหา</div>
          <div class="mt-1 text-gray-200 text-sm">ตรวจสอบ/ปิดงาน/ปฏิเสธ</div>
        </a>


      </div>

    </div>
  </div>
</x-app-layout>