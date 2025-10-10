@php
    use Illuminate\Support\Facades\Route;

    $links = [
        [
            'icon'   => '🗺️',
            'label'  => 'แผงควบคุมผู้ดูแลระบบ',   // หน้าแดชบอร์ด (แผนที่)
            'url'    => route('admin.dashboard'),
            'active' => request()->routeIs('admin.dashboard'),
        ],
        [
            'icon'   => '⚡',
            'label'  => 'จัดการสถานี',
            'url'    => route('admin.stations.index'),
            'active' => request()->routeIs('admin.stations.*'),
        ],
        [
            'icon'   => '👥',
            'label'  => 'จัดการผู้ใช้',
            'url'    => route('admin.users.index'),
            'active' => request()->routeIs('admin.users.*'),
        ],
        [
            'icon'   => '🛠️',
            'label'  => 'รายงานปัญหา',
            'url'    => route('admin.reports.index'),
            'active' => request()->routeIs('admin.reports.*'),
        ],
        [
            'icon'   => '🔔',
            'label'  => 'แจ้งเตือน',
            // กัน error ถ้ายังไม่ได้สร้าง route แจ้งเตือน
            'url'    => Route::has('admin.notifications.index') ? route('admin.notifications.index') : '#',
            'active' => request()->routeIs('admin.notifications.*'),
        ],
    ];
@endphp

<div class="fixed inset-x-0 bottom-3 z-[1000] px-3">
  <div class="mx-auto max-w-5xl">
    <div class="grid grid-cols-5 bg-white/95 backdrop-blur rounded-2xl shadow-2xl ring-1 ring-black/5 overflow-hidden">
      @foreach ($links as $it)
        <a href="{{ $it['url'] }}"
           class="flex flex-col items-center justify-center py-3 text-[12px] leading-4
                  {{ $it['active'] ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' }}">
          <div class="text-xl">{{ $it['icon'] }}</div>
          <div class="mt-1">{{ $it['label'] }}</div>
        </a>
      @endforeach
    </div>
  </div>
</div>
