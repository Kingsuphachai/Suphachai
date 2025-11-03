<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800">
      แผงข้อมูลสถิติ
    </h2>
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
      background: linear-gradient(135deg, #7c3aed, #6366f1);
      color: #f9fafb;
      padding: 12px;
      border-radius: 24px;
      box-shadow: 0 18px 36px rgba(79, 70, 229, .25);
      width: min(840px, 96vw);
      /* กว้างพอดีและกึ่งกลาง */
      backdrop-filter: saturate(160%) blur(6px);
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

    /* ===== Admin Dashboard Styling ===== */
    .dashboard-surface {
      position: relative;
      padding: 48px 0 70px;
      background: linear-gradient(145deg, #f5f3ff 0%, #fdf2f8 35%, #ffffff 90%);
    }

    .dashboard-surface::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.75), rgba(255, 255, 255, 0.95));
      pointer-events: none;
    }

    .dashboard-shell {
      position: relative;
      z-index: 1;
      display: flex;
      flex-direction: column;
      gap: 40px;
    }

    .dashboard-hero {
      position: relative;
      overflow: hidden;
      border-radius: 32px;
      padding: 32px;
      background: linear-gradient(130deg, #7c3aed 0%, #5b21b6 45%, #4338ca 100%);
      color: #ffffff;
      box-shadow: 0 35px 78px rgba(76, 29, 149, 0.28);
      display: flex;
      flex-direction: column;
      gap: 28px;
    }

    .dashboard-hero::after,
    .dashboard-hero::before {
      content: '';
      position: absolute;
      width: 220px;
      height: 220px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.12);
      filter: blur(0);
    }

    .dashboard-hero::before {
      top: -80px;
      right: -40px;
    }

    .dashboard-hero::after {
      bottom: -90px;
      left: -60px;
      background: rgba(255, 255, 255, 0.08);
    }

    .dashboard-hero__content {
      position: relative;
      z-index: 2;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .dashboard-hero__eyebrow {
      font-size: 0.78rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      opacity: 0.8;
      font-weight: 600;
    }

    .dashboard-hero__heading {
      font-size: clamp(1.7rem, 3vw, 2.4rem);
      font-weight: 700;
      line-height: 1.2;
    }

    .dashboard-hero__lead {
      font-size: 1rem;
      line-height: 1.6;
      opacity: 0.88;
      max-width: 48ch;
    }

    .dashboard-hero__badges {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 8px;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 10px 18px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.18);
      backdrop-filter: blur(4px);
      font-size: 0.88rem;
      font-weight: 500;
      letter-spacing: 0.01em;
    }

    .hero-badge--soft {
      background: rgba(255, 255, 255, 0.1);
      opacity: 0.9;
    }

    .dashboard-hero__summary {
      position: relative;
      z-index: 2;
      display: grid;
      gap: 18px;
    }

    .hero-kpi {
      display: flex;
      flex-direction: column;
      gap: 6px;
      padding: 20px 22px;
      border-radius: 26px;
      background: rgba(255, 255, 255, 0.12);
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.16);
      backdrop-filter: blur(6px);
    }

    .hero-kpi span {
      font-size: 0.78rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      opacity: 0.7;
      font-weight: 600;
    }

    .hero-kpi strong {
      font-size: 2.1rem;
      font-weight: 700;
      line-height: 1.1;
    }

    .hero-kpi small {
      font-size: 0.78rem;
      opacity: 0.85;
    }

    .metric-grid {
      display: grid;
      gap: 24px;
    }

    .metric-grid--secondary {
      margin-top: -8px;
    }

    .metric-card {
      position: relative;
      overflow: hidden;
      border-radius: 26px;
      background: #ffffff;
      padding: 26px;
      border: 1px solid rgba(99, 102, 241, 0.08);
      box-shadow: 0 24px 48px rgba(15, 23, 42, 0.12);
      transition: transform .25s ease, box-shadow .25s ease;
    }

    .metric-card::after {
      content: '';
      position: absolute;
      inset: -120px 40% auto -40px;
      height: 120%;
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), transparent);
      transform: rotate(12deg);
      opacity: 0;
      transition: opacity .25s ease;
    }

    .metric-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 28px 58px rgba(79, 70, 229, 0.16);
    }

    .metric-card:hover::after {
      opacity: 1;
    }

    .metric-card__icon {
      width: 48px;
      height: 48px;
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.35rem;
      margin-bottom: 18px;
      background: linear-gradient(135deg, #ede9fe, #ddd6fe);
      color: #5b21b6;
      box-shadow: 0 14px 30px rgba(124, 58, 237, 0.18);
    }

    .metric-card__label {
      font-size: 0.82rem;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: #6366f1;
    }

    .metric-card__value {
      margin-top: 10px;
      font-size: clamp(2.1rem, 3vw, 2.6rem);
      font-weight: 700;
      color: #0f172a;
      letter-spacing: -0.02em;
    }

    .metric-card__sub {
      margin-top: 10px;
      font-size: 0.95rem;
      color: #4f46e5;
    }

    .metric-card--emerald .metric-card__icon {
      background: linear-gradient(135deg, #d1fae5, #a7f3d0);
      color: #047857;
      box-shadow: 0 14px 28px rgba(16, 185, 129, 0.25);
    }

    .metric-card--emerald .metric-card__value {
      color: #047857;
    }

    .metric-card--rose .metric-card__icon {
      background: linear-gradient(135deg, #fee2e2, #fecaca);
      color: #b91c1c;
      box-shadow: 0 14px 28px rgba(248, 113, 113, 0.25);
    }

    .metric-card--rose .metric-card__value {
      color: #b91c1c;
    }

    .metric-card--amber .metric-card__icon {
      background: linear-gradient(135deg, #fef3c7, #fde68a);
      color: #b45309;
      box-shadow: 0 14px 28px rgba(251, 191, 36, 0.23);
    }

    .metric-card--amber .metric-card__value {
      color: #b45309;
    }

    .metric-card--indigo .metric-card__icon {
      background: linear-gradient(135deg, #c7d2fe, #a5b4fc);
      color: #4338ca;
      box-shadow: 0 14px 28px rgba(99, 102, 241, 0.25);
    }

    .metric-card--indigo .metric-card__value {
      color: #312e81;
    }

    .metric-card--slate .metric-card__icon {
      background: linear-gradient(135deg, #e2e8f0, #cbd5f5);
      color: #1f2937;
      box-shadow: 0 14px 28px rgba(100, 116, 139, 0.2);
    }

    .metric-card--purple .metric-card__icon {
      background: linear-gradient(135deg, #ede9fe, #e0e7ff);
      color: #6d28d9;
      box-shadow: 0 14px 28px rgba(147, 51, 234, 0.22);
    }

    .metric-card--blue .metric-card__icon {
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      color: #1d4ed8;
      box-shadow: 0 14px 28px rgba(59, 130, 246, 0.22);
    }

    .chart-grid {
      display: grid;
      gap: 24px;
    }

    .chart-card {
      border-radius: 28px;
      background: #ffffff;
      border: 1px solid rgba(99, 102, 241, 0.12);
      box-shadow: 0 30px 60px rgba(15, 23, 42, 0.12);
      padding: 28px 30px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .chart-card__title {
      font-size: 1rem;
      font-weight: 600;
      color: #312e81;
    }

    .chart-card__subtitle {
      font-size: 0.8rem;
      color: #94a3b8;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }

    .chart-card__body {
      flex: 1;
      min-height: 240px;
      position: relative;
    }

    .chart-card canvas {
      width: 100% !important;
      height: 100% !important;
    }

    .chart-card__body--donut {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .chart-card__body--bars {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 18px;
      padding-bottom: 8px;
    }

    .chart-card__empty {
      display: none;
      font-size: 0.9rem;
      color: #94a3b8;
      padding-top: 12px;
    }

    .chart-card__empty.is-visible {
      display: block;
    }

    .donut-chart {
      position: relative;
      width: min(220px, 52vw);
      aspect-ratio: 1;
      border-radius: 50%;
      background: conic-gradient(var(--segment-colors, #e2e8f0 0 100%));
      box-shadow: inset 0 0 0 12px #f8fafc, 0 24px 48px rgba(79, 70, 229, 0.18);
      transition: background .35s ease;
    }

    .donut-chart::after {
      content: '';
      position: absolute;
      inset: 18%;
      border-radius: 50%;
      background: #ffffff;
      box-shadow: inset 0 18px 28px rgba(148, 163, 184, 0.08);
    }

    .donut-chart.is-empty {
      background: radial-gradient(circle at center, #ffffff 0%, #f1f5f9 100%);
      box-shadow: inset 0 0 0 12px #f8fafc, 0 18px 36px rgba(148, 163, 184, 0.18);
    }

    .donut-chart__label {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 4px;
      z-index: 1;
      text-align: center;
    }

    .donut-chart__label span {
      font-size: clamp(1.6rem, 4vw, 2.4rem);
      font-weight: 700;
      color: #312e81;
    }

    .donut-chart__label small {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.16em;
      color: #94a3b8;
    }

    .chart-legend {
      display: grid;
      gap: 14px;
      margin-top: 18px;
    }

    .chart-legend__item {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .chart-legend__item.is-muted {
      opacity: 0.4;
    }

    .legend-dot {
      width: 14px;
      height: 14px;
      border-radius: 50%;
      background: var(--dot-color, #cbd5f5);
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
      flex-shrink: 0;
    }

    .chart-legend__title {
      font-size: 0.9rem;
      font-weight: 600;
      color: #312e81;
    }

    .chart-legend__meta {
      font-size: 0.78rem;
      color: #94a3b8;
      margin-top: 2px;
    }

    .bar-chart {
      display: flex;
      align-items: flex-end;
      gap: 22px;
      width: 100%;
      padding: 6px 6px 0;
    }

    .bar-chart__col {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
    }

    .bar-chart__bar {
      flex: 1;
      width: 100%;
      border-radius: 18px;
      background: linear-gradient(180deg, rgba(99, 102, 241, 0.12), rgba(99, 102, 241, 0.04));
      display: flex;
      align-items: flex-end;
      justify-content: center;
      overflow: hidden;
      box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.12);
    }

    .bar-chart__fill {
      width: 100%;
      border-radius: 16px 16px 6px 6px;
      background: linear-gradient(180deg, rgba(124, 58, 237, 0.9), rgba(99, 102, 241, 0.75));
      box-shadow: 0 18px 24px rgba(99, 102, 241, 0.25);
      transition: height .4s ease, background .3s ease;
      min-height: 4px;
    }

    .bar-chart__label {
      text-align: center;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .bar-chart__label span {
      font-size: 0.85rem;
      font-weight: 600;
      color: #312e81;
    }

    
    .bar-chart__col.is-muted {
      opacity: 0.35;
    }

.bar-chart__label small {
      font-size: 0.78rem;
      color: #94a3b8;
    }

    .list-grid {
      display: grid;
      gap: 24px;
    }

    .list-grid--two {
      grid-template-columns: 1fr;
    }

    .list-card {
      border-radius: 28px;
      background: #ffffff;
      padding: 26px 28px;
      border: 1px solid rgba(148, 163, 184, 0.18);
      box-shadow: 0 28px 58px rgba(15, 23, 42, 0.12);
      display: flex;
      flex-direction: column;
      gap: 18px;
      min-height: 0;
    }

    .list-card__title {
      font-size: 1.05rem;
      font-weight: 600;
      color: #312e81;
    }

    .list-card__subtitle {
      font-size: 0.78rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: #94a3b8;
      font-weight: 600;
    }

    .list-card__list {
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .list-card__item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 18px;
      border-radius: 20px;
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(255, 255, 255, 0.8));
      color: #1f2937;
      box-shadow: 0 10px 24px rgba(99, 102, 241, 0.08);
    }

    .list-card__item-title {
      font-size: 0.95rem;
      font-weight: 600;
      color: #312e81;
    }

    .list-card__item-meta {
      font-size: 0.78rem;
      color: #94a3b8;
      margin-top: 2px;
    }

    .list-card__item-badge {
      display: inline-flex;
      align-items: center;
      padding: 6px 14px;
      border-radius: 999px;
      background: rgba(99, 102, 241, 0.12);
      color: #4338ca;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .list-card__empty {
      padding: 22px;
      border-radius: 20px;
      border: 1px dashed rgba(99, 102, 241, 0.3);
      text-align: center;
      color: #94a3b8;
      font-size: 0.88rem;
      backdrop-filter: blur(5px);
    }

    .timeline {
      position: relative;
      display: flex;
      flex-direction: column;
      gap: 20px;
      padding-left: 24px;
    }

    .timeline::before {
      content: '';
      position: absolute;
      left: 8px;
      top: 4px;
      bottom: 4px;
      width: 2px;
      background: linear-gradient(180deg, rgba(99, 102, 241, 0.5), rgba(124, 58, 237, 0.15));
    }

    .timeline__item {
      position: relative;
      padding-left: 10px;
    }

    .timeline__dot {
      position: absolute;
      left: -18px;
      top: 6px;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #7c3aed;
      border: 3px solid #ffffff;
      box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.14);
    }

    .timeline__title {
      font-size: 0.95rem;
      font-weight: 600;
      color: #312e81;
    }

    .timeline__meta {
      font-size: 0.78rem;
      color: #a5b4fc;
      margin-top: 6px;
    }

    .timeline__text {
      margin-top: 6px;
      font-size: 0.85rem;
      color: #475569;
      line-height: 1.5;
    }

    .insight-card {
      border-radius: 26px;
      padding: 24px;
      background: linear-gradient(135deg, #7c3aed, #5b21b6 55%, #4f46e5);
      color: #ffffff;
      box-shadow: 0 32px 64px rgba(76, 29, 149, 0.35);
    }

    .insight-card__title {
      font-size: 0.78rem;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      opacity: 0.75;
      font-weight: 600;
    }

    .insight-card__value {
      margin-top: 10px;
      font-size: 2.6rem;
      font-weight: 700;
    }

    .insight-card__note {
      margin-top: 12px;
      font-size: 0.9rem;
      opacity: 0.85;
      max-width: 40ch;
    }

    .insight-stats {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .insight-stats li {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.9rem;
      color: #475569;
      padding: 10px 0;
      border-bottom: 1px dashed rgba(148, 163, 184, 0.3);
    }

    .insight-stats li:last-child {
      border-bottom: none;
    }

    .insight-stats span:last-child {
      font-weight: 600;
      color: #4338ca;
    }

    .alert-success {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 14px 20px;
      border-radius: 18px;
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(16, 185, 129, 0.05));
      border: 1px solid rgba(16, 185, 129, 0.2);
      color: #047857;
      font-size: 0.92rem;
      font-weight: 500;
      box-shadow: 0 18px 32px rgba(16, 185, 129, 0.15);
    }

    .alert-success::before {
      content: '✔';
      font-weight: 700;
      font-size: 1rem;
    }

    @media (min-width: 768px) {
      .dashboard-hero {
        flex-direction: row;
        align-items: stretch;
      }

      .dashboard-hero__summary {
        width: 280px;
      }

      .chart-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (min-width: 1024px) {
      .metric-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
      }

      .list-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }

      .list-grid--two {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 640px) {
      .dashboard-hero {
        padding: 26px 24px;
      }

      .dashboard-hero__summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
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

  <div class="dashboard-surface">
    <div class="dashboard-shell max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

      @if(session('success'))
        <div class="alert-success">
          {{ session('success') }}
        </div>
      @endif

      <section class="dashboard-hero">
        <div class="dashboard-hero__content">
          <span class="dashboard-hero__eyebrow">ศูนย์ควบคุมระบบ</span>
          <h3 class="dashboard-hero__heading">ภาพรวม Sakol EV Charging</h3>
          <p class="dashboard-hero__lead">
            ตอนนี้มีสถานีพร้อมใช้งาน {{ number_format($stats['stations_active'] ?? 0) }} แห่ง จากทั้งหมด {{ number_format($stats['stations_total'] ?? 0) }} แห่ง
            และมีรายงานใหม่ในเดือนนี้ {{ number_format($stats['reports_this_month'] ?? 0) }} ราย
          </p>
          <div class="dashboard-hero__badges">
            <span class="hero-badge">
              <strong>{{ number_format($stats['reports_pending'] ?? 0) }}</strong>
              รายงานรอจัดการ
            </span>
            <span class="hero-badge hero-badge--soft">
              <strong>{{ number_format($stats['stations_broken'] ?? 0) }}</strong>
              สถานีต้องซ่อม
            </span>
          </div>
        </div>
        <div class="dashboard-hero__summary">
          <div class="hero-kpi">
            <span>สถานีพร้อมใช้งาน</span>
            <strong>{{ number_format($stats['stations_active'] ?? 0) }}</strong>
            <small>คิดเป็น {{ $stats['stations_active_pct'] ?? 0 }}%</small>
          </div>
          <div class="hero-kpi">
            <span>สถานีชำรุด</span>
            <strong>{{ number_format($stats['stations_broken'] ?? 0) }}</strong>
            <small>{{ $stats['stations_broken_pct'] ?? 0 }}% ของทั้งหมด</small>
          </div>
        </div>
      </section>

      <section class="metric-grid">
        <article class="metric-card metric-card--indigo">
          <div class="metric-card__icon">⚡️</div>
          <p class="metric-card__label">สถานีทั้งหมด</p>
          <p class="metric-card__value">{{ number_format($stats['stations_total'] ?? 0) }}</p>
          <p class="metric-card__sub">พร้อมใช้งาน {{ $stats['stations_active_pct'] ?? 0 }}% ของทั้งหมด</p>
        </article>
        <article class="metric-card metric-card--emerald">
          <div class="metric-card__icon">✅</div>
          <p class="metric-card__label">สถานีพร้อมให้บริการ</p>
          <p class="metric-card__value">{{ number_format($stats['stations_active'] ?? 0) }}</p>
          <p class="metric-card__sub">พร้อมรองรับผู้ใช้งานทันที</p>
        </article>
        <article class="metric-card metric-card--rose">
          <div class="metric-card__icon">🛠</div>
          <p class="metric-card__label">สถานีชำรุด</p>
          <p class="metric-card__value">{{ number_format($stats['stations_broken'] ?? 0) }}</p>
          <p class="metric-card__sub">คิดเป็น {{ $stats['stations_broken_pct'] ?? 0 }}% ของทั้งหมด</p>
        </article>
        <article class="metric-card metric-card--amber">
          <div class="metric-card__icon">⏳</div>
          <p class="metric-card__label">สถานีรอตรวจสอบ</p>
          <p class="metric-card__value">{{ number_format($stats['stations_pending'] ?? 0) }}</p>
          <p class="metric-card__sub">เตรียมดำเนินการตรวจสอบเพื่อเปิดใช้งาน</p>
        </article>
      </section>

      <section class="metric-grid metric-grid--secondary">
        <article class="metric-card metric-card--slate">
          <div class="metric-card__icon">👥</div>
          <p class="metric-card__label">ผู้ใช้งานทั้งหมด</p>
          <p class="metric-card__value">{{ number_format($stats['users_total'] ?? 0) }}</p>
          <p class="metric-card__sub">แอดมิน {{ number_format($stats['admins'] ?? 0) }} คน</p>
        </article>
        <article class="metric-card metric-card--purple">
          <div class="metric-card__icon">✨</div>
          <p class="metric-card__label">ผู้ใช้ใหม่ (7 วัน)</p>
          <p class="metric-card__value">{{ number_format($stats['new_users_week'] ?? 0) }}</p>
          <p class="metric-card__sub">ต้อนรับสมาชิกใหม่ในสัปดาห์ที่ผ่านมา</p>
        </article>
        <article class="metric-card metric-card--blue">
          <div class="metric-card__icon">📨</div>
          <p class="metric-card__label">รายงานทั้งหมด</p>
          <p class="metric-card__value">{{ number_format($stats['reports_total'] ?? 0) }}</p>
          <p class="metric-card__sub">รวมทุกประเภทที่แจ้งมายังศูนย์</p>
        </article>
        <article class="metric-card metric-card--rose">
          <div class="metric-card__icon">🚨</div>
          <p class="metric-card__label">รายงานรอจัดการ</p>
          <p class="metric-card__value">{{ number_format($stats['reports_pending'] ?? 0) }}</p>
          <p class="metric-card__sub">มีรายงานใหม่วันนี้ {{ number_format($stats['reports_today'] ?? 0) }} ราย</p>
        </article>
      </section>

      <section class="chart-grid">
        <article class="chart-card">
          <h3 class="chart-card__title">สัดส่วนสถานีตามสถานะ</h3>
          <p class="chart-card__subtitle">อัปเดตแบบเรียลไทม์</p>
          <div class="chart-card__body chart-card__body--donut">
            <div id="stationStatusChart" class="donut-chart" data-total="{{ $stats['stations_total'] ?? 0 }}">
              <div class="donut-chart__label">
                <span>{{ number_format($stats['stations_total'] ?? 0) }}</span>
                <small>สถานี</small>
              </div>
            </div>
          </div>
          <ul class="chart-legend" id="stationStatusLegend">
            @php
              $stationLegendColors = ['#34d399', '#f87171', '#fbbf24'];
            @endphp
            @foreach ($stationChart['labels'] as $idx => $label)
              <li class="chart-legend__item" data-station-row="{{ $idx }}">
                <span class="legend-dot" style="--dot-color: {{ $stationLegendColors[$idx % count($stationLegendColors)] }}"></span>
                <div>
                  <p class="chart-legend__title">{{ $label }}</p>
                  <p class="chart-legend__meta"><span data-count>0</span> แห่ง · <span data-percent>0</span>%</p>
                </div>
              </li>
            @endforeach
          </ul>
          <p class="chart-card__empty" data-chart-empty="station">ยังไม่มีข้อมูลสัดส่วนสถานี</p>
        </article>

        <article class="chart-card">
          <h3 class="chart-card__title">สรุปรายงานตามประเภท</h3>
          <p class="chart-card__subtitle">ภาพรวมประมาณการจากระบบ</p>
          <div class="chart-card__body chart-card__body--bars">
            <div id="reportTypeChart" class="bar-chart" data-total="{{ $stats['reports_total'] ?? 0 }}">
              @php
                $reportBarColors = ['#8b5cf6', '#6366f1', '#7c3aed', '#5b21b6'];
              @endphp
              @foreach ($reportChart['labels'] as $idx => $label)
                <div class="bar-chart__col" data-report-col="{{ $idx }}">
                  <div class="bar-chart__bar">
                    <div class="bar-chart__fill" style="background: linear-gradient(180deg, {{ $reportBarColors[$idx % count($reportBarColors)] }}, rgba(99,102,241,0.75)); height: 0%;"></div>
                  </div>
                  <div class="bar-chart__label">
                    <span>{{ $label }}</span>
                    <small><span data-count>0</span> ราย · <span data-percent>0</span>%</small>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
          <p class="chart-card__empty" data-chart-empty="report">ยังไม่มีข้อมูลการรายงาน</p>
        </article>
      </section>

      <section class="list-grid">
        <article class="list-card">
          <p class="list-card__subtitle">Top 5 พื้นที่</p>
          <h3 class="list-card__title">อำเภอที่มีสถานีมากที่สุด</h3>
          <ul class="list-card__list">
            @forelse ($topDistricts as $row)
              @php
                $districtName = $row->district->name ?? 'ไม่ระบุพื้นที่';
                $share = ($stats['stations_total'] ?? 0) ? round(($row->total / max(1, $stats['stations_total'])) * 100) : 0;
              @endphp
              <li class="list-card__item">
                <div>
                  <p class="list-card__item-title">{{ $districtName }}</p>
                  <p class="list-card__item-meta">{{ number_format($row->total) }} สถานี</p>
                </div>
                <span class="list-card__item-badge">{{ $share }}%</span>
              </li>
            @empty
              <li class="list-card__empty">ยังไม่มีข้อมูลสถานี</li>
            @endforelse
          </ul>
        </article>

        <article class="list-card">
          <p class="list-card__subtitle">ภาพรวมประเภท</p>
          <h3 class="list-card__title">สัดส่วนประเภทปัญหา</h3>
          <ul class="list-card__list">
            @forelse ($reportTypeSummary as $item)
              <li class="list-card__item">
                <div>
                  <p class="list-card__item-title">{{ $item['label'] }}</p>
                  <p class="list-card__item-meta">{{ number_format($item['total']) }} รายการ</p>
                </div>
                <span class="list-card__item-badge">{{ $item['percent'] }}%</span>
              </li>
            @empty
              <li class="list-card__empty">ยังไม่มีการรายงานปัญหา</li>
            @endforelse
          </ul>
        </article>

        <article class="list-card">
          <p class="list-card__subtitle">ไทม์ไลน์ล่าสุด</p>
          <h3 class="list-card__title">รายงานล่าสุด</h3>
          @php
            $reportTypeLabels = [
                'no_power' => 'ไม่มีไฟ / ใช้งานไม่ได้',
                'occupied' => 'มีรถจอดขวาง/ไม่ว่าง',
                'broken'   => 'เครื่องชำรุด',
                'other'    => 'อื่น ๆ',
            ];
          @endphp
          @if($recentReports->isEmpty())
            <div class="list-card__empty">ยังไม่มีรายงานใหม่</div>
          @else
            <ul class="timeline">
              @foreach ($recentReports as $report)
                <li class="timeline__item">
                  <span class="timeline__dot"></span>
                  <div class="timeline__body">
                    <p class="timeline__title">{{ $reportTypeLabels[$report->type] ?? ucfirst($report->type) }}</p>
                    <p class="timeline__meta">{{ $report->created_at?->diffForHumans() }} • {{ $report->station->name ?? '-' }}</p>
                    <p class="timeline__text">{{ \Illuminate\Support\Str::limit($report->message, 80) }}</p>
                  </div>
                </li>
              @endforeach
            </ul>
          @endif
        </article>
      </section>

      <section class="list-grid list-grid--two">
        <article class="list-card">
          <p class="list-card__subtitle">การอัปเดตล่าสุด</p>
          <h3 class="list-card__title">สถานีที่เพิ่งเพิ่มเข้าระบบ</h3>
          <ul class="list-card__list">
            @forelse ($recentStations as $station)
              <li class="list-card__item">
                <div>
                  <p class="list-card__item-title">{{ $station->name }}</p>
                  <p class="list-card__item-meta">{{ $station->district->name ?? 'ไม่ระบุอำเภอ' }}</p>
                </div>
                <span class="list-card__item-badge">{{ $station->created_at?->diffForHumans() }}</span>
              </li>
            @empty
              <li class="list-card__empty">ยังไม่มีสถานีใหม่ในช่วงนี้</li>
            @endforelse
          </ul>
        </article>

        <article class="list-card">
          <p class="list-card__subtitle">Monthly Insight</p>
          <h3 class="list-card__title">การแจ้งปัญหารอบเดือนนี้</h3>
          <div class="insight-card">
            <p class="insight-card__title">รายงานที่ยังรอจัดการ</p>
            <p class="insight-card__value">{{ number_format($stats['reports_pending'] ?? 0) }}</p>
            <p class="insight-card__note">อย่าลืมตรวจสอบเพื่อให้สถานีกลับมาพร้อมใช้งานเร็วที่สุด</p>
          </div>
          <ul class="insight-stats">
            <li><span>รายงานวันนี้</span><span>{{ number_format($stats['reports_today'] ?? 0) }}</span></li>
            <li><span>รายงานเดือนนี้</span><span>{{ number_format($stats['reports_this_month'] ?? 0) }}</span></li>
            <li><span>สถานีที่ต้องตรวจสอบ</span><span>{{ number_format($stats['stations_pending'] ?? 0) }}</span></li>
          </ul>
        </article>
      </section>

    </div>
  </div>
</x-app-layout>

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const numberFormat = (value) => Number(value || 0).toLocaleString('th-TH');

      const stationChartData = @json($stationChart);
      const stationChartEl = document.getElementById('stationStatusChart');
      const stationLegendRows = document.querySelectorAll('[data-station-row]');
      const stationEmpty = document.querySelector('[data-chart-empty="station"]');
      const stationColors = ['#34d399', '#f87171', '#fbbf24'];

      if (stationChartEl && stationChartData) {
        const rawData = (stationChartData.data ?? []).map(v => Number(v || 0));
        const totalStations = rawData.reduce((sum, val) => sum + val, 0);
        const hasData = totalStations > 0;
        const percentages = rawData.map((val, idx, arr) => {
          if (totalStations === 0) {
            return arr.length ? 100 / arr.length : 0;
          }
          return (val / totalStations) * 100;
        });

        let current = 0;
        const segments = percentages.map((pct, idx) => {
          const start = current;
          current += pct;
          return `${stationColors[idx % stationColors.length]} ${start}% ${current}%`;
        }).join(', ');

        if (segments.trim() !== '') {
          stationChartEl.style.setProperty('--segment-colors', segments);
        }
        stationChartEl.classList.toggle('is-empty', !hasData);

        stationLegendRows.forEach((row, idx) => {
          const count = rawData[idx] ?? 0;
          const percent = Math.round(percentages[idx] ?? 0);
          row.querySelector('.legend-dot').style.setProperty('--dot-color', stationColors[idx % stationColors.length]);
          row.querySelector('[data-count]').textContent = numberFormat(count);
          row.querySelector('[data-percent]').textContent = percent;
          row.classList.toggle('is-muted', count === 0);
        });

        if (stationEmpty) {
          stationEmpty.classList.toggle('is-visible', !hasData);
        }
      }

      const reportChartData = @json($reportChart);
      const reportChartEl = document.getElementById('reportTypeChart');
      const reportCols = document.querySelectorAll('[data-report-col]');
      const reportEmpty = document.querySelector('[data-chart-empty="report"]');
      const totalReports = (reportChartData.data ?? []).reduce((sum, val) => sum + Number(val || 0), 0);
      const maxReportValue = Math.max(...(reportChartData.data ?? []).map(v => Number(v || 0)), 0);

      if (reportChartEl && reportChartData && reportCols.length) {
        reportCols.forEach((col, idx) => {
          const count = Number((reportChartData.data ?? [])[idx] || 0);
          const percentOfMax = maxReportValue > 0 ? (count / maxReportValue) * 100 : (totalReports > 0 ? 40 : 0);
          const percentOfTotal = totalReports > 0 ? Math.round((count / totalReports) * 100) : 0;

          const fill = col.querySelector('.bar-chart__fill');
          if (fill) {
            fill.style.height = `${Math.max(percentOfMax, count > 0 ? 12 : 0)}%`;
          }

          const countEl = col.querySelector('[data-count]');
          const percentEl = col.querySelector('[data-percent]');
          if (countEl) countEl.textContent = numberFormat(count);
          if (percentEl) percentEl.textContent = percentOfTotal;
          col.classList.toggle('is-muted', count === 0);
        });

        if (reportEmpty) {
          reportEmpty.classList.toggle('is-visible', totalReports === 0);
        }
      }
    });
  </script>
@endpush
