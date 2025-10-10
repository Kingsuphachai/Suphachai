<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('ข้อมูลโปรไฟล์') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('อัปเดตข้อมูลโปรไฟล์ของบัญชีของคุณได้ที่นี่') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('ชื่อ')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)"
                required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('อีเมล')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full bg-gray-100 cursor-not-allowed"
                :value="old('email', $user->email)" readonly disabled />

            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('ที่อยู่อีเมลของคุณยังไม่ได้รับการยืนยัน') }}

                        <button form="send-verification"
                            class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('คลิกที่นี่เพื่อส่งอีเมลยืนยันอีกครั้ง') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('ได้ส่งลิงก์ยืนยันใหม่ไปยังอีเมลของคุณแล้ว') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('บันทึก') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600">{{ __('บันทึกเรียบร้อยแล้ว') }}</p>
            @endif
        </div>
    </form>
</section>