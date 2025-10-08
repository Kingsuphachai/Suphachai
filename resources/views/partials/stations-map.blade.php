{{-- resources/views/partials/stations-map.blade.php --}}
<div class="space-y-3">
  <div id="map" class="w-full rounded-md border" style="height:80vh;"></div>
</div>

@push('scripts')
  <script>
    (() => {
      /* ===================== Config / State ===================== */
      const API_URL = @json(route('api.stations'));
      const PLACEHOLDER = @json(asset('images/no-image.png'));
      const SHOW_BASE_URL = @json(url('/stations'));

      let map, infoWindow, myMarker;
      let allStations = [];
      const markersById = Object.create(null);
      let myOrigin = null;

      /* ===================== Utils ===================== */
      const distKm = (a, b) => {           // หาระยะ (กม.)
        const R = 6371, dLat = (b.lat - a.lat) * Math.PI / 180, dLng = (b.lng - a.lng) * Math.PI / 180;
        const s1 = Math.sin(dLat / 2) ** 2;
        const s2 = Math.cos(a.lat * Math.PI / 180) * Math.cos(b.lat * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
        return 2 * R * Math.asin(Math.sqrt(s1 + s2));
      };
      const safeText = (v, f = '-') => (v ?? '').toString().trim() || f;
      const joinNonEmpty = (arr, sep = ' ') => arr.filter(Boolean).join(sep);

      /* 👉 กำหนด icon ตามสถานะ (รองรับทั้ง status_id และข้อความ) */
      const ICONS = {
        green: 'https://maps.gstatic.com/mapfiles/ms2/micons/green-dot.png',
        yellow: 'https://maps.gstatic.com/mapfiles/ms2/micons/yellow-dot.png',
        red: 'https://maps.gstatic.com/mapfiles/ms2/micons/red-dot.png',
        blue: 'https://maps.gstatic.com/mapfiles/ms2/micons/blue-dot.png',
      };
      function iconForStatus(s) {
        // ถ้ามี status_id ให้ map ตรง ๆ
        if (s.status_id === 1) return ICONS.green;   // พร้อมใช้งาน
        if (s.status_id === 0) return ICONS.yellow;  // รอตรวจสอบ
        if (s.status_id === 2) return ICONS.red;     // ชำรุด

        // 👉 fallback ตามข้อความ (กันกรณีไม่มี status_id)
        const t = (s.status || '').toString().trim().toLowerCase();
        if (/(พร้อม|available|ready)/.test(t)) return ICONS.green;
        if (/(รอ|คิว|pending|ตรวจสอบ|maintenance|ซ่อม)/.test(t)) return ICONS.yellow;
        if (/(ชำรุด|เสีย|ปิด|out\s*of\s*service|down)/.test(t)) return ICONS.red;
        return ICONS.blue;
      }

      /* InfoWindow HTML (รูป/ที่อยู่/สถานะ/เวลา/หัวชาร์จ) */
      function infoHtml(s) {
        const addressLine = joinNonEmpty([
          safeText(s.address, ''),
          s.subdistrict ? `ต.${s.subdistrict}` : '',
          s.district ? `อ.${s.district}` : '',
          s.province ? `จ.${s.province}` : '',
          s.postcode ? s.postcode : '',
        ], ' ');
        const chargers = Array.isArray(s.chargers) ? s.chargers.join(' • ') : (s.chargers || '');
        const imgSrc = s.image_url || PLACEHOLDER;

        return `
            <div style="min-width:260px;max-width:320px">
              <div style="margin:-8px -8px 8px -8px;">
                <img src="${imgSrc}" alt="${s.name ?? ''}"
                     style="width:100%;height:150px;object-fit:cover;border-radius:8px 8px 0 0;" loading="lazy">
              </div>
              <div style="font-weight:700;font-size:15px">${safeText(s.name)}</div>
              <div style="font-size:13px;color:#374151;margin-top:2px">${addressLine || '-'}</div>
              <div style="font-size:13px;margin-top:6px">
                <div><b>สถานะ:</b> ${safeText(s.status)}</div>
                <div><b>เวลาทำการ:</b> ${safeText(s.operating_hours, 'ไม่ระบุ')}</div>
                <div><b>ประเภทหัวชาร์จ:</b> ${chargers ? chargers : '-'}</div>
              </div>
              <div class="mt-2 flex justify-end gap-2">
                <a href="${SHOW_BASE_URL}/${s.id}/navigate" class="text-black underline">
                  นำทาง
                </a>
              </div>
            </div>`;
      }

      /* 👉 เปิดสถานี: ซูม + เปิด InfoWindow (ใช้ทั้งตอนคลิกจากลิสต์/Enter/Marker) */
      function openStation(station, zoom = 15) {
        if (!station) return;
        const marker = markersById[station.id];
        if (!marker) return;
        map.panTo(marker.getPosition());
        if (map.getZoom() < zoom) map.setZoom(zoom);
        infoWindow.setContent(infoHtml(station));
        infoWindow.open({ anchor: marker, map });
      }

      /* แปลง/เรียงลิสต์ด้วยระยะจากตำแหน่งฉัน (ถ้ามี) */
      function sortByDistance(items) {
        const origin = myOrigin || map.getCenter().toJSON();
        return items.map(s => {
          const _dist = (Number.isFinite(s.lat) && Number.isFinite(s.lng))
            ? distKm(origin, { lat: s.lat, lng: s.lng }) : null;
          const _addr = joinNonEmpty([
            s.subdistrict ? `ต.${s.subdistrict}` : '',
            s.district ? `อ.${s.district}` : '',
            s.province ? `จ.${s.province}` : '',
          ], ' ');
          return { ...s, _dist, _addr };
        }).sort((a, b) => {
          if (a._dist == null && b._dist == null) return 0;
          if (a._dist == null) return 1;
          if (b._dist == null) return -1;
          return a._dist - b._dist;
        });
      }

      /* 👉 กล่องแนะนำใต้ช่องค้นหาใน Navbar (กดแล้วซูม+เปิด InfoWindow) */
      function renderSuggest(list) {
        const box = document.getElementById('qSuggest');
        if (!box) return;
        if (!list.length) { box.classList.add('hidden'); box.innerHTML = ''; return; }

        box.innerHTML = list.slice(0, 20).map(item => `
            <button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-50 flex items-start gap-2"
                    data-id="${item.id}">
              <div class="mt-1">📍</div>
              <div class="flex-1">
                <div class="font-medium">${item.name}</div>
                <div class="text-xs text-gray-500">${item._addr || ''}</div>
                <div class="text-xs">${item._dist ? (item._dist.toFixed(1) + ' กม.') : ''}</div>
              </div>
            </button>
          `).join('');
        box.classList.remove('hidden');

        [...box.querySelectorAll('button[data-id]')].forEach(btn => {
          btn.addEventListener('click', () => {
            // e.preventDefault();               // 👉 กัน submit ฟอร์ม
            // e.stopPropagation();              // 👉 กัน event เด้งขึ้นไปปิดลิสต์ก่อนเวลา
            const id = btn.getAttribute('data-id');
            const s = allStations.find(x => String(x.id) === String(id));
            openStation(s);                      // 👉 ซูม + เปิด InfoWindow
            box.classList.add('hidden');
          });
        });
      }

      /* ===================== Map Init ===================== */
      function initMap() {
        const center = { lat: 17.1545, lng: 104.1347 };
        map = new google.maps.Map(document.getElementById('map'), {
          center, zoom: 11, mapTypeControl: false, fullscreenControl: true,
        });
        infoWindow = new google.maps.InfoWindow();

        // ตำแหน่งฉัน (optional)
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(pos => {
            myOrigin = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            myMarker = new google.maps.Marker({
              position: myOrigin, map, title: 'ตำแหน่งฉัน',
              icon: ICONS.blue, zIndex: 999
            });
          });
        }

        // โหลดสถานีทั้งหมด + วาดหมุด (หมุด “ไม่หาย”)
        fetch(API_URL, { headers: { 'Accept': 'application/json' } })
          .then(r => r.json())
          .then(raw => {
            allStations = (Array.isArray(raw) ? raw : []).map(s => ({
              id: s.id,
              name: s.name || '-',
              address: s.address || '',
              subdistrict: s.subdistrict || '',
              district: s.district || '',
              province: s.province || '',
              postcode: s.postcode || '',
              status_id: Number.isFinite(s.status_id) ? s.status_id : (s.status_id ?? null),   // 👉 ใช้ทำสี
              status: s.status || '-',
              operating_hours: s.operating_hours || '',
              chargers: Array.isArray(s.chargers) ? s.chargers : (s.chargers ? [s.chargers] : []),
              image_url: s.image_url || null,
              lat: Number(s.lat ?? s.latitude),
              lng: Number(s.lng ?? s.longitude),
            })).filter(s => Number.isFinite(s.lat) && Number.isFinite(s.lng));

            const bounds = new google.maps.LatLngBounds();
            allStations.forEach(s => {
              const marker = new google.maps.Marker({
                position: { lat: s.lat, lng: s.lng },
                map,
                title: s.name,
                icon: iconForStatus(s),                  // 👉 สีตามสถานะ (แก้ข้อ 2)
              });
              marker.addListener('click', () => openStation(s));
              markersById[s.id] = marker;
              bounds.extend(marker.getPosition());
            });

            if (allStations.length > 1) map.fitBounds(bounds);
            else if (allStations.length === 1) { map.setCenter(bounds.getCenter()); map.setZoom(14); }
          });

        /* ============ ค้นหา/Suggest + Enter ============ */
        const input = document.getElementById('q');
        const box = document.getElementById('qSuggest');

        if (input) {
          input.addEventListener('input', () => {
            const kw = input.value.trim().toLowerCase();
            const pool = kw
              ? allStations.filter(s =>
                s.name.toLowerCase().includes(kw) ||
                s.district.toLowerCase().includes(kw) ||
                s.subdistrict.toLowerCase().includes(kw) ||
                s.province.toLowerCase().includes(kw) ||
                (s.postcode && String(s.postcode).includes(kw))
              )
              : allStations;

            const sorted = sortByDistance(pool);
            renderSuggest(sorted);                 // 👉 โชว์ลิสต์เรียงใกล้→ไกล
          });

          /* 👉 กด Enter = ซูม + เปิด InfoWindow ของรายการ “แรกสุดในลิสต์แนะนำ” */
          input.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
              e.preventDefault();
              const kw = input.value.trim().toLowerCase();
              const pool = kw
                ? allStations.filter(s =>
                  s.name.toLowerCase().includes(kw) ||
                  s.district.toLowerCase().includes(kw) ||
                  s.subdistrict.toLowerCase().includes(kw) ||
                  s.province.toLowerCase().includes(kw) ||
                  (s.postcode && String(s.postcode).includes(kw))
                )
                : allStations;

              const sorted = sortByDistance(pool);
              if (sorted.length) {
                openStation(sorted[0]);            // 👉 แก้ข้อ 1
                box?.classList.add('hidden');
              }
            }
          });
        }

        // คลิกนอกกล่อง ⇒ ปิดลิสต์
        document.addEventListener('click', (e) => {
          if (!box || !input) return;
          if (!box.contains(e.target) && e.target !== input) box.classList.add('hidden');
        });
      }

      // โหลดผ่าน loader กลาง
      window.whenGoogleMapsReady ? whenGoogleMapsReady(initMap)
        : (window.initMap = initMap);
    })();
  </script>
@endpush