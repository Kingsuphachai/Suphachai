<x-app-layout>
  <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">แก้ไขสถานี #{{ $station->name }}</h2></x-slot>

  <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8">
    <form method="POST"
          action="{{ route('admin.stations.update',$station) }}"
          enctype="multipart/form-data"                             {{-- 👈 สำคัญมาก --}}
          class="bg-white shadow sm:rounded-lg p-6 space-y-4">
      @csrf @method('PUT')
      @include('admin.stations._form', ['station' => $station])
      <div class="flex gap-2">
        <button class="px-4 py-2 border rounded">อัปเดต</button>
        <a href="{{ route('admin.stations.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded">ยกเลิก</a>
      </div>
    </form>
  </div>
</x-app-layout>
