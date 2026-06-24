<style>
    .rpt-shell {
        display: grid;
        grid-template-columns: 240px 1fr;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 991.98px) {
        .rpt-shell { grid-template-columns: 1fr; gap: 16px; }
        .rpt-aside { position: static !important; }
    }
    .rpt-aside {
        position: sticky;
        top: 80px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    .rpt-aside-section { display: flex; flex-direction: column; gap: 8px; }
    .rpt-aside-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #94a3b8;
        margin-bottom: 4px;
    }
    .rpt-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .rpt-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        font-size: .82rem;
        font-weight: 600;
        color: #475569;
        background: #f1f5f9;
        border: 1px solid transparent;
        border-radius: 999px;
        text-decoration: none;
        transition: all .15s ease;
    }
    .rpt-chip:hover { background: #e2e8f0; color: #0f172a; }
    .rpt-chip.is-active {
        background: #0f172a;
        color: #fff;
        border-color: #0f172a;
    }
    .rpt-nav { display: flex; flex-direction: column; gap: 2px; }
    .rpt-nav-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        font-size: .9rem;
        font-weight: 600;
        color: #475569;
        text-decoration: none;
        border-radius: 10px;
        transition: all .15s ease;
    }
    .rpt-nav-link i { font-size: 1.05rem; width: 18px; text-align: center; }
    .rpt-nav-link:hover { background: #f1f5f9; color: #0f172a; }
    .rpt-nav-link.is-active {
        background: #eff6ff;
        color: #1d4ed8;
    }
    .rpt-aside-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 14px;
        font-size: .88rem;
        font-weight: 600;
        background: #0f172a;
        color: #fff;
        border: none;
        border-radius: 10px;
        text-decoration: none;
        transition: all .15s ease;
    }
    .rpt-aside-btn:hover { background: #1e293b; color: #fff; transform: translateY(-1px); }
    .rpt-aside-section > .rpt-aside-btn { display: flex; width: 100%; }
    .rpt-aside-section > .rpt-aside-btn + .rpt-aside-btn { margin-top: 8px; }
    .rpt-aside-btn-ghost {
        background: transparent;
        color: #475569;
        border: 1px solid #e2e8f0;
        font-weight: 500;
    }
    .rpt-aside-btn-ghost:hover { background: #f1f5f9; color: #0f172a; }

    .rpt-main { min-width: 0; }
    .rpt-header { margin-bottom: 20px; display: flex; justify-content: space-between; align-items: end; flex-wrap: wrap; gap: 12px; }
    .rpt-header h1 { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0 0 4px; }
    .rpt-header-sub { font-size: .85rem; color: #64748b; margin: 0; }
    .rpt-header-sub i { color: #94a3b8; }

    .rpt-kpis {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }
    @media (max-width: 767.98px) { .rpt-kpis { grid-template-columns: repeat(2, 1fr); } }
    .rpt-kpi {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .rpt-kpi-label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .rpt-kpi-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
    }
    .rpt-kpi-sub {
        font-size: .78rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .rpt-kpi-delta {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: .78rem;
        font-weight: 700;
    }
    .rpt-kpi-delta.is-up { color: #059669; }
    .rpt-kpi-delta.is-down { color: #dc2626; }
    .rpt-kpi.is-accent-success .rpt-kpi-value { color: #059669; }
    .rpt-kpi.is-accent-primary .rpt-kpi-value { color: #1d4ed8; }
    .rpt-kpi.is-accent-warning .rpt-kpi-value { color: #b45309; }
    .rpt-kpi.is-accent-danger .rpt-kpi-value { color: #dc2626; }

    .rpt-section {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 16px;
    }
    .rpt-section-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }
    .rpt-section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .rpt-section-title i { color: #64748b; }
    .rpt-section-sub { font-size: .8rem; color: #94a3b8; }

    .rpt-grid-2 {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }
    @media (max-width: 991.98px) { .rpt-grid-2 { grid-template-columns: 1fr; } }
    .rpt-grid-2 > .rpt-section { margin-bottom: 0; }

    .rpt-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }
    @media (max-width: 767.98px) { .rpt-grid-3 { grid-template-columns: 1fr; } }

    .rpt-rank { display: flex; flex-direction: column; gap: 10px; }
    .rpt-rank-row { display: flex; align-items: center; gap: 12px; }
    .rpt-rank-pos {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: #f1f5f9;
        color: #475569;
        font-size: .82rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .rpt-rank-row:nth-child(1) .rpt-rank-pos { background: #fef3c7; color: #b45309; }
    .rpt-rank-row:nth-child(2) .rpt-rank-pos { background: #e0e7ff; color: #4338ca; }
    .rpt-rank-row:nth-child(3) .rpt-rank-pos { background: #fce7f3; color: #be185d; }
    .rpt-rank-body { flex: 1; min-width: 0; }
    .rpt-rank-line {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 4px;
    }
    .rpt-rank-name {
        font-size: .9rem;
        font-weight: 600;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding-right: 8px;
    }
    .rpt-rank-meta { font-size: .8rem; color: #64748b; white-space: nowrap; }
    .rpt-bar {
        height: 6px;
        background: #f1f5f9;
        border-radius: 999px;
        overflow: hidden;
    }
    .rpt-bar > span {
        display: block;
        height: 100%;
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
        border-radius: 999px;
    }
    .rpt-bar.is-success > span { background: linear-gradient(90deg, #10b981, #059669); }
    .rpt-bar.is-warning > span { background: linear-gradient(90deg, #f59e0b, #d97706); }
    .rpt-bar.is-danger > span { background: linear-gradient(90deg, #ef4444, #dc2626); }

    .rpt-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .rpt-table th {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #94a3b8;
        padding: 8px 10px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }
    .rpt-table th.text-end { text-align: right; }
    .rpt-table td {
        padding: 12px 10px;
        font-size: .9rem;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    .rpt-table td.text-end { text-align: right; }
    .rpt-table tr:last-child td { border-bottom: none; }
    .rpt-table tbody tr:hover td { background: #f8fafc; }

    .rpt-empty {
        text-align: center;
        padding: 40px 20px;
        color: #94a3b8;
    }
    .rpt-empty i { font-size: 2.5rem; opacity: .4; display: block; margin-bottom: 8px; }
    .rpt-empty p { margin: 0; font-size: .9rem; }

    .rpt-tour-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: #fff;
    }
    .rpt-tour-card-head { display: flex; justify-content: space-between; align-items: start; gap: 12px; }
    .rpt-tour-card-name {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .rpt-tour-card-cap { font-size: .78rem; color: #94a3b8; margin: 0; }
    .rpt-tour-card-rate {
        font-size: 1.7rem;
        font-weight: 800;
        line-height: 1;
    }
    .rpt-tour-card-rate.is-success { color: #059669; }
    .rpt-tour-card-rate.is-warning { color: #d97706; }
    .rpt-tour-card-rate.is-danger { color: #dc2626; }
    .rpt-tour-card-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
    }
    .rpt-tour-card-stat-label { font-size: .7rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; font-weight: 600; }
    .rpt-tour-card-stat-value { font-size: .95rem; font-weight: 700; color: #0f172a; }

    .rpt-slot-tile {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px;
        text-align: center;
        background: #fff;
    }
    .rpt-slot-time {
        font-size: 1.15rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 4px;
    }
    .rpt-slot-count {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1d4ed8;
        margin: 0;
        line-height: 1;
    }
    .rpt-slot-sub {
        font-size: .75rem;
        color: #94a3b8;
        margin: 4px 0 0;
    }
</style>
