{{-- resources/views/layouts/app.blade.php (ตัวอย่างโครงหลัก) --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    {{-- ... meta/css/... --}}
    @vite(['resources/css/app.css','resources/js/app.js'])
  </head>
  <body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
      @include('layouts.navigation')
      <!-- Page Heading -->
      @isset($header)
        <header class="bg-white shadow">
          <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            {{ $header }}
          </div>
        </header>
      @endisset

      <!-- Page Content -->
      <main>
        {{ $slot }}
      </main>
    </div>

    {{-- ✅ โหลด Google Maps SDK กลาง (ครั้งเดียวทั้งเว็บ) --}}
    @include('partials.google-maps-loader')

    {{-- stack สคริปต์หน้าเพจต่าง ๆ --}}
    @stack('scripts')
  </body>
</html>
