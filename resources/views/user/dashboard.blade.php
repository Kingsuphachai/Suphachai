<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('partials.stations-map')
        </div>
    </div>

    @stack('scripts')
</x-app-layout>