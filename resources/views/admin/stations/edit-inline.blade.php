<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>แก้ไขสถานี | EV Charging Admin</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        body {
            background: #f3f4f6;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #1f2937;
        }
        .inline-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 32px 34px 42px;
        }
        .inline-card {
            background: #ffffff;
            border-radius: 26px;
            box-shadow: 0 26px 48px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }
        .inline-card__body {
            padding: 32px 34px 36px;
        }
        .inline-heading {
            font-size: 22px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 28px;
        }
        .inline-alert {
            border-radius: 16px;
            padding: 14px 18px;
            margin-bottom: 22px;
            font-size: 14px;
        }
        .inline-alert--success {
            background: #ecfdf5;
            border: 1px solid #6ee7b7;
            color: #047857;
        }
        .inline-form .inline-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .inline-form .inline-label {
            font-size: 14px;
            font-weight: 500;
            color: #4b5563;
        }
        .inline-form .inline-input,
        .inline-form select.inline-input {
            width: 100%;
            border-radius: 14px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            padding: 12px 16px;
            min-height: 46px;
            font-size: 15px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .inline-form .inline-input:focus,
        .inline-form select.inline-input:focus {
            outline: none;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.18);
            background: #fff;
        }
        .inline-form textarea.inline-input {
            min-height: 110px;
            resize: vertical;
        }
        .inline-form .double-field {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        .inline-form .chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .inline-form .chip-option {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border-radius: 999px;
            border: 1px solid #d8d5f3;
            background: #f4f3ff;
            font-size: 14px;
            font-weight: 500;
            color: #4c1d95;
            cursor: pointer;
            transition: all .18s ease-in-out;
        }
        .inline-form .chip-option:hover {
            border-color: #7c3aed;
            background: #ede9fe;
        }
        .inline-form .chip-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .inline-form .chip-option input:checked + .chip-bg {
            opacity: 1;
        }
        .inline-form .chip-option input:checked ~ span {
            color: #fff;
        }
        .inline-form .chip-option .chip-bg {
            position: absolute;
            inset: -1px;
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            border-radius: inherit;
            opacity: 0;
            transition: opacity .18s ease-in-out;
            z-index: 0;
        }
        .inline-form .chip-option span {
            position: relative;
            z-index: 1;
        }
        .inline-form .inline-help {
            font-size: 13px;
            color: #6b7280;
        }
        .inline-actions {
            display: flex;
            gap: 14px;
            margin-top: 26px;
        }
        .inline-primary {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: #fff;
            border-radius: 14px;
            padding: 12px 24px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(124, 58, 237, 0.25);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .inline-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(124, 58, 237, 0.3);
        }
        .inline-secondary {
            border: 1px solid #d1d5db;
            border-radius: 14px;
            padding: 12px 24px;
            font-weight: 500;
            color: #1f2937;
            background: #fff;
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease;
        }
        .inline-secondary:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }
        @media (max-width: 768px) {
            .inline-container {
                padding: 20px;
            }
            .inline-card__body {
                padding: 24px;
            }
            .inline-form .double-field {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .inline-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="inline-container">
        <div class="inline-card">
            <div class="inline-card__body">
                @if(session('success'))
                    <div class="inline-alert inline-alert--success">
                        {{ session('success') }}
                    </div>
                @endif
                <h2 class="inline-heading">แก้ไขสถานี · {{ $station->name }}</h2>
                <form method="POST"
                      action="{{ route('admin.stations.update',$station) }}"
                      enctype="multipart/form-data"
                      class="inline-form space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="inline" value="1">
                    @include('admin.stations._form', ['station' => $station])
                    <div class="inline-actions">
                        <button class="inline-primary">บันทึกการเปลี่ยนแปลง</button>
                        <button type="button" class="inline-secondary" onclick="window.parent.dispatchEvent(new CustomEvent('admin-modal-close'));">
                            ปิดหน้าต่าง
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @if(session('success'))
        <script>
            window.parent.dispatchEvent(new CustomEvent('admin-station-updated', {
                detail: { stationId: {{ $station->id }} }
            }));
        </script>
    @endif
</body>
</html>
