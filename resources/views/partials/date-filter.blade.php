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
</style>
@endpush
@endonce

<div class="d-flex align-items-center gap-3 flex-wrap">
    <!-- Date mode filter buttons -->
    <div class="d-inline-flex bg-white border rounded-3 shadow-sm tab-filter-container">
        <a href="{{ request()->fullUrlWithQuery(['mode' => 'day', 'ref_date' => $ref_date]) }}" 
           class="btn btn-sm px-3 filter-tab-btn {{ $mode === 'day' ? 'active-tab' : '' }}">
            Day
        </a>
        <a href="{{ request()->fullUrlWithQuery(['mode' => 'week', 'ref_date' => $ref_date]) }}" 
           class="btn btn-sm px-3 filter-tab-btn {{ $mode === 'week' ? 'active-tab' : '' }}">
            Week
        </a>
        <a href="{{ request()->fullUrlWithQuery(['mode' => 'month', 'ref_date' => $ref_date]) }}" 
           class="btn btn-sm px-3 filter-tab-btn {{ $mode === 'month' ? 'active-tab' : '' }}">
            Month
        </a>
    </div>

    <!-- Date navigation controls -->
    <div class="d-flex align-items-center gap-2">
        <!-- Previous Arrow -->
        <a href="{{ request()->fullUrlWithQuery(['ref_date' => $prev_date]) }}" class="nav-arrow-btn">
            <i class="bi bi-chevron-left" style="-webkit-text-stroke: 0.5px;"></i>
        </a>

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
        <a href="{{ request()->fullUrlWithQuery(['ref_date' => $next_date]) }}" class="nav-arrow-btn">
            <i class="bi bi-chevron-right" style="-webkit-text-stroke: 0.5px;"></i>
        </a>
    </div>
</div>
