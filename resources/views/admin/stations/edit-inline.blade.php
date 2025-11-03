<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>แก้ไขสถานี | EV Charging Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: #f3f4f6;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #1f2937;
        }

        .inline-container {
            max-width: 780px;
            margin: 0 auto;
            padding: 26px 22px 32px;
        }

        .inline-card {
            background: #ffffff;
            border-radius: 28px;
            box-shadow: 0 32px 56px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }

        .inline-card__body {
            padding: 32px 32px 34px;
        }

        .inline-alert {
            border-radius: 16px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .inline-alert--success {
            background: #ecfdf5;
            border: 1px solid #6ee7b7;
            color: #047857;
        }

        .form-control {
            width: 100%;
            border-radius: 18px;
            border: 1px solid #d7dde8;
            background: rgba(248, 250, 252, 0.95);
            padding: 12px 16px;
            font-size: 0.95rem;
            line-height: 1.5;
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
            color: #94a3b8;
        }

        .inline-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .inline-form .inline-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .inline-form .inline-label {
            font-size: 14px;
            font-weight: 500;
            color: #455a64;
        }

        .inline-form .chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
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

        .form-chip__input:checked + .form-chip__label {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            box-shadow: 0 10px 22px rgba(124, 58, 237, 0.25);
        }

        .inline-actions {
            display: flex;
            gap: 14px;
            margin-top: 18px;
        }

        .inline-primary {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: #fff;
            border-radius: 16px;
            padding: 14px 28px;
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
            border-radius: 16px;
            padding: 12px 26px;
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
                padding: 18px 14px 28px;
            }

            .inline-card__body {
                padding: 24px 20px 28px;
            }

            .inline-actions {
                flex-direction: column;
                align-items: stretch;
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
                <form method="POST" action="{{ route('admin.stations.update', $station) }}"
                    enctype="multipart/form-data" class="inline-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="inline" value="1">
                    @include('admin.stations._form', ['station' => $station])
                    <div class="inline-actions">
                        <button class="inline-primary">บันทึกการเปลี่ยนแปลง</button>
                        <button type="button" class="inline-secondary"
                            onclick="window.parent.dispatchEvent(new CustomEvent('admin-modal-close'));">
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
