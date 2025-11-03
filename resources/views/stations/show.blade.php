{{-- resources/views/stations/show.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $station->name }}</h2>
  </x-slot>

  <div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-700">รายละเอียดสถานี</h3>
        <a href="{{ route('reports.create', ['station' => $station->id]) }}"
          class="inline-block px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700">
          แจ้งปัญหาสถานีนี้
        </a>
        <div><strong>ชื่อสถานี:</strong> {{ $station->name }}</div>
        <div><strong>ที่อยู่:</strong> {{ $station->address ?? '-' }}</div>
        <div><strong>สถานะ:</strong> {{ $station->status->name ?? '-' }}</div>
        <div><strong>เวลาทำการ:</strong> {{ $station->operating_hours ?? 'ไม่ระบุ' }}</div>

        <div>
          <strong>ประเภทหัวชาร์จ:</strong>
          @forelse ($station->chargers ?? [] as $charger)
            <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded-md text-sm mr-2">
              {{ $charger->name }}
            </span>
          @empty
            <span class="text-gray-500">-</span>
          @endforelse
        </div>

        <div><strong>พิกัด:</strong>
          @if($station->latitude && $station->longitude)
            {{ $station->latitude }}, {{ $station->longitude }}
          @else
            <span class="text-gray-500">ยังไม่ระบุ</span>
          @endif
        </div>

        @if($station->latitude && $station->longitude)
          <div id="map" class="w-full rounded border" style="height:380px;"></div>
        @else
          <div class="mt-4 p-3 border rounded-md bg-gray-50 text-gray-600">
            ยังไม่มีพิกัดสำหรับแสดงแผนที่
          </div>
        @endif

        @if($station->latitude && $station->longitude)
          <div class="mt-4 flex items-center gap-2">
            <a href="{{ route('stations.navigate', $station) }}" class="px-3 py-2 bg-indigo-600 text-white rounded">
              🚗 เปิดหน้านำทาง
            </a>
            <button id="btnStartNav" class="px-3 py-2 bg-indigo-100 rounded">
              เริ่มนำทางในหน้านี้
            </button>
            <button id="btnToggleVoice" class="px-3 py-2 bg-gray-200 rounded" data-on="0" title="เปิด/ปิด เสียงคำแนะนำ">
              🔈 เสียง: ปิด
            </button>
          </div>

          <div id="navPanel" class="hidden mt-3">
            <div id="navMap" class="border rounded" style="height:60vh;"></div>
            <div class="mt-3 grid md:grid-cols-3 gap-3">
              <div class="md:col-span-2">
                <ol id="navSteps" class="text-sm space-y-2"></ol>
              </div>
              <div class="p-3 bg-gray-50 rounded border text-sm">
                <div class="font-semibold mb-2">สรุปเส้นทาง</div>
                <div>ระยะทาง: <span id="sumDist">-</span></div>
                <div>เวลาโดยประมาณ: <span id="sumDur">-</span></div>
              </div>
            </div>
          </div>
        @endif

        <div class="pt-4">
          <a href="{{ route('stations.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            ← กลับไปยังรายการ
          </a>
        </div>
      </div>
    </div>
  </div>

  @if($station->latitude && $station->longitude)
    @push('scripts')
      <script>
        function initStationMap() {
          const center = {
            lat: parseFloat(@json($station->latitude)) || 17.1545,
            lng: parseFloat(@json($station->longitude)) || 104.1347,
          };
          const map = new google.maps.Map(document.getElementById("map"), { zoom: 13, center });
          new google.maps.Marker({ map, position: center, title: @json($station->name) });
        }
        whenGoogleMapsReady(initStationMap);
      </script>

      <script>
        (function () {
          const DEST = {
            lat: parseFloat(@json($station->latitude)),
            lng: parseFloat(@json($station->longitude)),
            name: @json($station->name)
          };
          let navMap, dirService, dirRenderer, watchId = null, voiceOn = false, currentLeg = null;

          const btnStart = document.getElementById('btnStartNav');
          const btnVoice = document.getElementById('btnToggleVoice');
          const panel = document.getElementById('navPanel');
          const stepsEl = document.getElementById('navSteps');
          const sumDist = document.getElementById('sumDist');
          const sumDur = document.getElementById('sumDur');

          function speak(text) {
            if (!voiceOn) return;
            try {
              const u = new SpeechSynthesisUtterance(text); u.lang = 'th-TH';
              speechSynthesis.cancel(); speechSynthesis.speak(u);
            } catch (e) { }
          }
          function arrowFor(m) {
            const s = (m || '').toLowerCase();
            if (s.includes('uturn')) return '↩️';
            if (s.includes('left')) return '⬅️';
            if (s.includes('right')) return '➡️';
            if (s.includes('roundabout')) return '🛞';
            return '⬆️';
          }
          function ensureGeoOK() {
            const isLocal = ['localhost', '127.0.0.1'].includes(location.hostname);
            return location.protocol === 'https:' || isLocal;
          }
          function renderSteps(leg) {
            stepsEl.innerHTML = '';
            (leg.steps || []).forEach(st => {
              const li = document.createElement('li');
              li.className = 'p-2 bg-white rounded border';
              li.innerHTML = `
              <div class="flex items-start gap-2">
                <div class="text-xl leading-none">${arrowFor(st.maneuver || st.instructions)}</div>
                <div class="flex-1">
                  <div class="leading-5">${st.instructions}</div>
                  <div class="text-gray-500 text-xs">
                    ${(st.distance && st.distance.text) || ''} • ${(st.duration && st.duration.text) || ''}
                  </div>
                </div>
              </div>`;
              stepsEl.appendChild(li);
            });
          }
          function drawRoute(origin) {
            const req = {
              origin,
              destination: { lat: DEST.lat, lng: DEST.lng },
              travelMode: google.maps.TravelMode.DRIVING,
              provideRouteAlternatives: false
            };
            dirService.route(req, (res, status) => {
              if (status === 'OK') {
                dirRenderer.setDirections(res);
                const leg = res.routes[0].legs[0];
                currentLeg = leg;
                renderSteps(leg);
                sumDist.textContent = leg.distance?.text || '-';
                sumDur.textContent = leg.duration?.text || '-';
                speak(`เริ่มนำทางไปยัง ${DEST.name} ระยะทาง ${leg.distance?.text || ''} ใช้เวลา ${leg.duration?.text || ''}`);
              } else {
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
              }
            });
          }
          function startWatch() {
            if (watchId !== null) navigator.geolocation.clearWatch(watchId);
            watchId = navigator.geolocation.watchPosition(pos => {
              navMap.panTo({ lat: pos.coords.latitude, lng: pos.coords.longitude });
            }, () => { }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 5000 });
          }

          btnStart?.addEventListener('click', () => {
            if (!Number.isFinite(DEST.lat) || !Number.isFinite(DEST.lng)) {
              alert('สถานีนี้ยังไม่มีพิกัด'); return;
            }
            panel.classList.remove('hidden');
            whenGoogleMapsReady(() => {
              navMap = new google.maps.Map(document.getElementById('navMap'), { center: { lat: DEST.lat, lng: DEST.lng }, zoom: 14, mapTypeControl: false });
              dirService = new google.maps.DirectionsService();
              dirRenderer = new google.maps.DirectionsRenderer({ map: navMap });

              if (!ensureGeoOK()) { drawRoute(navMap.getCenter()); return; }
              if (!('geolocation' in navigator)) { drawRoute(navMap.getCenter()); return; }

              const hi = { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 };
              const lo = { enableHighAccuracy: false, timeout: 12000, maximumAge: 0 };

              navigator.geolocation.getCurrentPosition(pos => {
                const origin = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                navMap.setCenter(origin); if (navMap.getZoom() < 14) navMap.setZoom(15);
                drawRoute(origin); startWatch();
              }, () => {
                navigator.geolocation.getCurrentPosition(pos2 => {
                  const origin2 = { lat: pos2.coords.latitude, lng: pos2.coords.longitude };
                  navMap.setCenter(origin2); drawRoute(origin2); startWatch();
                }, () => drawRoute(navMap.getCenter()), lo);
              }, hi);
            });
          });

          btnVoice?.addEventListener('click', () => {
            voiceOn = !voiceOn;
            btnVoice.dataset.on = voiceOn ? '1' : '0';
            btnVoice.textContent = voiceOn ? '🔊 เสียง: เปิด' : '🔈 เสียง: ปิด';
            if (!voiceOn) { try { speechSynthesis.cancel(); } catch (e) { } }
            else if (currentLeg) { speak(`กำลังนำทางไปยัง ${DEST.name}`); }
          });
        })();
      </script>
    @endpush
  @endif
</x-app-layout>