<x-app-layout>
    @php
        $requestErrorFields = [
            'name',
            'address',
            'district_id',
            'subdistrict_id',
            'operating_hours',
            'latitude',
            'longitude',
            'charger_type_ids',
            'charger_type_ids.*',
            'image',
        ];
        $reportErrorFields = ['station_id', 'type', 'message'];

        $requestModalShouldOpen = false;
        foreach ($requestErrorFields as $field) {
            if ($errors->has($field)) {
                $requestModalShouldOpen = true;
                break;
            }
        }
        if (old('name')) {
            $requestModalShouldOpen = true;
        }

        $reportModalShouldOpen = false;
        foreach ($reportErrorFields as $field) {
            if ($errors->has($field)) {
                $reportModalShouldOpen = true;
                break;
            }
        }
        $preselectedReportStation = old('station_id') ?? request('station_id');
        if ($preselectedReportStation) {
            $reportModalShouldOpen = true;
        }
        $currentReportStationSelection = old('station_id', $preselectedReportStation);
    @endphp

    {{-- ส่วนของแผนที่ --}}
    {{-- resources/views/partials/stations-map.blade.php --}}
    <div id="mapWrap" data-skip-nav-offset="true" data-gap="0" class="relative w-full rounded-md border overflow-hidden"
        style="min-height:70vh;">
        <div id="map" class="absolute inset-0"></div>
    </div>

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
                    if (id === 2 || /(ชำรุด|เสีย|out\s*of\s*service|down)/.test(lower)) return 'ชำรุด 🔴 ';
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


                    // ✅ ดึง role จาก Blade (ฝังลงใน JS)
                    const userRole = @json(auth()->user()->role->name ?? 'guest');

                    // ✅ Action buttons
                    const actions = (() => {
                        const navigateUrl = `${SHOW_BASE_URL}/${s.id}/navigate`;
                        const button = (label, classes = '', attrs = '') => `
                                <button type="button" class="map-infobox-btn ${classes}" ${attrs}>
                                    ${label}
                                </button>
                                `;
                        if (userRole === 'admin') {
                            const editButton = `<a href="/admin/stations/${s.id}/edit"
                                    class="map-infobox-btn map-infobox-btn--primary"
                                    data-admin-edit="true">แก้ไข</a>`;
                            const navigateButton = button('นำทาง', 'map-infobox-btn--secondary js-navigate-to', `data-navigation-url="${navigateUrl}"`);
                            return `${editButton}${navigateButton}`;
                        }
                        if (userRole === 'user') {
                            const reportButton = button('แจ้งปัญหา', 'map-infobox-btn--primary js-open-report-modal', `data-station-id="${s.id}"`);
                            const navigateButton = button('นำทาง', 'map-infobox-btn--secondary js-navigate-to', `data-navigation-url="${navigateUrl}"`);
                            return `${reportButton}${navigateButton}`;
                        }
                        return button('นำทาง', 'map-infobox-btn--primary js-navigate-to', `data-navigation-url="${navigateUrl}"`);
                    })();

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
                                        ${actions}
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
                                label: 'พร้อมใช้งาน 🟢',
                                pillBg: '#bbf7d0',
                                pillText: '#047857',
                                pillBorder: '#86efac'
                            };
                        }
                        if (id === 2 || /(ชำรุด|เสีย|out\s*of\s*service|down)/.test(raw)) {
                            return {
                                label: 'ชำรุด 🔴',
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
                        document.querySelectorAll('.js-open-report-modal').forEach(btn => {
                            if (btn.dataset.bound === 'true') return;
                            btn.dataset.bound = 'true';
                            btn.addEventListener('click', () => {
                                const stationId = btn.getAttribute('data-station-id');
                                if (window.ev && typeof window.ev.openReportModal === 'function') {
                                    window.ev.openReportModal(stationId);
                                }
                            });
                        });
                        document.querySelectorAll('.js-navigate-to').forEach(btn => {
                            if (btn.dataset.boundNavigate === 'true') return;
                            btn.dataset.boundNavigate = 'true';
                            btn.addEventListener('click', () => {
                                const url = btn.getAttribute('data-navigation-url');
                                if (url) {
                                    window.open(url, '_blank');
                                }
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

                    const myLocationTriggers = document.querySelectorAll('[data-my-location-trigger]');
                    myLocationTriggers.forEach(btn => {
                        btn.addEventListener('click', () => focusMyLocation());
                    });
                    window.ev = window.ev || {};
                    window.ev.panToMe = focusMyLocation;

                }

                // ใช้ loader กลาง
                window.whenGoogleMapsReady ? whenGoogleMapsReady(initMap)
                    : (window.initMap = initMap);
            })();
        </script>
    @endpush



    <style>
        /* === โหมดพื้นฐาน: ล่าง-กึ่งกลางจอ === */
        .floating-actions {
            position: fixed;
            inset: auto 0 14px 0;
            /* left:0; right:0; bottom:14px */
            z-index: 99999;
            display: flex;
            justify-content: center;
            /* กึ่งกลางแนวนอน */
            pointer-events: none;
            /* ให้คลิกผ่าน wrapper ได้ */
            padding: 0 12px;
        }

        .floating-actions__inner {
            pointer-events: auto;
            /* รับคลิกเฉพาะกล่องด้านใน */
            background: #7c3aed;
            color: #111827;
            padding: 12px;
            border-radius: 20px;
            box-shadow: 0 10px 28px rgba(124, 58, 237, .22);
            width: min(840px, 96vw);
            /* กว้างพอดีและกึ่งกลาง */
        }

        .floating-actions__list {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            /* 6 ปุ่มเรียงแนวนอน */
            gap: 10px;
        }

        .floating-actions__item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px 8px;
            background: #fff;
            border: 1px solid #7c3aed;
            border-radius: 14px;
            text-decoration: none;
            font-size: 12px;
            box-shadow: 0 6px 18px rgba(124, 58, 237, .14);
            transition: transform .2s, box-shadow .2s, background .2s;
        }

        .floating-actions__item:hover {
            transform: translateY(-2px);
            background: #f9f5ff;
        }

        /* จอแคบมาก ให้แตกเป็น 3x2 แต่ยังอยู่ล่าง-กึ่งกลาง */
        @media (max-width: 560px) {
            .floating-actions__list {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        /* === โหมด Desktop: ขวากลางจอ (แนวตั้ง) === */
        @media (min-width: 1024px) {
            .floating-actions {
                top: 35%;
                right: 12px;
                left: auto;
                bottom: auto;
                transform: translateY(-50%);
                /* จัดกึ่งกลางแนวตั้ง */
                padding: 0;
                justify-content: flex-end;
                /* ชิดขวา */
            }

            .floating-actions__inner {
                width: 100px;
                border-radius: 24px;
                padding: 8px 6px;
            }

            .floating-actions__list {
                display: flex;
                flex-direction: column;
                /* เรียงแนวตั้ง */
                gap: 5px;
            }
        }

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

        .map-infobox-btn--secondary {
            background: #f3f4f6;
            color: #1f2937;
            border-color: #d1d5db;
        }

        .map-infobox-btn--secondary:hover,
        .map-infobox-btn--secondary:focus-visible {
            background: #e5e7eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(17, 24, 39, 0.15);
        }

        body.modal-open {
            overflow: hidden;
        }

        .ev-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100000;
            padding: 24px 16px;
        }

        .ev-modal.is-open {
            display: flex;
        }

        .ev-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(17, 24, 39, 0.45);
            backdrop-filter: blur(4px);
        }

        .ev-modal__panel {
            position: relative;
            width: min(640px, 100%);
            max-height: 90vh;
            background: #ffffff;
            border-radius: 24px;
            padding: 24px 24px 28px;
            box-shadow: 0 24px 60px rgba(17, 24, 39, 0.25);
            overflow: hidden;
        }

        .ev-modal__title {
            font-size: 20px;
            font-weight: 600;
            color: #111827;
            margin: 0;
            padding-right: 36px;
        }

        .ev-modal__content {
            margin-top: 16px;
            overflow-y: auto;
            max-height: calc(90vh - 96px);
            padding-right: 4px;
        }

        .ev-modal__close {
            position: absolute;
            top: 14px;
            right: 14px;
            border: none;
            background: transparent;
            font-size: 20px;
            line-height: 1;
            cursor: pointer;
            color: #4b5563;
        }

        .ev-modal__close:hover {
            color: #1f2937;
        }

        .ev-modal__alert {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 14px;
            font-size: 13px;
            line-height: 1.5;
        }

        .ev-modal__alert--error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .ev-modal__form-group {
            margin-bottom: 14px;
        }

        .ev-modal__form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            color: #1f2937;
        }

        .ev-modal__form-group input,
        .ev-modal__form-group textarea,
        .ev-modal__form-group select {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
        }

        .ev-modal__form-group textarea {
            resize: vertical;
        }

        .ev-modal__checkbox-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 16px;
        }

        .ev-modal__actions {
            display: flex;
            gap: 12px;
            margin-top: 18px;
        }

        .ev-modal__primary {
            background: #7c3aed;
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 10px 18px;
            font-weight: 600;
            cursor: pointer;
        }

        .ev-modal__primary:hover {
            background: #6d28d9;
        }

        .ev-modal__secondary {
            border: 1px solid #d1d5db;
            background: #fff;
            color: #1f2937;
            border-radius: 12px;
            padding: 10px 18px;
            font-weight: 500;
            cursor: pointer;
        }

        .ev-modal__secondary:hover {
            background: #f3f4f6;
        }

        .ev-modal__error-text {
            margin-top: 4px;
            font-size: 12px;
            color: #b91c1c;
        }

        .ev-chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .ev-chip-option {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border-radius: 999px;
            border: 1px solid #d8d5f3;
            background: #f5f3ff;
            font-size: 13px;
            font-weight: 500;
            color: #4c1d95;
            cursor: pointer;
            transition: all .18s ease-in-out;
        }

        .ev-chip-option:hover {
            border-color: #7c3aed;
            background: #ede9fe;
        }

        .ev-chip-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .ev-chip-option input:checked+.ev-chip-bg {
            opacity: 1;
        }

        .ev-chip-option input:checked~span {
            color: #fff;
        }

        .ev-chip-option .ev-chip-bg {
            position: absolute;
            inset: -1px;
            background: linear-gradient(135deg, #7c3aed, #9d4edd);
            border-radius: inherit;
            opacity: 0;
            transition: opacity .18s ease-in-out;
            z-index: 0;
        }

        .ev-chip-option span {
            position: relative;
            z-index: 1;
        }
    </style>

    {{-- 🔻 แถบล่างแนวยาว (5 ปุ่ม: แผนที่, จัดการสถานี, จัดการผู้ใช้, รายงานปัญหา, แจ้งเตือน) --}}
    <div class="floating-actions">
        <div class="floating-actions__inner">
            <div class="floating-actions__list">

                {{-- 📍 ตำแหน่งฉัน --}}
                <button type="button" id="btnMyLocationShortcut" data-my-location-trigger
                    class="floating-actions__item">
                    <div class="floating-actions__icon">📍</div>
                    <div class="floating-actions__label">ตำแหน่งฉัน</div>
                </button>

                {{-- ➕ ขอเพิ่มสถานีชาร์จ --}}
                <button type="button" class="floating-actions__item" data-modal-trigger="requestModal">
                    <div class="floating-actions__icon">➕</div>
                    <div class="floating-actions__label">ขอเพิ่มสถานีชาร์จ</div>
                </button>

                {{-- ⚠️ แจ้งปัญหาสถานี --}}
                <button type="button" class="floating-actions__item" data-modal-trigger="reportModal">
                    <div class="floating-actions__icon">⚠️</div>
                    <div class="floating-actions__label">แจ้งปัญหาสถานี</div>
                </button>

            </div>
        </div>
    </div>

    @php
        $hasRequestErrors = false;
        foreach ($requestErrorFields as $field) {
            if ($errors->has($field)) {
                $hasRequestErrors = true;
                break;
            }
        }
        $hasReportErrors = false;
        foreach ($reportErrorFields as $field) {
            if ($errors->has($field)) {
                $hasReportErrors = true;
                break;
            }
        }
    @endphp

    {{-- Modal: ขอเพิ่มสถานีชาร์จ --}}
    <div id="requestModal" class="ev-modal" aria-hidden="true">
        <div class="ev-modal__backdrop" data-modal-close></div>
        <div class="ev-modal__panel" role="dialog" aria-modal="true" aria-labelledby="requestModalTitle">
            <button type="button" class="ev-modal__close" data-modal-close aria-label="ปิด">×</button>
            <h3 id="requestModalTitle" class="ev-modal__title">ขอเพิ่มสถานีชาร์จ</h3>
            <div class="ev-modal__content">
                @if ($hasRequestErrors)
                    <div class="ev-modal__alert ev-modal__alert--error">
                        กรุณาตรวจสอบข้อมูลให้ครบถ้วน แล้วลองอีกครั้ง
                    </div>
                @endif
                <form method="POST" action="{{ route('user.request.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="ev-modal__form-group">
                        <label for="request_name">ชื่อสถานี <span class="text-red-500">*</span></label>
                        <input id="request_name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <p class="ev-modal__error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="ev-modal__form-group">
                        <label for="request_address">ที่อยู่</label>
                        <textarea id="request_address" name="address" rows="2">{{ old('address') }}</textarea>
                    </div>
                    <div class="ev-modal__form-group">
                        <label for="request_district_id">อำเภอ <span class="text-red-500">*</span></label>
                        <select id="request_district_id" name="district_id" required>
                            <option value="">-- เลือกอำเภอ --</option>
                            @foreach ($districts as $district)
                                <option value="{{ $district->id }}" @selected(old('district_id') == $district->id)>
                                    {{ $district->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('district_id')
                            <p class="ev-modal__error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="ev-modal__form-group">
                        <label for="request_subdistrict_id">ตำบล</label>
                        <select id="request_subdistrict_id" name="subdistrict_id"
                            data-selected="{{ old('subdistrict_id') }}">
                            <option value="">-- เลือกตำบล --</option>
                            @foreach ($subdistricts as $subdistrict)
                                <option value="{{ $subdistrict->id }}" data-district="{{ $subdistrict->district_id }}"
                                    @selected(old('subdistrict_id') == $subdistrict->id)>
                                    {{ $subdistrict->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subdistrict_id')
                            <p class="ev-modal__error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="ev-modal__form-group">
                        <label for="request_operating_hours">เวลาทำการ</label>
                        <input id="request_operating_hours" name="operating_hours" value="{{ old('operating_hours') }}"
                            placeholder="เช่น 08:00-20:00">
                    </div>
                    <div class="ev-modal__form-group">
                        <label>พิกัด Latitude </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input id="request_latitude" name="latitude" value="{{ old('latitude') }}"
                                placeholder="Latitude">
                        </div>
                        @error('latitude')
                            <p class="ev-modal__error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="ev-modal__form-group">
                        <label>พิกัด Longitude</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input id="request_longitude" name="longitude" value="{{ old('longitude') }}"
                                placeholder="Longitude">
                        </div>
                        @error('longitude')
                            <p class="ev-modal__error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="ev-modal__form-group">
                        <label>ประเภทหัวชาร์จ</label>
                        <div class="ev-chip-group">
                            @foreach ($chargerTypes as $charger)
                                <label class="ev-chip-option">
                                    <input type="checkbox" name="charger_type_ids[]" value="{{ $charger->id }}" {{ in_array($charger->id, old('charger_type_ids', [])) ? 'checked' : '' }}>
                                    <div class="ev-chip-bg"></div>
                                    <span>{{ $charger->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @if ($errors->has('charger_type_ids') || $errors->has('charger_type_ids.*'))
                            <p class="ev-modal__error-text">
                                {{ $errors->first('charger_type_ids') ?? $errors->first('charger_type_ids.*') }}
                            </p>
                        @endif
                    </div>
                    <div class="ev-modal__form-group">
                        <label for="request_image">รูปสถานี (ไม่บังคับ)</label>
                        <input id="request_image" type="file" name="image" accept="image/*">
                        @error('image')
                            <p class="ev-modal__error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="ev-modal__actions">
                        <button type="submit" class="ev-modal__primary">ส่งคำขอ</button>
                        <button type="button" class="ev-modal__secondary" data-modal-close>ยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: แจ้งปัญหาสถานี --}}
    <div id="reportModal" class="ev-modal" aria-hidden="true">
        <div class="ev-modal__backdrop" data-modal-close></div>
        <div class="ev-modal__panel" role="dialog" aria-modal="true" aria-labelledby="reportModalTitle">
            <button type="button" class="ev-modal__close" data-modal-close aria-label="ปิด">×</button>
            <h3 id="reportModalTitle" class="ev-modal__title">แจ้งปัญหาสถานี</h3>
            <div class="ev-modal__content">
                @if ($hasReportErrors)
                    <div class="ev-modal__alert ev-modal__alert--error">
                        กรุณากรอกข้อมูลแจ้งปัญหาให้ครบถ้วน
                    </div>
                @endif
                <form method="POST" action="{{ route('user.reports.store') }}">
                    @csrf
                    <div class="ev-modal__form-group">
                        <label for="report_station_id">สถานีที่มีปัญหา <span class="text-red-500">*</span></label>
                        <select id="report_station_id" name="station_id" required>
                            <option value="">-- เลือกสถานี --</option>
                            @foreach (($stations ?? collect())->where('status_id', 1) as $station)
                                <option value="{{ $station->id }}" @selected($currentReportStationSelection == $station->id)>
                                    {{ $station->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('station_id')
                            <p class="ev-modal__error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="ev-modal__form-group">
                        <label for="report_type">ประเภทปัญหา <span class="text-red-500">*</span></label>
                        <select id="report_type" name="type" required>
                            <option value="">-- เลือกประเภท --</option>
                            @foreach ($reportTypes as $typeValue => $typeLabel)
                                <option value="{{ $typeValue }}" @selected(old('type') == $typeValue)>{{ $typeLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')
                            <p class="ev-modal__error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="ev-modal__form-group">
                        <label for="report_message">รายละเอียด <span class="text-red-500">*</span></label>
                        <textarea id="report_message" name="message" rows="5" required
                            placeholder="อธิบายปัญหาที่พบ">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="ev-modal__error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="ev-modal__actions">
                        <button type="submit" class="ev-modal__primary">ส่งรายงาน</button>
                        <button type="button" class="ev-modal__secondary" data-modal-close>ยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const body = document.body;
                const openStack = [];

                function getModal(id) {
                    if (!id) return null;
                    return document.getElementById(id);
                }

                function openModal(id) {
                    const modal = getModal(id);
                    if (!modal) return;
                    if (!openStack.includes(id)) {
                        openStack.push(id);
                    }
                    modal.classList.add('is-open');
                    body.classList.add('modal-open');
                }

                function closeModal(id) {
                    const modal = getModal(id);
                    if (!modal) return;
                    modal.classList.remove('is-open');
                    const idx = openStack.lastIndexOf(id);
                    if (idx !== -1) {
                        openStack.splice(idx, 1);
                    }
                    if (!openStack.length) {
                        body.classList.remove('modal-open');
                    }
                }

                document.addEventListener('click', (event) => {
                    const trigger = event.target.closest('[data-modal-trigger]');
                    if (trigger) {
                        event.preventDefault();
                        openModal(trigger.dataset.modalTrigger);
                        return;
                    }
                    const closer = event.target.closest('[data-modal-close]');
                    if (closer) {
                        event.preventDefault();
                        const hostModal = closer.closest('.ev-modal');
                        if (hostModal && hostModal.id) {
                            closeModal(hostModal.id);
                        }
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && openStack.length) {
                        const topModalId = openStack[openStack.length - 1];
                        closeModal(topModalId);
                    }
                });

                const requestDistrictSelect = document.getElementById('request_district_id');
                const requestSubdistrictSelect = document.getElementById('request_subdistrict_id');
                if (requestDistrictSelect && requestSubdistrictSelect) {
                    const placeholder = requestSubdistrictSelect.querySelector('option[value=""]');
                    const placeholderHtml = placeholder ? placeholder.outerHTML : '<option value="">-- เลือกตำบล --</option>';
                    const optionPool = Array.from(requestSubdistrictSelect.querySelectorAll('option[data-district]'));

                    const renderSubdistricts = (districtId, preselect = '') => {
                        requestSubdistrictSelect.innerHTML = placeholderHtml;
                        optionPool.forEach(opt => {
                            if (!districtId || opt.dataset.district === districtId) {
                                const clone = opt.cloneNode(true);
                                if (preselect && clone.value === preselect) {
                                    clone.selected = true;
                                }
                                requestSubdistrictSelect.appendChild(clone);
                            }
                        });
                    };

                    renderSubdistricts(requestDistrictSelect.value, requestSubdistrictSelect.dataset.selected || '');

                    requestDistrictSelect.addEventListener('change', () => {
                        renderSubdistricts(requestDistrictSelect.value);
                    });
                }

                const reportStationSelect = document.getElementById('report_station_id');
                const setReportStation = (stationId) => {
                    if (!reportStationSelect) return;
                    const value = stationId ? String(stationId) : '';
                    reportStationSelect.value = value;
                };

                window.ev = window.ev || {};
                window.ev.openReportModal = (stationId) => {
                    if (stationId) {
                        setReportStation(stationId);
                    }
                    openModal('reportModal');
                };
                window.ev.openRequestModal = () => openModal('requestModal');

                const shouldOpenRequest = @json($requestModalShouldOpen);
                const shouldOpenReport = @json($reportModalShouldOpen);
                const initialReportStation = @json($currentReportStationSelection);

                if (initialReportStation) {
                    setReportStation(initialReportStation);
                }
                if (shouldOpenRequest) {
                    openModal('requestModal');
                }
                if (shouldOpenReport) {
                    openModal('reportModal');
                }
            });
        </script>
    @endpush

</x-app-layout>