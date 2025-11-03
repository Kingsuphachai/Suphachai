<x-app-layout>
  {{-- resources/views/partials/stations-map.blade.php --}}
  <div id="mapWrap" data-skip-nav-offset="true" data-gap="0" class="relative w-full rounded-md border overflow-hidden"
    style="min-height:70vh;">
    <div id="map" class="absolute inset-0"></div>
  </div>

  <style>
    .map-infobox-actions {
      margin-top: 14px;
      display: flex;
      gap: 10px;
    }

    .map-infobox-btn {
      flex: 1;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 9px 12px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: 600;
      border: 1px solid transparent;
      text-decoration: none;
      cursor: pointer;
      transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease, color 0.15s ease;
    }

    .map-infobox-btn:focus {
      outline: 2px solid #6366f1;
      outline-offset: 2px;
    }

    .map-infobox-btn--primary {
      background: #7c3aed;
      color: #fff;
      border-color: #6d28d9;
      box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
    }

    .map-infobox-btn--primary:hover,
    .map-infobox-btn--primary:focus-visible {
      background: #6d28d9;
      transform: translateY(-1px);
      box-shadow: 0 6px 14px rgba(124, 58, 237, 0.35);
    }
  </style>

  @push('scripts')
    <script>
      (() => {
        // 🎨 โทนม่วงสบายตา
        const EV_PURPLE_STYLE = [
          { elementType: "geometry", stylers: [{ color: "#f3f4f6" }] },
          { elementType: "labels.text.fill", stylers: [{ color: "#4c1d95" }] },
          { elementType: "labels.text.stroke", stylers: [{ color: "#ffffff" }] },
          { featureType: "poi", stylers: [{ visibility: "off" }] },
          { featureType: "road", elementType: "geometry", stylers: [{ color: "#e5e7eb" }] },
          { featureType: "road.arterial", elementType: "geometry", stylers: [{ color: "#ddd6fe" }] },
          { featureType: "road.highway", elementType: "geometry", stylers: [{ color: "#c4b5fd" }] },
          { featureType: "road.local", elementType: "geometry", stylers: [{ color: "#ede9fe" }] },
          { featureType: "water", elementType: "geometry", stylers: [{ color: "#dbeafe" }] },
          { featureType: "administrative", elementType: "labels.text.fill", stylers: [{ color: "#6b21a8" }] },
        ];
        /* =============== ตั้งความสูงให้เต็มหน้าจอ แต่ไม่ทับ Navbar =============== */
        function adjustMapHeight() {
          const wrap = document.getElementById('mapWrap');
          if (!wrap) return;
          const nav = document.querySelector('nav');             // ถ้าใช้ Breeze/Jetstream จะเป็น <nav> อยู่แล้ว
          const navH = nav ? nav.offsetHeight : 0;               // สูงของ Navbar จริง
          const navPos = nav ? window.getComputedStyle(nav).position : '';
          const isOverlayNav = navPos === 'fixed' || navPos === 'sticky';
          const gap = Number(wrap.dataset.gap ?? 10);             // เว้นระยะเหนือแผนที่ ~10px
          const skipNavOffset = wrap.dataset.skipNavOffset === 'true';
          const marginTop = (!skipNavOffset && isOverlayNav) ? navH + gap : gap;

          wrap.style.height = `calc(100vh - ${marginTop}px)`;    // เต็มจอ - ส่วนที่ทับ
          wrap.style.marginTop = `${marginTop}px`;               // ให้เว้นระยะคงที่
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
        function statusDisplay(s) {
          const raw = safeText(s.status, '').trim();
          const lower = raw.toLowerCase();
          const id = Number.isFinite(Number(s.status_id)) ? Number(s.status_id) : null;
          if (id === 1 || /(พร้อม|available|ready)/.test(lower)) return 'พร้อมใช้งาน 🟢';
          if (id === 2 || /(ชำรุด|เสีย|out\s*of\s*service|down)/.test(lower)) return 'ชำรุด 🔴';
          if (id === 0 || /(รอ|pending|ตรวจสอบ|maintenance|คิว)/.test(lower)) return 'รอตรวจสอบ 🟡 ';
          if (raw) return `⚪ ${raw}`;
          return '⚪ ไม่ระบุ';
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
          const statusLabel = statusDisplay(s);

          return `
          <div style="min-width:260px;max-width:320px">
            <div style="margin:-8px -8px 8px -8px;">
              <img src="${imgSrc}" alt="${s.name ?? ''}"
                   style="width:100%;height:150px;object-fit:cover;border-radius:8px 8px 0 0;" loading="lazy">
            </div>
            <div style="font-weight:700;font-size:15px">${safeText(s.name)}</div>
            <div style="font-size:13px;color:#374151;margin-top:2px">${addressLine || '-'}</div>
            <div style="font-size:13px;margin-top:6px">
              <div><b>สถานะ:</b> ${statusLabel}</div>
              <div><b>เวลาทำการ:</b> ${safeText(s.operating_hours, 'ไม่ระบุ')}</div>
              <div><b>ประเภทหัวชาร์จ:</b> ${chargers ? chargers : '-'}</div>
            </div>
            <div class="map-infobox-actions">
              <button type="button" class="map-infobox-btn map-infobox-btn--primary js-navigate-to"
                data-navigation-url="${SHOW_BASE_URL}/${s.id}/navigate">นำทาง</button>
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

          const statusInfo = (item) => {
            const id = Number(item.status_id);
            const raw = safeText(item.status, '').toLowerCase();
            if (id === 1 || /(พร้อม|available|ready)/.test(raw)) {
              return {
                label: 'พร้อมใช้งาน',
                pillBg: '#bbf7d0',
                pillText: '#047857',
                pillBorder: '#86efac'
              };
            }
            if (id === 2 || /(ชำรุด|เสีย|out\s*of\s*service|down)/.test(raw)) {
              return {
                label: 'ชำรุด',
                pillBg: '#fecaca',
                pillText: '#b91c1c',
                pillBorder: '#fca5a5'
              };
            }
            return null;
          };

          box.innerHTML = list.slice(0, 6).map(item => {
            const status = statusInfo(item);
            const statusHtml = status
              ? `<span class="inline-flex items-center px-2 py-[2px] rounded-full text-[10px] font-medium"
                         style="background-color:${status.pillBg};color:${status.pillText};border:1px solid ${status.pillBorder};">
                      ${status.label}
                   </span>`
              : '';
            return `
                    <button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-50 flex items-start gap-3" data-id="${item.id}">
                      <div class="mt-1 text-base">📍</div>
                      <div class="flex-1">
                        <div class="font-medium">${item.name}</div>
                        <div class="text-xs text-gray-500">${item._addr || ''}</div>
                        <div class="mt-1 text-xs text-gray-600">${item._dist ? (item._dist.toFixed(1) + ' กม.') : ''}</div>
                        ${statusHtml ? `<div class="mt-1">${statusHtml}</div>` : ''}
                      </div>
                    </button>
                  `;
          }).join('');
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
            styles: EV_PURPLE_STYLE,
          });
          infoWindow = new google.maps.InfoWindow();
          infoWindow.addListener('domready', () => {
            document.querySelectorAll('.js-navigate-to').forEach(btn => {
              if (btn.dataset.boundNavigate === 'true') return;
              btn.dataset.boundNavigate = 'true';
              btn.addEventListener('click', () => {
                const url = btn.getAttribute('data-navigation-url');
                if (url) window.open(url, '_blank');
              });
            });
          });

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

              if (document.activeElement === input) {
                showSuggest();
              }

              if (!userFocused) {
                if (allStations.length > 1) map.fitBounds(bounds);
                else if (allStations.length === 1) { map.setCenter(bounds.getCenter()); map.setZoom(14); }
              }
            });

          // เชื่อมกับช่องค้นหาใน Navbar (ถ้ามี)
          const input = document.getElementById('q');
          const box = document.getElementById('qSuggest');

          const filteredStations = () => {
            if (!input) return allStations;
            const kw = input.value.trim().toLowerCase();
            return kw
              ? allStations.filter(s =>
                s.name.toLowerCase().includes(kw) ||
                s.district.toLowerCase().includes(kw) ||
                s.subdistrict.toLowerCase().includes(kw) ||
                s.province.toLowerCase().includes(kw) ||
                (s.postcode && String(s.postcode).includes(kw))
              )
              : allStations;
          };

          const showSuggest = () => {
            if (!input || !allStations.length) return;
            renderSuggest(sortByDistance(filteredStations()));
          };

          if (input) {

            input.addEventListener('input', showSuggest);
            input.addEventListener('focus', showSuggest);
            input.addEventListener('click', showSuggest);

            input.addEventListener('keydown', e => {
              if (e.key === 'Enter') {
                e.preventDefault();
                const sorted = sortByDistance(filteredStations());
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

</x-app-layout>