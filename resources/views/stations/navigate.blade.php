{{-- resources/views/stations/navigate.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800">
      นำทางไป: {{ $station->name }}
    </h2>
  </x-slot>

  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow sm:rounded-lg p-4 space-y-3">

        {{-- แถบบน (Sticky) : สรุปเส้นทาง / ขั้นตอนปัจจุบัน / ปุ่มกลับ --}}
        <div class="sticky top-2 z-10">
          <div class="mb-3 grid grid-cols-1 md:grid-cols-3 gap-3 backdrop-blur-sm bg-white/60 rounded-xl p-2">

            {{-- ซ้าย: สรุปเส้นทาง --}}
            <div class="rounded-xl border bg-white p-4 shadow-sm">
              <div class="text-base font-bold text-slate-800">สรุปเส้นทาง</div>
              <div class="mt-2 h-px bg-slate-200"></div>
              <div class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-sm text-slate-700">
                <div>ระยะทาง: <span id="sumDist">-</span></div>
                <div>เวลา: <span id="sumDur">-</span></div>
              </div>
            </div>

            {{-- กลาง: ขั้นตอนปัจจุบัน --}}
            <div class="rounded-xl border bg-white p-4 shadow-sm">
              <div id="navStepTitle" class="text-base font-bold text-slate-800">ขั้นตอนนำทาง</div>
              <div class="mt-2 h-px bg-slate-200"></div>
              <div class="mt-2 flex items-start gap-3">
                <div id="navStepIcon" class="text-2xl leading-none">⬆️</div>
                <div class="min-w-0">
                  <div id="navInstructionText"
                       class="text-sm font-semibold text-slate-700 truncate">
                    กำลังคำนวณเส้นทาง...
                  </div>
                  <div id="navInstructionMeta"
                       class="text-xs text-slate-500 truncate"></div>
                </div>
              </div>
            </div>

            {{-- ขวา: ปุ่มกลับ --}}
            <div class="rounded-xl border bg-white p-4 shadow-sm flex items-center justify-end">
              <button id="btnBackTop"
                class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2
                       text-indigo-700 font-medium hover:bg-indigo-100 active:scale-[.99] transition">
                ← กลับหน้าสถานี
              </button>
            </div>

          </div>
        </div>

        {{-- แผนที่ --}}
        <div id="navMap" class="w-full rounded border" style="height:70vh;"></div>

      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    // ======================= 🎨 โทนสีแผนที่ =======================
    const EV_PURPLE_STYLE = [
      { elementType: "geometry", stylers: [{ color: "#f3f4f6" }] },
      { elementType: "labels.text.fill", stylers: [{ color: "#4c1d95" }] },
      { elementType: "labels.text.stroke", stylers: [{ color: "#ffffff" }] },
      { featureType: "poi", stylers: [{ visibility: "off" }] },
      { featureType: "road", elementType: "geometry", stylers: [{ color: "#e5e7eb" }] },
      { featureType: "road.arterial", elementType: "geometry", stylers: [{ color: "#ddd6fe" }] },
      { featureType: "road.highway", elementType: "geometry", stylers: [{ color: "#a78bfa" }] },
      { featureType: "road.local", elementType: "geometry", stylers: [{ color: "#ede9fe" }] },
      { featureType: "water", elementType: "geometry", stylers: [{ color: "#dbeafe" }] },
      { featureType: "administrative", elementType: "labels.text.fill", stylers: [{ color: "#6b21a8" }] },
    ];

    // สร้างหมุดแบบกำหนดสี
    function makePin(fill = "#7c3aed", stroke = "#4c1d95") {
      const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">
          <path d="M18 2c-6.1 0-11 4.9-11 11 0 7.5 9.2 18.1 10.3 19.3a1 1 0 0 0 1.4 0C19.8 31.1 29 20.5 29 13c0-6.1-4.9-11-11-11z"
                fill="${fill}" stroke="${stroke}" stroke-width="1.2"/>
          <circle cx="18" cy="13" r="4.2" fill="#fff"/>
        </svg>`;
      return {
        url: "data:image/svg+xml;charset=UTF-8," + encodeURIComponent(svg),
        anchor: new google.maps.Point(18, 34),
        scaledSize: new google.maps.Size(36, 36),
      };
    }

    // ======================= ⚙️ ตัวแปรหลัก =======================
    const DEST = {
      lat: parseFloat(@json($station->latitude)),
      lng: parseFloat(@json($station->longitude)),
      name: @json($station->name)
    };

    let map, dirService, dirRenderer, watchId = null;
    let originMarker = null, destMarker = null;

    let currentLeg = null;
    let currentSteps = [];
    let currentStepIndex = 0;
    let lastUserLatLng = null;

    const STEP_DISTANCE_THRESHOLD_METERS = 40; // เปลี่ยน step เมื่อเข้าใกล้ปลาย step ~40 ม.

    // UI refs
    const instructionUI = {
      icon: null, text: null, meta: null, header: null
    };

    // ======================= 📄 แสดง/อัปเดตคำสั่งทีละขั้น =======================
    function arrowFor(m) {
      const s = (m || '').toLowerCase();
      if (s.includes('uturn')) return '↩️';
      if (s.includes('left')) return '⬅️';
      if (s.includes('right')) return '➡️';
      if (s.includes('roundabout')) return '🛞';
      return '⬆️';
    }

    function updateInstructionDisplay() {
      const iconEl = instructionUI.icon;
      const titleEl = instructionUI.text;
      const metaEl  = instructionUI.meta;
      const headEl  = instructionUI.header;

      if (!currentSteps.length) {
        iconEl.textContent = '⬆️';
        titleEl.textContent = 'กำลังคำนวณเส้นทาง...';
        metaEl.textContent  = '';
        headEl.textContent  = 'ขั้นตอนนำทาง';
        return;
      }

      if (currentStepIndex >= currentSteps.length) {
        iconEl.textContent = '✅';
        titleEl.textContent = 'ถึงจุดหมายแล้ว';
        metaEl.textContent  = 'ปลายทางอยู่ข้างหน้า';
        headEl.textContent  = 'เสร็จสิ้น';
        return;
      }

      const step = currentSteps[currentStepIndex];
      iconEl.textContent   = arrowFor(step.maneuver || step.instructions);

      // แปลง HTML instruction -> ข้อความธรรมดา เพื่อใช้เป็นหัวข้อ
      const tmp = document.createElement('div');
      tmp.innerHTML = step.instructions || '';
      const plain = tmp.textContent || '';

      headEl.textContent   = (plain.split(' ')[0] || 'ขั้นตอนถัดไป'); // คำกริยาแรก
      titleEl.innerHTML    = step.instructions;
      const parts = [step.distance?.text, step.duration?.text].filter(Boolean);
      metaEl.textContent   = parts.join(' • ');
    }

    function renderSteps(leg) {
      currentLeg   = leg || null;
      currentSteps = Array.isArray(leg?.steps) ? leg.steps : [];
      currentStepIndex = 0;
      updateInstructionDisplay();
    }

    function advanceStepIfNeeded(userLatLng) {
      if (!currentSteps.length || !google?.maps?.geometry?.spherical) return;
      const spherical = google.maps.geometry.spherical;

      while (currentStepIndex < currentSteps.length) {
        const target   = currentSteps[currentStepIndex].end_location;
        const distance = spherical.computeDistanceBetween(userLatLng, target);

        if (distance > STEP_DISTANCE_THRESHOLD_METERS) {
          // แสดง "เหลือประมาณ … ม."
          const base = [currentSteps[currentStepIndex].distance?.text,
                        currentSteps[currentStepIndex].duration?.text].filter(Boolean);
          base.push(`เหลือประมาณ ${Math.max(Math.round(distance), 0)} ม.`);
          instructionUI.meta.textContent = base.join(' • ');
          break;
        }
        currentStepIndex++;
        updateInstructionDisplay();
      }
    }

    // ======================= 🗺️ วาดเส้นทาง =======================
    function drawRoute(origin) {
      dirService.route({
        origin,
        destination: { lat: DEST.lat, lng: DEST.lng },
        travelMode: google.maps.TravelMode.DRIVING,
        provideRouteAlternatives: false,
        unitSystem: google.maps.UnitSystem.METRIC, // กม./นาที
        region: 'TH'
      }, (res, status) => {
        if (status !== 'OK') {
          const hints = {
            INVALID_REQUEST: 'origin/destination ไม่ถูกต้อง',
            NOT_FOUND: 'หาตำแหน่งไม่เจอ',
            ZERO_RESULTS: 'ไม่มีเส้นทาง',
            OVER_DAILY_LIMIT: 'ยังไม่เปิด Billing/เกินโควตา',
            OVER_QUERY_LIMIT: 'เรียกบ่อยเกินไป',
            REQUEST_DENIED: 'คีย์ถูกปฏิเสธ/ยังไม่เปิด Directions',
            UNKNOWN_ERROR: 'ข้อผิดพลาดชั่วคราว'
          };
          alert(`คำนวณเส้นทางไม่สำเร็จ\nสถานะ: ${status}\n${hints[status] || ''}`);
          return;
        }

        dirRenderer.setDirections(res);
        const leg = res.routes[0].legs[0];

        // สรุป
        document.getElementById('sumDist').textContent = leg.distance?.text || '-';
        document.getElementById('sumDur').textContent  = leg.duration?.text || '-';

        renderSteps(leg);
        if (lastUserLatLng) advanceStepIfNeeded(lastUserLatLng); else updateInstructionDisplay();

        // หมุด
        if (originMarker) originMarker.setMap(null);
        if (destMarker)   destMarker.setMap(null);

        // เริ่มต้น: น้ำเงิน
        originMarker = new google.maps.Marker({
          position: leg.start_location,
          map,
          title: "จุดเริ่มต้น",
          icon: makePin("#2563eb", "#1e40af")
        });

        // ปลายทาง: แดง
        destMarker = new google.maps.Marker({
          position: leg.end_location,
          map,
          title: DEST.name,
          icon: makePin("#ef4444", "#b91c1c")
        });
      });
    }

    // ======================= 📍 ติดตามตำแหน่งผู้ใช้ =======================
    function handlePositionUpdate(position) {
      const userLatLng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
      lastUserLatLng = userLatLng;
      map.panTo(userLatLng);
      advanceStepIfNeeded(userLatLng);
    }

    function startWatch() {
      if (watchId !== null) navigator.geolocation.clearWatch(watchId);
      watchId = navigator.geolocation.watchPosition(
        handlePositionUpdate,
        () => {},
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 5000 }
      );
    }

    // ======================= 🚗 Init Map =======================
    function initNav() {
      map = new google.maps.Map(document.getElementById('navMap'), {
        center: { lat: DEST.lat, lng: DEST.lng },
        zoom: 14,
        styles: EV_PURPLE_STYLE,
        mapTypeControl: false
      });

      instructionUI.icon   = document.getElementById('navStepIcon');
      instructionUI.text   = document.getElementById('navInstructionText');
      instructionUI.meta   = document.getElementById('navInstructionMeta');
      instructionUI.header = document.getElementById('navStepTitle');

      dirService  = new google.maps.DirectionsService();
      dirRenderer = new google.maps.DirectionsRenderer({
        map,
        suppressMarkers: true,
        polylineOptions: { strokeColor: "#7c3aed", strokeWeight: 6, strokeOpacity: 0.95 }
      });

      // ปุ่ม Back
      const backUrl =
        @auth "{{ auth()->user()->role->name === 'admin' ? route('stations.map') : route('user.dashboard') }}"
        @else "{{ route('welcome') }}"
        @endauth;
      document.getElementById('btnBackTop').addEventListener('click', () => {
        window.location.href = backUrl;
      });

      // เอาตำแหน่งปัจจุบัน (HTTPS หรือ localhost เท่านั้น)
      const okHTTPS = (location.protocol === 'https:' || ['localhost','127.0.0.1'].includes(location.hostname));
      if (!okHTTPS || !('geolocation' in navigator)) { drawRoute(map.getCenter()); return; }

      const hi = { enableHighAccuracy: true,  timeout: 12000, maximumAge: 0 };
      const lo = { enableHighAccuracy: false, timeout: 12000, maximumAge: 0 };

      navigator.geolocation.getCurrentPosition(p => {
        const o = { lat: p.coords.latitude, lng: p.coords.longitude };
        map.setCenter(o); if (map.getZoom() < 14) map.setZoom(15);
        drawRoute(o); startWatch();
      }, () => {
        navigator.geolocation.getCurrentPosition(p2 => {
          const o2 = { lat: p2.coords.latitude, lng: p2.coords.longitude };
          map.setCenter(o2); drawRoute(o2); startWatch();
        }, () => drawRoute(map.getCenter()), lo);
      }, hi);
    }

    // ✅ รอ Google Maps พร้อม (มีตัวช่วย whenGoogleMapsReady อยู่แล้วในโปรเจกต์)
    whenGoogleMapsReady(initNav);
  </script>
  @endpush
</x-app-layout>
