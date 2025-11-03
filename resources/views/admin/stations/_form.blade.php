@php
    $editing = isset($station);
@endphp

<div class="grid gap-8 md:grid-cols-2">
    <div class="flex flex-col space-y-2">
        <label class="text-sm font-medium text-slate-600">ชื่อสถานี *</label>
        <input type="text" name="name" required
               class="form-control"
               value="{{ old('name', $editing ? $station->name : '') }}"
               placeholder="เช่น สถานีชาร์จกลางเมืองสกล">
        @error('name') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
    </div>

    <div class="flex flex-col space-y-2">
        <label class="text-sm font-medium text-slate-600">สถานะ *</label>
        <select name="status_id" class="form-control">
            @foreach($statuses as $s)
                <option value="{{ $s->id }}" @selected(old('status_id', $editing ? $station->status_id : '') == $s->id)>
                    {{ $s->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="md:col-span-2 flex flex-col space-y-2">
        <label class="text-sm font-medium text-slate-600">ที่อยู่</label>
        <textarea name="address" rows="2" class="form-control"
                  placeholder="บ้านเลขที่, ซอย, ถนน">{{ old('address', $editing ? $station->address : '') }}</textarea>
    </div>

    <div class="flex flex-col space-y-2">
        <label class="text-sm font-medium text-slate-600">อำเภอ *</label>
        <select name="district_id" id="district_id" class="form-control">
            @foreach($districts as $d)
                <option value="{{ $d->id }}" @selected(old('district_id', $editing ? $station->district_id : '') == $d->id)>
                    {{ $d->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="flex flex-col space-y-2">
        <label class="text-sm font-medium text-slate-600">ตำบล</label>
        <select name="subdistrict_id" id="subdistrict_id" class="form-control">
            <option value="">— เลือกตำบล —</option>
            @foreach($subdistricts as $sd)
                <option value="{{ $sd->id }}" data-district="{{ $sd->district_id }}" @selected(old('subdistrict_id', $editing ? $station->subdistrict_id : '') == $sd->id)>
                    {{ $sd->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="flex flex-col space-y-2">
        <label class="text-sm font-medium text-slate-600">พิกัด Latitude</label>
        <input type="text" name="latitude" class="form-control"
               value="{{ old('latitude', $editing ? $station->latitude : '') }}" placeholder="เช่น 17.1545">
    </div>

    <div class="flex flex-col space-y-2">
        <label class="text-sm font-medium text-slate-600">พิกัด Longitude</label>
        <input type="text" name="longitude" class="form-control"
               value="{{ old('longitude', $editing ? $station->longitude : '') }}" placeholder="เช่น 104.1347">
    </div>

    <div class="md:col-span-2 flex flex-col space-y-2">
        <label class="text-sm font-medium text-slate-600">เวลาทำการ</label>
        <input type="text" name="operating_hours" class="form-control"
               value="{{ old('operating_hours', $editing ? $station->operating_hours : '') }}"
               placeholder="เช่น 08:00-20:00">
    </div>

    <div class="md:col-span-2 flex flex-col space-y-3">
        <label class="text-sm font-medium text-slate-600">ประเภทหัวชาร์จ</label>
        <div class="flex flex-wrap gap-3">
            @foreach($chargers as $c)
                <label class="form-chip">
                    <input type="checkbox" name="charger_type_ids[]" value="{{ $c->id }}"
                           @checked(in_array($c->id, old('charger_type_ids', $selectedChargers ?? [])))
                           class="form-chip__input">
                    <span class="form-chip__label">{{ $c->name }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="md:col-span-2 flex flex-col space-y-3">
        <label class="text-sm font-medium text-slate-600">รูปภาพสถานี</label>

        @if($editing && !empty($station->image))
            <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <a href="{{ $station->image_url ?? asset('storage/' . $station->image) }}" target="_blank" class="block">
                    <img src="{{ $station->image_url ?? asset('storage/' . $station->image) }}"
                         alt="รูปสถานี {{ $station->name }}"
                         class="h-20 w-32 rounded-xl border border-slate-200 object-cover shadow-sm transition-transform duration-200 hover:scale-[1.03]">
                </a>

                <label class="inline-flex items-center gap-2 text-sm font-medium text-red-600">
                    <input type="checkbox" name="remove_image" value="1" class="rounded border-red-300 text-red-500 focus:ring-red-200">
                    <span>ลบรูปปัจจุบัน</span>
                </label>
            </div>
        @endif

        <input type="file" name="image" accept="image/*"
               class="form-control file-input">
        @error('image') <p class="text-sm text-red-500">{{ $message }}</p> @enderror

        <p class="text-xs text-slate-500">
            อัปโหลดรูปใหม่เพื่อแทนที่รูปเดิม หรือกา “ลบรูปปัจจุบัน” เพื่อเอาออก
        </p>
    </div>
</div>

<script>
    (function () {
        const distSel = document.getElementById('district_id');
        const subSel = document.getElementById('subdistrict_id');
        if (!distSel || !subSel) return;

        const originalOptions = Array.from(subSel.options);

        function renderSubdistricts(districtId) {
            const keep = '{{ old('subdistrict_id', $editing ? ($station->subdistrict_id ?? '') : '') }}';
            subSel.innerHTML = '';
            const ph = document.createElement('option');
            ph.value = '';
            ph.textContent = '— เลือกตำบล —';
            subSel.appendChild(ph);

            originalOptions.forEach(opt => {
                const did = opt.getAttribute('data-district');
                if (!did) return;
                if (String(districtId) === String(did)) {
                    subSel.appendChild(opt.cloneNode(true));
                }
            });

            const canKeep = Array.from(subSel.options).some(o => o.value === keep);
            subSel.value = canKeep ? keep : '';
        }

        distSel.addEventListener('change', () => renderSubdistricts(distSel.value));
        renderSubdistricts(distSel.value);
    })();
</script>
