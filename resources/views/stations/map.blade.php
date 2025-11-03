<x-app-layout>

    {{-- ส่วนของแผนที่ --}}

    @include('partials.stations-map')


    {{-- 🔻 แถบล่างแนวยาว (6 ปุ่ม: แผนที่, จัดการสถานี, จัดการผู้ใช้, รายงานปัญหา, แจ้งเตือน, สถิติ) --}}
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
            grid-template-columns: repeat(6, minmax(0, 1fr));
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

        .floating-actions__label {
            color: #0f172a;
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
                top: 50%;
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
    </style>


    <div class="floating-actions">
        <div class="floating-actions__inner">
            <div class="floating-actions__list">

                {{-- 🗺️ แผนที่ --}}
                <a href="{{ route('stations.map') }}" class="floating-actions__item">
                    <div class="floating-actions__icon">🗺️</div>
                    <div class="floating-actions__label">แผนที่</div>
                </a>

                {{-- 🏭 จัดการสถานี --}}
                <a href="{{ route('admin.stations.index') }}" class="floating-actions__item">
                    <div class="floating-actions__icon">🏭</div>
                    <div class="floating-actions__label">จัดการสถานี</div>
                </a>

                {{-- 👤 จัดการผู้ใช้ --}}
                <a href="{{ route('admin.users.index') }}" class="floating-actions__item">
                    <div class="floating-actions__icon">👤</div>
                    <div class="floating-actions__label">จัดการผู้ใช้</div>
                </a>

                {{-- ⚠️ รายงานปัญหา --}}
                <a href="{{ route('admin.reports.index') }}" class="floating-actions__item">
                    <div class="floating-actions__icon">⚠️</div>
                    <div class="floating-actions__label">รายงานปัญหา</div>
                </a>

                {{-- 🔔 แจ้งเตือน --}}
                <a href="{{ route('admin.notifications.index') }}" class="floating-actions__item">
                    <div class="floating-actions__icon">🔔</div>
                    <div class="floating-actions__label">แจ้งเตือน</div>
                </a>

                {{-- 📊 สถิติ --}}
                <a href="{{ route('admin.dashboard') }}" class="floating-actions__item">
                    <div class="floating-actions__icon">📊</div>
                    <div class="floating-actions__label">สถิติ</div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>