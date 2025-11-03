@php
    $editing = isset($station);
@endphp


<div class="grid md:grid-cols-2 gap-6 inline-form-grid">
    <div class="inline-field">
        <label class="inline-label">ชื่อสถานี *</label>
        <input type="text" name="name" required class="inline-input"
            value="{{ old('name', $editing ? $station->name : '') }}" placeholder="ใส่ชื่อสถานี">
        @error('name') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
    </div>

    <div class="inline-field">
        <label class="inline-label">สถานะ *</label>
        <select name="status_id" class="inline-input">
            @foreach($statuses as $s)
                <option value="{{ $s->id }}" @selected(old('status_id', $editing ? $station->status_id : '') == $s->id)>
                    {{ $s->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="md:col-span-2 inline-field">
        <label class="inline-label">ที่อยู่</label>
        <textarea name="address" class="inline-input" rows="2"
            placeholder="บ้านเลขที่, ซอย, ถนน">{{ old('address', $editing ? $station->address : '') }}</textarea>
    </div>

    <div class="inline-field">
        <label class="inline-label">อำเภอ *</label>
        <select name="district_id" class="inline-input" id="district_id">
            @foreach($districts as $d)
                <option value="{{ $d->id }}" @selected(old('district_id', $editing ? $station->district_id : '') == $d->id)>
                    {{ $d->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="inline-field">
        <label class="inline-label">ตำบล</label>
        <select name="subdistrict_id" class="inline-input" id="subdistrict_id">
            <option value="">— เลือกตำบล —</option>
            @foreach($subdistricts as $sd)
                <option value="{{ $sd->id }}" data-district="{{ $sd->district_id }}" @selected(old('subdistrict_id', $editing ? $station->subdistrict_id : '') == $sd->id)>
                    {{ $sd->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="inline-field">
        <label class="inline-label">พิกัด Latitude</label>
        <input type="text" name="latitude" class="inline-input"
            value="{{ old('latitude', $editing ? $station->latitude : '') }}" placeholder="เช่น 17.1545">
    </div>

    <div class="inline-field">
        <label class="inline-label">พิกัด Longitude</label>
        <input type="text" name="longitude" class="inline-input"
            value="{{ old('longitude', $editing ? $station->longitude : '') }}" placeholder="เช่น 104.1347">
    </div>

    <div class="md:col-span-2 inline-field">
        <label class="inline-label">เวลาทำการ</label>
        <input type="text" name="operating_hours" class="inline-input"
            value="{{ old('operating_hours', $editing ? $station->operating_hours : '') }}"
            placeholder="เช่น 08:00-20:00">
    </div>

    <div class="md:col-span-2 inline-field">
        <label class="inline-label">ประเภทหัวชาร์จ</label>
        <div class="chip-group">
            @foreach($chargers as $c)
                <label class="chip-option">
                    <input type="checkbox" name="charger_type_ids[]" value="{{ $c->id }}" @checked(in_array($c->id, old('charger_type_ids', $selectedChargers ?? [])))>
                    <div class="chip-bg"></div>
                    <span>{{ $c->name }}</span>
                </label>
            @endforeach
        </div>
    </div>
    {{-- รูปภาพสถานี --}}
    <div class="md:col-span-2 inline-field">
        <label class="inline-label">รูปภาพสถานี</label>

        @if($editing && !empty($station->image))
            <div class="mb-2 flex items-center gap-3">
                <a href="{{ $station->image_url ?? asset('storage/' . $station->image) }}" target="_blank">
                    <img src="{{ $station->image_url ?? asset('storage/' . $station->image) }}"
                        alt="รูปสถานี {{ $station->name }}"
                        class="h-16 w-24 object-cover rounded border hover:scale-110 transition-transform duration-200">
                </a>

                {{-- เช็กบ็อกซ์ลบรูป (อยู่ในฟอร์มเดียวกัน, ไม่ใช่ฟอร์มแยก) --}}
                <label class="inline-flex items-center gap-2 text-red-700">
                    <input type="checkbox" name="remove_image" value="1">
                    <span>ลบรูปปัจจุบัน</span>
                </label>
            </div>
        @endif

        {{-- เลือกไฟล์ใหม่เพื่อแทนที่รูปเดิม --}}
        <input type="file" name="image" accept="image/*" class="inline-input bg-white">
        @error('image') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror

        <p class="inline-help">
            อัปโหลดรูปใหม่เพื่อแทนที่รูปเดิม หรือกา “ลบรูปปัจจุบัน” เพื่อเอาออก
        </p>
    </div>
</div>
{{-- 👉 JS กรองตำบลตามอำเภอ --}}
<script>
    (function () {
        const distSel = document.getElementById('district_id');   // 👉
        const subSel = document.getElementById('subdistrict_id'); // 👉
        if (!distSel || !subSel) return;

        // เก็บ options ต้นฉบับไว้ (เพื่อ rebuild)
        const originalOptions = Array.from(subSel.options);

        function renderSubdistricts(districtId) {
            const keep = '{{ old('subdistrict_id', $editing ? ($station->subdistrict_id ?? '') : '') }}';

            // ล้าง + ใส่ placeholder
            subSel.innerHTML = '';
            const ph = document.createElement('option');
            ph.value = '';
            ph.textContent = '— เลือกตำบล —';
            subSel.appendChild(ph);

            // เติมเฉพาะตำบลที่ district_id ตรง
            originalOptions.forEach(opt => {
                const did = opt.getAttribute('data-district');
                if (!did) return; // ข้าม placeholder เดิม
                if (String(districtId) === String(did)) {
                    subSel.appendChild(opt.cloneNode(true));
                }
            });

            // ถ้าค่าที่เคยเลือกยังอยู่ในอำเภอนี้ ให้คงไว้
            const canKeep = Array.from(subSel.options).some(o => o.value === keep);
            subSel.value = canKeep ? keep : '';
        }

        // เปลี่ยนอำเภอ → เรนเดอร์ตำบลใหม่
        distSel.addEventListener('change', () => renderSubdistricts(distSel.value));

        // โหลดครั้งแรกให้ตรงกับค่าเดิม
        renderSubdistricts(distSel.value);
    })();
</script>
