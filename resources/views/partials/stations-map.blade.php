{{-- resources/views/partials/stations-map.blade.php --}}
<div class="space-y-3">
  <div id="map" class="w-full rounded-md border" style="height:66vh;"></div>
  <div class="flex justify-end">
    <button id="btnMyLocation" class="px-3 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
      ตำแหน่งฉัน
    </button>
  </div>
</div>

@push('scripts')
  <script>
    (() => {
      /* =============== ตั้งความสูงให้เต็มหน้าจอ แต่ไม่ทับ Navbar =============== */
      function adjustMapHeight() {
        const nav = document.querySelector('nav');             // ถ้าใช้ Breeze/Jetstream จะเป็น <nav> อยู่แล้ว
        const wrap = document.getElementById('mapWrap');
        if (!wrap) return;
        const navH = nav ? nav.offsetHeight : 0;               // สูงของ Navbar จริง
        wrap.style.height = `calc(100vh - ${navH}px)`;        // เต็มจอ - Navbar
        wrap.style.marginTop = `${navH}px`;                     // เริ่มใต้ Navbar พอดี
      }
      window.addEventListener('load', adjustMapHeight);
      window.addEventListener('resize', adjustMapHeight);

      /* ===================== Config / State ===================== */
      const API_URL = @json(route('api.stations'));
      const PLACEHOLDER = @json(asset('images/no-image.png'));
      const SHOW_BASE_URL = @json(url('/stations'));

      let map, infoWindow, myMarker;
      let allStations = [];
      const markersById = Object.create(null);
      let myOrigin = null;
      let userFocused = false; // ป้องกัน fitBounds ทับการซูมของผู้ใช้

      /* ===================== Utils ===================== */
      const distKm = (a, b) => {
        const R = 6371, dLat = (b.lat - a.lat) * Math.PI / 180, dLng = (b.lng - a.lng) * Math.PI / 180;
        const s1 = Math.sin(dLat / 2) ** 2;
        const s2 = Math.cos(a.lat * Math.PI / 180) * Math.cos(b.lat * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
        return 2 * R * Math.asin(Math.sqrt(s1 + s2));
      };
      const safeText = (v, f = '-') => (v ?? '').toString().trim() || f;
      const joinNonEmpty = (arr, sep = ' ') => arr.filter(Boolean).join(sep);

      const ICONS = {
        green: 'https://maps.gstatic.com/mapfiles/ms2/micons/green-dot.png',
        yellow: 'https://maps.gstatic.com/mapfiles/ms2/micons/yellow-dot.png',
        red: 'https://maps.gstatic.com/mapfiles/ms2/micons/red-dot.png',
        blue: 'https://maps.gstatic.com/mapfiles/ms2/micons/blue-dot.png',
      };
      function iconForStatus(s) {
        // มี status_id => แปลตาม id ก่อน
        if (s.status_id === 1) return ICONS.green;   // พร้อมใช้งาน
        if (s.status_id === 0) return ICONS.yellow;  // รอตรวจสอบ
        if (s.status_id === 2) return ICONS.red;     // ชำรุด

        // สำรอง: ใช้ข้อความ status
        const t = (s.status || '').toString().trim().toLowerCase();
        if (/(พร้อม|available|ready)/.test(t)) return ICONS.green;
        if (/(รอ|คิว|pending|ตรวจสอบ|maintenance|ซ่อม)/.test(t)) return ICONS.yellow;
        if (/(ชำรุด|เสีย|ปิด|out\s*of\s*service|down)/.test(t)) return ICONS.red;
        return ICONS.blue;
      }
      // info รายละเอียด
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
        

        // ✅ ดึง role จาก Blade (ฝังลงใน JS)
        const userRole = @json(auth()->user()->role->name ?? 'guest');

        // ✅ เงื่อนไขแยกปุ่มตาม role
        let extraButton = '';
        if (userRole === 'admin') {
          extraButton = `<a href="/admin/stations/${s.id}/edit"
                        class="text-blue-600 underline">แก้ไข</a>`;
        } else if (userRole === 'user') {
          extraButton = `<a href="/reports/create?station_id=${s.id}"
                        class="text-amber-600 underline">แจ้งปัญหา</a>`;
        }

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
        <div class="mt-3 flex justify-between items-center text-sm font-medium">
          <a href="${SHOW_BASE_URL}/${s.id}/navigate" class="text-black underline">นำทาง</a>
          ${extraButton}
        </div>
      </div>`;
      }


      /* ===================== โฟกัส & เปิด InfoWindow ===================== */
      function openStation(station, zoom = 15) {
        if (!station) return;
        const marker = markersById[String(station.id)];
        if (!marker) return;

        map.panTo(marker.getPosition());
        if (typeof zoom === 'number' && Number.isFinite(zoom) && map.getZoom() < zoom) {
          map.setZoom(zoom);
        }
        infoWindow.setContent(infoHtml(station));
        infoWindow.open({ anchor: marker, map });
      }

      /* ===================== เรียงตามระยะ ===================== */
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

      /* ===================== Suggest ใต้ช่องค้นหา (ใน Navbar) ===================== */
      function renderSuggest(list) {
        const box = document.getElementById('qSuggest'); // ถ้ามีใน navigation.blade.php
        if (!box) return;
        if (!list.length) { box.classList.add('hidden'); box.innerHTML = ''; return; }

        box.innerHTML = list.slice(0, 20).map(item => `
                <button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-50 flex items-start gap-2" data-id="${item.id}">
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
            const id = btn.getAttribute('data-id');
            const s = allStations.find(x => String(x.id) === String(id));
            openStation(s);               // ซูมปกติ
            box.classList.add('hidden');
          });
        });
      }

      /* ===================== Map Init ===================== */
      function initMap() {
        const el = document.getElementById('map');
        if (!el) return;

        map = new google.maps.Map(el, {
          center: { lat: 17.1545, lng: 104.1347 },
          zoom: 11,
          mapTypeControl: false,
          fullscreenControl: true,
        });
        infoWindow = new google.maps.InfoWindow();

        // ตำแหน่งฉัน (ไม่บังคับ)
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(pos => {
            myOrigin = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            myMarker = new google.maps.Marker({
              position: myOrigin,
              map,
              title: 'ตำแหน่งฉัน',
              icon: ICONS.blue,
              zIndex: 999
            });
            // ไม่ซูมอัตโนมัติ ให้ผู้ใช้กดปุ่ม "ตำแหน่งฉัน" เอง
          });
        }

        // โหลดสถานี + วางหมุด (หมุดไม่หาย)
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
              status_id: Number.isFinite(s.status_id) ? s.status_id : (s.status_id ?? null),
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
                icon: iconForStatus(s),       // ✅ สีตามสถานะ
              });
              marker.addListener('click', () => openStation(s, null)); // คลิกหมุด = เปิด info แต่ไม่บังคับซูม
              markersById[String(s.id)] = marker;
              bounds.extend(marker.getPosition());
            });

            if (!userFocused) {
              if (allStations.length > 1) map.fitBounds(bounds);
              else if (allStations.length === 1) { map.setCenter(bounds.getCenter()); map.setZoom(14); }
            }
          });

        // เชื่อมกับช่องค้นหาใน Navbar (ถ้ามี)
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
            renderSuggest(sortByDistance(pool));
          });

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
                openStation(sorted[0]);      // Enter = ซูมไปตัวแรกที่ใกล้สุด
                box?.classList.add('hidden');
              }
            }
          });

          // คลิกนอกกล่อง ⇒ ปิดลิสต์
          document.addEventListener('click', (e) => {
            if (!box) return;
            if (!box.contains(e.target) && e.target !== input) box.classList.add('hidden');
          });
        }

        // ปุ่มซูมไปยังตำแหน่งฉัน
        const btnMy = document.getElementById('btnMyLocation');
        function focusMyLocation() {
          userFocused = true;
          const doFocus = () => {
            // ใช้ setTimeout เพื่อให้ UI อัปเดตก่อน แล้วค่อยซูม
            setTimeout(() => {
              map.setCenter(myOrigin);
              map.setZoom(17);
              if (myMarker) {
                infoWindow.setContent('<div style="text-align:center;min-width:120px">📍<br>ตำแหน่งฉัน</div>');
                infoWindow.open({ anchor: myMarker, map });
              }
            }, 0);
          };

          if (myOrigin) { doFocus(); return; }
          if (!navigator.geolocation) { alert('เบราว์เซอร์ไม่รองรับการระบุตำแหน่ง'); return; }
          navigator.geolocation.getCurrentPosition(pos => {
            myOrigin = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            if (!myMarker) {
              myMarker = new google.maps.Marker({ position: myOrigin, map, title: 'ตำแหน่งฉัน', icon: ICONS.blue, zIndex: 999 });
            } else {
              myMarker.setPosition(myOrigin);
            }
            doFocus();
          }, () => alert('ไม่สามารถขอตำแหน่งได้ โปรดอนุญาตการเข้าถึงตำแหน่ง'));
        }

        btnMy?.addEventListener('click', () => {
          focusMyLocation();
        });

      }

      // ใช้ loader กลาง
      window.whenGoogleMapsReady ? whenGoogleMapsReady(initMap)
        : (window.initMap = initMap);
    })();
  </script>
@endpush