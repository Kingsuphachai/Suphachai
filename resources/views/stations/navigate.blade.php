{{-- resources/views/stations/navigate.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800">นำทางไป: {{ $station->name }}</h2>
  </x-slot>

  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow sm:rounded-lg p-4 space-y-3">
        <div class="flex flex-wrap items-center gap-2 text-sm">
          <span class="px-2 py-1 bg-gray-100 rounded">สถานะ: {{ $station->status->name ?? '-' }}</span>
          <span class="px-2 py-1 bg-gray-100 rounded">{{ $station->address ?? '-' }}</span>
        </div>

        <div id="navMap" class="w-full rounded border" style="height:70vh;"></div>

        <div class="mt-3 grid md:grid-cols-3 gap-3">
          <div class="md:col-span-2">
            <ol id="navSteps" class="text-sm space-y-2"></ol>
          </div>
          <div class="p-3 bg-gray-50 rounded border text-sm">
            <div class="font-semibold mb-2">สรุปเส้นทาง</div>
            <div>ระยะทาง: <span id="sumDist">-</span></div>
            <div>เวลา: <span id="sumDur">-</span></div>
            <button id="toggleVoice" class="mt-2 px-3 py-2 bg-gray-200 rounded" data-on="0">🔈 เสียง: ปิด</button>
          </div>
        </div>

        <div class="pt-2">
          <button id="btnBack" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            ← กลับหน้าสถานี
          </button>
          <script>
            document.getElementById('btnBack').addEventListener('click', () => {
              const cameFromMap = document.referrer.includes('/stations/map');
              if (cameFromMap) history.back();
              else window.location.href = @json(route('stations.map'));
            });
          </script>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    const DEST = {
      lat: parseFloat(@json($station->latitude)),
      lng: parseFloat(@json($station->longitude)),
      name: @json($station->name)
    };
    let map, dirService, dirRenderer, watchId=null, voiceOn=false;

    function speak(t){ if(!voiceOn) return; try{ const u=new SpeechSynthesisUtterance(t); u.lang='th-TH'; speechSynthesis.cancel(); speechSynthesis.speak(u);}catch(e){} }
    function arrowFor(m){ const s=(m||'').toLowerCase(); if(s.includes('uturn'))return'↩️'; if(s.includes('left'))return'⬅️'; if(s.includes('right'))return'➡️'; if(s.includes('roundabout'))return'🛞'; return'⬆️'; }
    function renderSteps(leg){
      const el=document.getElementById('navSteps'); el.innerHTML='';
      (leg.steps||[]).forEach(st=>{
        const li=document.createElement('li'); li.className='p-2 bg-white rounded border';
        li.innerHTML=`<div class="flex items-start gap-2">
          <div class="text-xl leading-none">${arrowFor(st.maneuver||st.instructions)}</div>
          <div class="flex-1"><div class="leading-5">${st.instructions}</div>
          <div class="text-gray-500 text-xs">${st.distance?.text||''} • ${st.duration?.text||''}</div></div></div>`;
        el.appendChild(li);
      });
    }
    function drawRoute(origin){
      dirService.route({
        origin,
        destination:{lat:DEST.lat,lng:DEST.lng},
        travelMode: google.maps.TravelMode.DRIVING,
        provideRouteAlternatives:false
      }, (res,status)=>{
        if(status==='OK'){
          dirRenderer.setDirections(res);
          const leg=res.routes[0].legs[0];
          document.getElementById('sumDist').textContent=leg.distance?.text||'-';
          document.getElementById('sumDur').textContent=leg.duration?.text||'-';
          renderSteps(leg);
          speak(`เริ่มนำทางไปยัง ${DEST.name} ระยะทาง ${leg.distance?.text||''} ใช้เวลา ${leg.duration?.text||''}`);
        }else{
          const hints={
            INVALID_REQUEST:'origin/destination ไม่ถูกต้อง',
            NOT_FOUND:'หาตำแหน่งไม่เจอ',
            ZERO_RESULTS:'ไม่มีเส้นทาง',
            OVER_DAILY_LIMIT:'ยังไม่เปิด Billing/เกินโควตา',
            OVER_QUERY_LIMIT:'เรียกบ่อยเกินไป',
            REQUEST_DENIED:'คีย์ถูกปฏิเสธ/ยังไม่เปิด Directions',
            UNKNOWN_ERROR:'ข้อผิดพลาดชั่วคราว'
          };
          alert(`คำนวณเส้นทางไม่สำเร็จ\nสถานะ: ${status}\n${hints[status]||''}`);
        }
      });
    }
    function startWatch(){
      if(watchId!==null) navigator.geolocation.clearWatch(watchId);
      watchId = navigator.geolocation.watchPosition(pos=>{
        map.panTo({lat:pos.coords.latitude,lng:pos.coords.longitude});
      }, ()=>{}, {enableHighAccuracy:true,timeout:12000,maximumAge:5000});
    }
    function initNav(){
      map = new google.maps.Map(document.getElementById('navMap'), {
        center:{lat:DEST.lat,lng:DEST.lng}, zoom:14, mapTypeControl:false
      });
      dirService=new google.maps.DirectionsService();
      dirRenderer=new google.maps.DirectionsRenderer({map});

      const okHTTPS = (location.protocol==='https:' || ['localhost','127.0.0.1'].includes(location.hostname));
      if(!okHTTPS || !('geolocation' in navigator)){
        drawRoute(map.getCenter()); // fallback
        return;
      }
      const hi={enableHighAccuracy:true,timeout:12000,maximumAge:0};
      const lo={enableHighAccuracy:false,timeout:12000,maximumAge:0};

      navigator.geolocation.getCurrentPosition(p=>{
        const o={lat:p.coords.latitude,lng:p.coords.longitude};
        map.setCenter(o); if(map.getZoom()<14) map.setZoom(15);
        drawRoute(o); startWatch();
      }, ()=>{
        navigator.geolocation.getCurrentPosition(p2=>{
          const o2={lat:p2.coords.latitude,lng:p2.coords.longitude};
          map.setCenter(o2); drawRoute(o2); startWatch();
        }, ()=> drawRoute(map.getCenter()), lo);
      }, hi);
    }

    // ✅ รอ SDK พร้อม
    whenGoogleMapsReady(initNav);

    // ปุ่มเสียง
    document.addEventListener('DOMContentLoaded', ()=>{
      const tgl = document.getElementById('toggleVoice');
      tgl.addEventListener('click', e=>{
        voiceOn = !voiceOn;
        e.currentTarget.dataset.on = voiceOn?'1':'0';
        e.currentTarget.textContent = voiceOn ? '🔊 เสียง: เปิด' : '🔈 เสียง: ปิด';
        if(!voiceOn){ try{speechSynthesis.cancel();}catch(e){} }
      });
    });
  </script>
  @endpush
</x-app-layout>
