{{-- resources/views/admin/users/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            แก้ไขสมาชิก #{{ $user->name }}</h2>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow sm:rounded-lg p-6">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf @method('PUT')

                <div class="mb-4">
                    <label class="block font-medium">ชื่อ</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="w-full border rounded p-2 bg-gray-100 text-gray-600" readonly>
                </div>

                <div class="mb-4">
                    <label class="block font-medium">อีเมล</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                        class="w-full border rounded p-2 bg-gray-100 text-gray-600" readonly>
                </div>


                <div class="mb-4">
                    <label class="block font-medium">บทบาท</label>
                    <select name="role_id" class="w-full border rounded p-2" required>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}" {{ old('role_id', $user->role_id) == $r->id ? 'selected' : '' }}>
                                {{ $r->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2">
                    <button class="px-4 py-2 border rounded">อัพเดต</button>
                    <a href="{{ route('admin.users.index') }}"
                        class="px-4 py-2 bg-gray-500 text-white rounded">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>