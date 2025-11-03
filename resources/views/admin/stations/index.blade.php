<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">จัดการสถานีชาร์จ</h2>
    </x-slot>
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

        /* ===== Modal Global Styles (Create Station) ===== */
        .ev-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 28px 16px;
            z-index: 100000;
        }

        .ev-modal.is-open {
            display: flex;
        }

        .ev-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: saturate(160%) blur(8px);
        }

        .ev-modal__panel {
            position: relative;
            width: min(780px, 94vw);
            max-width: 820px;
            max-height: min(90vh, 760px);
            background: #ffffff;
            border-radius: 28px;
            box-shadow: 0 28px 68px rgba(15, 23, 42, 0.28);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .ev-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 26px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(90deg, rgba(124, 58, 237, .08), rgba(124, 58, 237, 0));
        }

        .ev-modal__title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
        }

        .ev-modal__close {
            border: none;
            background: transparent;
            font-size: 28px;
            line-height: 1;
            color: #4b5563;
            cursor: pointer;
            transition: color .15s ease;
        }

        .ev-modal__close:hover,
        .ev-modal__close:focus-visible {
            color: #1f2937;
        }

        .ev-modal__body {
            flex: 1;
            overflow-y: auto;
            background: #f8fafc;
            padding: 26px 28px 32px;
        }

        .admin-modal-form {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .admin-modal-form .form-section {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-start;
            align-items: center;
            margin-top: 10px;
        }

        .modal-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 22px;
            border-radius: 18px;
            border: none;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            color: #fff;
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            box-shadow: 0 14px 26px rgba(124, 58, 237, 0.28);
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .modal-primary:hover,
        .modal-primary:focus-visible {
            transform: translateY(-1px);
            box-shadow: 0 18px 32px rgba(124, 58, 237, 0.32);
        }

        .modal-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 22px;
            border-radius: 18px;
            border: 1px solid #cbd5f5;
            background: #ffffff;
            font-weight: 500;
            font-size: 0.95rem;
            color: #1f2937;
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease;
        }

        .modal-secondary:hover,
        .modal-secondary:focus-visible {
            background: #f1f5f9;
            border-color: #a5b4fc;
        }

        .modal-alert {
            border-radius: 18px;
            padding: 14px 18px;
            font-size: 0.925rem;
            border: 1px solid #fcd34d;
            background: #fffbeb;
            color: #92400e;
        }

        .form-control {
            width: 100%;
            border-radius: 18px;
            border: 1px solid #d7dde8;
            background: rgba(248, 250, 252, 0.95);
            padding: 12px 16px;
            font-size: 0.95rem;
            line-height: 1.45;
            color: #0f172a;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #2f4f4f;
            box-shadow: 0 0 0 4px rgba(47, 79, 79, 0.2);
            background: #fff;
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        .form-chip {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .form-chip__input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .form-chip__label {
            padding: 10px 18px;
            border-radius: 999px;
            border: 1px solid #d8d5f3;
            background: #f5f3ff;
            color: #4c1d95;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all .18s ease-in-out;
        }

        .form-chip__label:hover {
            border-color: #a855f7;
            background: #ede9fe;
            color: #5b21b6;
        }

        .form-chip__input:checked+.form-chip__label {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            box-shadow: 0 10px 22px rgba(124, 58, 237, 0.25);
        }

        .file-input {
            padding: 10px 16px;
            background: #fff;
        }

        body.modal-open {
            overflow: hidden;
        }

        @media (max-width: 768px) {
            .ev-modal__panel {
                max-width: 100%;
                width: min(640px, 100%);
                border-radius: 22px;
            }

            .modal-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .modal-primary,
            .modal-secondary {
                width: 100%;
                justify-content: center;
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

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">

                    <!-- ส่วนฟิลเตอร์ -->
                    <form method="GET" class="mb-6 flex flex-wrap items-end gap-4">
                        <!-- ค้นหา -->
                        <div class="flex flex-col">
                            <label class="block text-sm text-gray-600 mb-1">ค้นหาชื่อสถานี</label>
                            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                                class="border border-gray-300 rounded-lg px-3 py-2 w-48 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="เช่น ปตท., กฟภ.">
                        </div>

                        <!-- สถานะ -->
                        <div class="flex flex-col">
                            <label class="block text-sm text-gray-600 mb-1">สถานะ</label>
                            <select name="status_id"
                                class="border border-gray-300 rounded-lg px-3 py-2 w-48 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">ทั้งหมด</option>
                                @foreach ($statuses as $s)
                                    <option value="{{ $s->id }}" @selected(($filters['status_id'] ?? '') !== '' && (int) ($filters['status_id']) === $s->id)>
                                        {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- อำเภอ -->
                        <div class="flex flex-col">
                            <label class="block text-sm text-gray-600 mb-1">อำเภอ</label>
                            <select name="district_id"
                                class="border border-gray-300 rounded-lg px-3 py-2 w-48 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">ทั้งหมด</option>
                                @foreach ($districts as $d)
                                    <option value="{{ $d->id }}" @selected(($filters['district_id'] ?? '') !== '' && (int) ($filters['district_id']) === $d->id)>
                                        {{ $d->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- ปุ่มค้นหา  -->
                        <div class="flex flex-col">
                            <label class="text-sm font-medium text-gray-700 mb-1 invisible">.</label>
                            <button
                                class="px-5 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 focus:ring-2 focus:ring-gray-400">
                                ค้นหา
                            </button>
                        </div>

                        <!-- ปุ่มเพิ่มสถานี -->
                        <div class="flex flex-col ml-auto">
                            <label class="text-sm font-medium text-gray-700 mb-1 invisible">.</label>
                            <button type="button"
                                class="px-5 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 focus:ring-2 focus:ring-gray-400 js-open-create-modal">
                                + เพิ่มสถานี
                            </button>
                        </div>
                    </form>

                </div>

                <table class="w-full border">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2 border">ลำดับ</th>
                            <th class="p-2 border">ชื่อสถานี</th>
                            <th class="p-2 border">ที่อยู่</th>
                            <th class="p-2 border">สถานะ</th>
                            <th class="p-2 border">รูปสถานี</th>
                            <th class="p-2 border">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stations as $station)
                            <tr>
                                <td class="p-2 border">
                                    <div class="flex items-center justify-center h-full">
                                        {{ ($stations->currentPage() - 1) * $stations->perPage() + $loop->iteration }}
                                    </div>
                                </td>
                                <td class="p-2 border">{{ $station->name }}</td>
                                <td class="p-2 border">
                                    {{ $station->address }}{{  ' ' . $station->district->name ?? '-' }}{{  ' ' . $station->subdistrict->name ?? '-' }}
                                </td>
                                <td class="p-2 border">
                                    @php
                                        $statusName = $station->status->name ?? '-';
                                        $statusClass = match ($statusName) {
                                            'พร้อมใช้งาน' => 'text-green-600',
                                            'ชำรุด' => 'text-red-600',
                                            default => 'text-gray-700'
                                        };
                                    @endphp
                                    <span class="font-medium {{ $statusClass }}">{{ $statusName }}</span>
                                </td>
                                <td class="p-2 border text-center">
                                    <div class="flex items-center justify-center">
                                        @if ($station->image)
                                            <a href="{{ $station->image_url }}" target="_blank">
                                                <img src="{{ $station->image_url }}" alt="รูปสถานี {{ $station->name }}"
                                                    class="h-16 w-24 object-cover rounded border hover:scale-110 transition-transform duration-200">
                                            </a>
                                        @else
                                            <span class="text-gray-400">ไม่มีรูป</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-2 border">

                                    <a href="{{ route('admin.stations.edit', $station) }}"
                                        class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100 hover:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-1">
                                        แก้ไข
                                    </a>
                                    <form action="{{ route('admin.stations.destroy', $station) }}" method="POST"
                                        class="inline-block" onsubmit="return confirm('ยืนยันลบสถานีนี้?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-100 hover:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1">
                                            ลบ
                                        </button>
                                    </form>

                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center p-3">ไม่มีข้อมูล</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @php
        $createModalShouldOpen = old('_modal') === 'create-station' && $errors->any();
    @endphp

    <div id="createStationModal" class="ev-modal {{ $createModalShouldOpen ? 'is-open' : '' }}"
        aria-hidden="{{ $createModalShouldOpen ? 'false' : 'true' }}">
        <div class="ev-modal__backdrop" data-modal-close></div>
        <div class="ev-modal__panel" role="dialog" aria-modal="true" aria-labelledby="createStationModalTitle">
            <div class="ev-modal__header">
                <h3 class="ev-modal__title" id="createStationModalTitle">เพิ่มสถานีชาร์จ</h3>
                <button type="button" class="ev-modal__close" data-modal-close aria-label="ปิด">×</button>
            </div>
            <div class="ev-modal__body">
                @if ($errors->any() && old('_modal') === 'create-station')
                    <div class="modal-alert mb-4">
                        กรุณาตรวจสอบข้อมูลอีกครั้ง มีบางฟิลด์ที่ต้องการการแก้ไข
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.stations.store') }}" enctype="multipart/form-data"
                    class="admin-modal-form">
                    @csrf
                    <input type="hidden" name="_modal" value="create-station">

                    @include('admin.stations._form', ['station' => null])

                    <div class="modal-actions">
                        <button type="submit" class="modal-primary">ยืนยัน</button>
                        <button type="button" class="modal-secondary" data-modal-close>ยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('createStationModal');
                if (!modal) return;

                const body = document.body;
                const openButtons = document.querySelectorAll('.js-open-create-modal');
                const closeSelectors = modal.querySelectorAll('[data-modal-close]');
                const form = modal.querySelector('form');
                const nameField = form ? form.querySelector('input[name="name"]') : null;

                const ensureBodyState = () => {
                    const anyOpen = document.querySelector('.ev-modal.is-open');
                    if (anyOpen) {
                        body.classList.add('modal-open');
                    } else {
                        body.classList.remove('modal-open');
                    }
                };

                const openModal = () => {
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    ensureBodyState();
                    window.setTimeout(() => nameField?.focus(), 180);
                };

                const closeModal = () => {
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                    ensureBodyState();
                };

                openButtons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        openModal();
                    });
                });

                closeSelectors.forEach(el => {
                    el.addEventListener('click', () => {
                        closeModal();
                    });
                });

                modal.addEventListener('click', (event) => {
                    const target = event.target;
                    if (target === modal || target.classList.contains('ev-modal__backdrop')) {
                        closeModal();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                        closeModal();
                    }
                });

                if (modal.classList.contains('is-open')) {
                    ensureBodyState();
                    window.setTimeout(() => nameField?.focus(), 260);
                }
            });
        </script>
    @endpush
</x-app-layout>
@if (session('success'))
    <script>
        alert(@json(session('success')));
    </script>
@endif