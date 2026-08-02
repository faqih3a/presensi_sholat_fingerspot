@once
@push('styles')
<style>
    /* Clean overrides for compact green filter widget */
    .tab-filter-container {
        height: 36px !important;
        padding: 3px !important;
        background-color: #fff;
    }
    .filter-tab-btn {
        font-size: 0.85rem !important;
        padding: 0.25rem 0.85rem !important;
        line-height: 1.5 !important;
        height: 28px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        border: none !important;
        font-weight: 600 !important;
    }
    .filter-tab-btn.active-tab {
        background: linear-gradient(310deg, #198754 0%, #2dc57b 100%) !important;
        color: #fff !important;
    }
    .filter-tab-btn:not(.active-tab) {
        color: #198754 !important;
        background-color: transparent !important;
    }
    .nav-arrow-btn {
        width: 36px !important;
        height: 36px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 0.5rem !important;
        background: #fff !important;
        color: #64748b !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-decoration: none !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
        transition: all 0.2s ease !important;
    }
    .nav-arrow-btn:hover {
        background-color: #f8f9fa !important;
        color: #198754 !important;
        border-color: #198754 !important;
    }
    .date-display-pill {
        min-width: 140px !important;
        height: 36px !important;
        font-size: 0.85rem !important;
        font-weight: 700 !important;
        border: 1px solid #cbd5e1 !important;
        color: #334155 !important;
        background: #fff !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
        padding: 0 1.25rem !important;
        cursor: pointer;
    }
    .date-display-pill::after {
        display: none !important;
    }
    .month-grid-item {
        transition: all 0.2s ease;
        border-radius: 9999px !important;
        font-size: 0.85rem !important;
        padding: 0.35rem 0 !important;
    }
    .month-grid-item.active-month {
        background: linear-gradient(310deg, #198754 0%, #2dc57b 100%) !important;
        color: #fff !important;
    }
    .month-grid-item:hover:not(.active-month) {
        background-color: #f1f5f9;
        color: #198754 !important;
    }

    /* ─── Custom Date Range Panel ─────────────────────────── */
    .custom-date-panel {
        animation: slideDown 0.25s ease-out;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .custom-date-input {
        height: 36px;
        font-size: 0.82rem;
        font-weight: 600;
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        padding: 0 0.6rem;
        color: #334155;
        background: #fff;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        transition: border-color 0.2s, box-shadow 0.2s;
        min-width: 130px;
    }
    .custom-date-input:focus {
        outline: none;
        border-color: #198754;
        box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.12);
    }
    .custom-date-separator {
        font-size: 0.75rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .custom-date-apply-btn {
        height: 36px;
        font-size: 0.82rem;
        font-weight: 700;
        padding: 0 1rem;
        border: none;
        border-radius: 0.5rem;
        background: linear-gradient(310deg, #198754 0%, #2dc57b 100%);
        color: #fff;
        box-shadow: 0 2px 6px rgba(25, 135, 84, 0.25);
        transition: all 0.2s ease;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    .custom-date-apply-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(25, 135, 84, 0.3);
    }
    
    /* Dark mode overrides */
    body.dark-mode .tab-filter-container,
    body.dark-mode .nav-arrow-btn,
    body.dark-mode .date-display-pill {
        background-color: #1e1e1e !important;
        border-color: #333333 !important;
        color: #e2e8f0 !important;
    }
    body.dark-mode .nav-arrow-btn:hover {
        background-color: #2d2d2d !important;
        color: #2dc57b !important;
        border-color: #2dc57b !important;
    }
    body.dark-mode .filter-tab-btn:not(.active-tab) {
        color: #2dc57b !important;
    }
    body.dark-mode .month-grid-dropdown {
        background-color: #1e1e1e !important;
        border: 1px solid #333333 !important;
    }
    body.dark-mode .month-grid-item:hover:not(.active-month) {
        background-color: #2d2d2d;
        color: #2dc57b !important;
    }
    body.dark-mode .month-grid-item.text-secondary {
        color: #94a3b8 !important;
    }
    body.dark-mode .custom-date-input {
        background-color: #1e1e1e;
        border-color: #333333;
        color: #e2e8f0;
    }
    body.dark-mode .custom-date-input:focus {
        border-color: #2dc57b;
        box-shadow: 0 0 0 3px rgba(45, 197, 123, 0.15);
    }
</style>
@endpush
@endonce

<div class="d-flex flex-column gap-2">
    {{-- Row 1: Mode tabs + Date navigation --}}
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <!-- Date mode filter buttons -->
        <div class="d-inline-flex bg-white border rounded-3 shadow-sm tab-filter-container">
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'day', 'ref_date' => $ref_date, 'start_date' => null, 'end_date' => null]) }}" 
               class="btn btn-sm px-3 filter-tab-btn {{ $mode === 'day' ? 'active-tab' : '' }}">
                Day
            </a>
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'week', 'ref_date' => $ref_date, 'start_date' => null, 'end_date' => null]) }}" 
               class="btn btn-sm px-3 filter-tab-btn {{ $mode === 'week' ? 'active-tab' : '' }}">
                Week
            </a>
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'month', 'ref_date' => $ref_date, 'start_date' => null, 'end_date' => null]) }}" 
               class="btn btn-sm px-3 filter-tab-btn {{ $mode === 'month' ? 'active-tab' : '' }}">
                Month
            </a>
            <a href="{{ request()->fullUrlWithQuery(['mode' => 'custom', 'start_date' => request('start_date', now()->subWeek()->format('Y-m-d')), 'end_date' => request('end_date', now()->format('Y-m-d'))]) }}" 
               class="btn btn-sm px-3 filter-tab-btn {{ $mode === 'custom' ? 'active-tab' : '' }}">
                <i class="bi bi-calendar-range me-1" style="font-size: 0.75rem;"></i>Custom
            </a>
        </div>

        {{-- Date navigation controls (hidden on custom mode) --}}
        @if($mode !== 'custom')
        <div class="d-flex align-items-center gap-2">
            <!-- Previous Arrow -->
            @if($prev_date)
            <a href="{{ request()->fullUrlWithQuery(['ref_date' => $prev_date]) }}" class="nav-arrow-btn">
                <i class="bi bi-chevron-left" style="-webkit-text-stroke: 0.5px;"></i>
            </a>
            @endif

            <!-- Date Display Label -->
            <div class="dropdown d-inline-block">
                <button class="d-flex align-items-center justify-content-center date-display-pill dropdown-toggle border-0" 
                        type="button" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false">
                    {{ $display_date }}
                </button>
                <div class="dropdown-menu dropdown-menu-end p-3 border-0 shadow-lg month-grid-dropdown" style="width: 240px; border-radius: 1rem; margin-top: 5px;">
                    <div class="row g-2 text-center m-0">
                        @php
                            $activeYear = \Carbon\Carbon::parse($ref_date)->format('Y');
                            $activeMonthNum = \Carbon\Carbon::parse($ref_date)->month;
                            $shortMonths = [
                                1 => 'Jan', 2 => 'Feb', 3 => 'Mar',
                                4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
                                7 => 'Jul', 8 => 'Agt', 9 => 'Sep',
                                10 => 'Okt', 11 => 'Nov', 12 => 'Des'
                            ];
                        @endphp
                        @foreach($shortMonths as $mNum => $mLabel)
                            <div class="col-4 p-1">
                                <a href="{{ request()->fullUrlWithQuery(['mode' => $mode, 'ref_date' => "$activeYear-" . sprintf('%02d', $mNum) . "-01"]) }}" 
                                   class="d-block text-decoration-none fw-bold month-grid-item {{ $activeMonthNum == $mNum ? 'active-month' : 'text-secondary' }}">
                                    {{ $mLabel }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Next Arrow -->
            @if($next_date)
            <a href="{{ request()->fullUrlWithQuery(['ref_date' => $next_date]) }}" class="nav-arrow-btn">
                <i class="bi bi-chevron-right" style="-webkit-text-stroke: 0.5px;"></i>
            </a>
            @endif
        </div>
        @endif
    </div>

    {{-- Row 2: Custom date range inputs (only visible when mode = custom) --}}
    @if($mode === 'custom')
    <div class="custom-date-panel">
        <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-2 flex-wrap no-loader">
            {{-- Preserve existing query params (search, status, kelas, etc.) --}}
            @foreach(request()->except(['mode', 'start_date', 'end_date', 'ref_date', 'page', 'tanggal_mulai', 'tanggal_akhir']) as $key => $value)
                @if(is_string($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <input type="hidden" name="mode" value="custom">

            <div class="d-flex align-items-center gap-2">
                <label for="custom_start_date" class="custom-date-separator mb-0">Dari</label>
                <input type="date" 
                       id="custom_start_date"
                       name="start_date" 
                       value="{{ request('start_date', $tanggal_mulai) }}" 
                       class="custom-date-input"
                       required>
            </div>

            <span class="custom-date-separator">—</span>

            <div class="d-flex align-items-center gap-2">
                <label for="custom_end_date" class="custom-date-separator mb-0">Sampai</label>
                <input type="date" 
                       id="custom_end_date"
                       name="end_date" 
                       value="{{ request('end_date', $tanggal_akhir) }}" 
                       class="custom-date-input"
                       required>
            </div>

            <button type="submit" class="custom-date-apply-btn">
                <i class="bi bi-funnel-fill" style="font-size: 0.75rem;"></i>
                Terapkan
            </button>
        </form>
    </div>
    @endif
</div>
