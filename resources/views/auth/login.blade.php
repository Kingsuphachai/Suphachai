<x-guest-layout>

    {{-- โลโก้ EV Charging --}}
    <div class="mb-8 flex flex-col items-center">
        <img src="{{ asset('images/ev-logo.png') }}" alt="EV Logo" class="h-28 w-auto drop-shadow-sm">

    </div>

    <!-- สถานะเซสชัน -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- อีเมล -->
        <div>
            <x-input-label for="email" :value="__('อีเมล')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required
                autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- รหัสผ่าน -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('รหัสผ่าน')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- จดจำฉันไว้ -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('จดจำฉันไว้ในระบบ') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    href="{{ route('password.request') }}">
                    {{ __('ลืมรหัสผ่าน?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('เข้าสู่ระบบ') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>