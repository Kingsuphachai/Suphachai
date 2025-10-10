<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            แผนที่สถานีชาร์จ
        </h2>
    </x-slot>

    {{-- ส่วนของแผนที่ --}}
    <div class="py-6 relative">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 relative z-0">
            @include('partials.stations-map')
        </div>
    </div>

    {{-- 🔻 แถบล่างแนวยาว (5 ปุ่ม: แผนที่, จัดการสถานี, จัดการผู้ใช้, รายงานปัญหา, แจ้งเตือน) --}}
    <div style="
        position:fixed; left:0; right:0; bottom:0; z-index:99999;
        background:#fffffff5; backdrop-filter:saturate(180%) blur(10px);
        border-top:1px solid #e5e7eb; box-shadow:0 -5px 20px rgba(0,0,0,0.1);
        padding:8px 12px;
    ">
        <div style="max-width:960px; margin:0 auto;">
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px;">

                {{-- 📍 ตำแหน่งฉัน --}}
                <button type="button" onclick="window.ev?.panToMe?.()" style="display:flex; flex-direction:column; align-items:center; justify-content:center;
                           padding:10px 0; border-radius:14px; background:#fff;
                           font-size:12px; color:#374151; border:1px solid #e5e7eb;
                           transition:background .2s, transform .2s;">
                    <div style="font-size:20px;">📍</div>
                    <div style="margin-top:4px;">ตำแหน่งฉัน</div>
                </button>

                {{-- ➕ ขอเพิ่มสถานีชาร์จ --}}
                <a href="{{ route('user.request.create') }}" style="display:flex; flex-direction:column; align-items:center; justify-content:center;
                           padding:10px 0; border-radius:14px; background:#fff;
                           font-size:12px; color:#374151; border:1px solid #e5e7eb;
                           text-decoration:none; transition:background .2s, transform .2s;">
                    <div style="font-size:20px;">➕</div>
                    <div style="margin-top:4px;">ขอเพิ่มสถานีชาร์จ</div>
                </a>

                {{-- ⚠️ แจ้งปัญหาสถานี --}}
                <a href="{{ route('user.reports.create') }}" style="display:flex; flex-direction:column; align-items:center; justify-content:center;
                           padding:10px 0; border-radius:14px; background:#fff;
                           font-size:12px; color:#374151; border:1px solid #e5e7eb;
                           text-decoration:none; transition:background .2s, transform .2s;">
                    <div style="font-size:20px;">⚠️</div>
                    <div style="margin-top:4px;">แจ้งปัญหาสถานี</div>
                </a>

            </div>
        </div>
    </div>

    {{-- ✅ ฟังก์ชันตำแหน่งฉัน --}}
    @push('scripts')
        <script>
            window.ev = window.ev || {};
            window.ev.panToMe = function () {
                if (!navigator.geolocation) {
                    alert("เบราว์เซอร์ของคุณไม่รองรับการระบุตำแหน่ง");
                    return;
                }

                navigator.geolocation.getCurrentPosition(pos => {
                    const me = { lat: pos.coords.latitude, lng: pos.coords.longitude };

                    if (window.map) {
                        window.map.panTo(me);
                        if (window.map.getZoom && window.map.getZoom() < 14) {
                            window.map.setZoom(14);
                        }

                        if (window.google?.maps) {
                            if (!window.myMarker) {
                                window.myMarker = new google.maps.Marker({
                                    position: me,
                                    map: window.map,
                                    icon: 'https://maps.gstatic.com/mapfiles/ms2/micons/blue-dot.png',
                                    title: 'ตำแหน่งฉัน',
                                    zIndex: 9999
                                });
                            } else {
                                window.myMarker.setPosition(me);
                            }
                        }
                    }
                });
            };
        </script>
    @endpush
</x-app-layout>