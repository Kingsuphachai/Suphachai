<x-guest-layout>
    <div class="mb-8 flex flex-col items-center">
        <img src="{{ asset('images/ev-logo.png') }}" alt="EV Logo" class="h-28 w-auto drop-shadow-sm">

    </div>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('ลืมรหัสผ่านใช่ไหม? ไม่เป็นไร เพียงแค่กรอกที่อยู่อีเมลของคุณ แล้วเราจะส่งลิงก์รีเซ็ตรหัสผ่านไปให้ เพื่อให้คุณตั้งรหัสใหม่ได้เอง') }}
    </div>

    <!-- สถานะข้อความ -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- ที่อยู่อีเมล -->
        <div>
            <x-input-label for="email" :value="__('อีเมล')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required
                autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('ส่งลิงก์รีเซ็ตรหัสผ่านไปทางอีเมล') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>