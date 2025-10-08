<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">แผนที่สถานีชาร์จ</h2>
  </x-slot>

  <div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      @include('partials.stations-map')
    </div>
  </div>

  @stack('scripts')
</x-app-layout>
