{{-- resources/views/partials/google-maps-loader.blade.php --}}
<script>
  // ให้ทุกหน้าเรียกงานที่ต้องทำหลัง Maps พร้อมผ่านคิวนี้
  (function () {
    window._gmReadyQueue = window._gmReadyQueue || [];
    window.whenGoogleMapsReady = function (fn) {
      if (window.google && window.google.maps) { fn(); }
      else { window._gmReadyQueue.push(fn); }
    };
    window.onGoogleMapsReady = function () {
      (window._gmReadyQueue || []).forEach(fn => { try { fn(); } catch(e) { console.error(e); } });
      window._gmReadyQueue = [];
    };
  })();
</script>

<script
  src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=onGoogleMapsReady&language=th&region=TH&libraries=geometry"
  async
></script>
