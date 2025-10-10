<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800">ขอเพิ่มสถานีชาร์จ</h2>
  </x-slot>

  <div class="py-6">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow sm:rounded-lg p-6">
        {{-- ✅ ฟอร์มขอเพิ่มสถานี --}}
        <form method="POST" action="{{ route('user.request.store') }}" 
              enctype="multipart/form-data" {{-- 🔹เพิ่มเพื่ออัปโหลดรูป --}}
              class="space-y-4">
          @csrf

          {{-- ชื่อสถานี --}}
          <div>
            <label class="block font-medium">ชื่อสถานี <span class="text-red-600">*</span></label>
            <input name="name" value="{{ old('name') }}" class="w-full border rounded p-2" required>
            @error('name') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
          </div>

          {{-- ที่อยู่ --}}
          <div>
            <label class="block font-medium">ที่อยู่</label>
            <textarea name="address" class="w-full border rounded p-2" rows="2">{{ old('address') }}</textarea>
          </div>

          {{-- อำเภอ / ตำบล --}}
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block font-medium">อำเภอ <span class="text-red-600">*</span></label>
              <select name="district_id" id="district_id" class="w-full border rounded p-2" required>
                <option value="">-- เลือกอำเภอ --</option>
                @foreach($districts as $d)
                  <option value="{{ $d->id }}" @selected(old('district_id')==$d->id)>
                    {{ $d->name }}
                  </option>
                @endforeach
              </select>
              @error('district_id') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block font-medium">ตำบล</label>
              <select name="subdistrict_id" id="subdistrict_id" class="w-full border rounded p-2">
                <option value="">-- เลือกตำบล --</option>
                @foreach($subdistricts as $s)
                  {{-- 🔹เก็บ district_id ของแต่ละตำบลไว้เพื่อใช้กรอง --}}
                  <option value="{{ $s->id }}" data-district="{{ $s->district_id }}"
                    @selected(old('subdistrict_id')==$s->id)>
                    {{ $s->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>

          {{-- เวลาทำการ --}}
          <div>
            <label class="block font-medium">เวลาทำการ</label>
            <input name="operating_hours" value="{{ old('operating_hours') }}" 
              class="w-full border rounded p-2" placeholder="เช่น 08:00-20:00">
          </div>

          {{-- Latitude / Longitude --}}
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block font-medium">Latitude</label>
              <input name="latitude" value="{{ old('latitude') }}" 
                     class="w-full border rounded p-2" placeholder="เช่น 17.1545">
            </div>
            <div>
              <label class="block font-medium">Longitude</label>
              <input name="longitude" value="{{ old('longitude') }}" 
                     class="w-full border rounded p-2" placeholder="เช่น 104.1347">
            </div>
          </div>

          {{-- ประเภทหัวชาร์จ --}}
          <div>
            <label class="block font-medium mb-1">ประเภทหัวชาร์จ</label>
            <div class="flex flex-wrap gap-3">
              @foreach($chargers as $c)
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" name="charger_type_ids[]" value="{{ $c->id }}"
                    {{ in_array($c->id, old('charger_type_ids', [])) ? 'checked' : '' }}>
                  {{ $c->name }}
                </label>
              @endforeach
            </div>
          </div>

          {{-- 🔹เพิ่มช่องอัปโหลดรูป --}}
          <div>
            <label class="block font-medium mb-1">รูปสถานี (ไม่บังคับ)</label>
            <input type="file" name="image" accept="image/*" class="w-full border rounded p-2">
          </div>

          {{-- ปุ่ม --}}
          <div class="pt-4 flex gap-2">
            <button class="px-4 py-2 border rounded">
              ส่งคำขอ
            </button>
            <a href="{{ route('user.dashboard') }}" class="px-4 py-2 border rounded">ยกเลิก</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- 🔹 Script: กรองตำบลตามอำเภอ --}}
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const districtSelect = document.getElementById('district_id');
      const subdistrictSelect = document.getElementById('subdistrict_id');
      if (!districtSelect || !subdistrictSelect) return;

      const allOptions = Array.from(subdistrictSelect.options);

      const renderSubdistricts = (districtId) => {
        subdistrictSelect.innerHTML = '<option value="">-- เลือกตำบล --</option>';
        allOptions.forEach(opt => {
          if (opt.dataset.district === districtId) {
            subdistrictSelect.appendChild(opt.cloneNode(true));
          }
        });
      };

      districtSelect.addEventListener('change', () => {
        renderSubdistricts(districtSelect.value);
      });

      // โหลดครั้งแรกถ้ามีค่าเดิม
      renderSubdistricts(districtSelect.value);
    });
  </script>
</x-app-layout>
