<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('ลบบัญชีผู้ใช้') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('เมื่อบัญชีของคุณถูกลบ ข้อมูลและทรัพยากรทั้งหมดจะถูกลบอย่างถาวร กรุณาดาวน์โหลดข้อมูลที่คุณต้องการเก็บไว้ก่อนดำเนินการลบบัญชี') }}
        </p>
    </header>

    <x-danger-button x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">{{ __('ลบบัญชีผู้ใช้') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('คุณแน่ใจหรือไม่ว่าต้องการลบบัญชีนี้?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('เมื่อบัญชีของคุณถูกลบ ข้อมูลและทรัพยากรทั้งหมดจะถูกลบอย่างถาวร กรุณากรอกรหัสผ่านเพื่อยืนยันการลบบัญชีถาวรของคุณ') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('รหัสผ่าน') }}" class="sr-only" />

                <x-text-input id="password" name="password" type="password" class="mt-1 block w-3/4"
                    placeholder="{{ __('รหัสผ่าน') }}" />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('ยกเลิก') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('ลบบัญชีผู้ใช้') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>