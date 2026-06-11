@extends('layouts.app')
@section('title', 'Dashboard')
@push('styles')
<style>
.stat-card{border-radius:1rem;padding:1.25rem 1.5rem;display:flex;justify-content:space-between;align-items:center;border:1px solid #edf2f9;transition:all .3s ease;background:#fff}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(0,0,0,.08)}
.stat-card .icon-box{width:48px;height:48px;border-radius:.75rem;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.stat-card .stat-value{font-size:1.75rem;font-weight:800;line-height:1.2}
.stat-card .stat-label{font-size:.8rem;font-weight:600;color:#8898aa;margin-bottom:.25rem}
.stat-card .stat-sub{font-size:.7rem;color:#adb5bd}
.prayer-section{border-radius:1rem;padding:1.5rem;border:1px solid #edf2f9;background:#fff}
.prayer-card{border:2px solid #edf2f9;border-radius:.75rem;padding:.75rem 1rem;text-align:left;min-width:120px;position:relative;transition:all .2s}
.prayer-card.next-prayer{border-color:#198754;background:rgba(25,135,84,.05)}
.prayer-card .prayer-name{font-size:.8rem;color:#8898aa;font-weight:600;display:flex;align-items:center;gap:.35rem}
.prayer-card .prayer-time{font-size:1.35rem;font-weight:800;color:#333}
.prayer-card .next-badge{position:absolute;top:.4rem;right:.5rem;background:#198754;color:#fff;font-size:.55rem;font-weight:700;padding:.15rem .4rem;border-radius:.25rem;text-transform:uppercase}
.chart-card{border-radius:1rem;padding:1.5rem;border:1px solid #edf2f9;background:#fff}
.chart-card .chart-title{font-size:1rem;font-weight:700;color:#333}
.chart-card .chart-sub{font-size:.75rem;color:#8898aa}
.prayer-meta{display:flex;align-items:center;gap:.5rem;font-size:.75rem;color:#8898aa;margin-top:1rem;padding:.75rem 1rem;background:#f8f9fa;border-radius:.5rem}
.prayer-meta .live-dot{width:8px;height:8px;border-radius:50%;background:#198754;display:inline-block;animation:pulse-live 2s infinite}
@keyframes pulse-live{0%,100%{box-shadow:0 0 0 0 rgba(25,135,84,.4)}50%{box-shadow:0 0 0 6px rgba(25,135,84,0)}}
.refresh-btn{border:1px solid #edf2f9;border-radius:.5rem;background:#fff;color:#8898aa;font-size:.8rem;font-weight:600;padding:.4rem .75rem;cursor:pointer;transition:all .2s}
.refresh-btn:hover{border-color:#198754;color:#198754}
body.dark-mode .stat-card,body.dark-mode .prayer-section,body.dark-mode .chart-card{background:#1e1e1e;border-color:#333}
body.dark-mode .stat-card .stat-value,body.dark-mode .prayer-card .prayer-time,body.dark-mode .chart-card .chart-title{color:#f8f9fa!important}
body.dark-mode .prayer-card{border-color:#333}
body.dark-mode .prayer-card.next-prayer{border-color:#198754;background:rgba(25,135,84,.1)}
body.dark-mode .prayer-meta{background:#2c2c2c}
body.dark-mode .refresh-btn{background:#2c2c2c;border-color:#333;color:#adb5bd}
</style>
@endpush
@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h1 class="h3 mb-0 text-dark fw-bold">Dashboard</h1>
        <p class="text-muted mb-0">Selamat datang di sistem presensi sholat</p>
    </div>
    @include('partials.date-filter')
</div>

{{-- 4 Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card h-100">
            <div>
                <div class="stat-label">Total Jamaah Terdaftar</div>
                <div class="stat-value text-dark">{{ number_format($totalSantri) }}</div>
                <div class="stat-sub">Santri terdaftar aktif</div>
            </div>
            <div class="icon-box" style="background:rgba(25,135,84,.12)"><i class="bi bi-people-fill text-success"></i></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card h-100">
            <div>
                <div class="stat-label">Jamaah Hadir Hari Ini</div>
                <div class="stat-value text-dark">{{ number_format($jamaahHadirHariIni) }}</div>
                <div class="stat-sub">{{ $totalSantri > 0 ? round(($jamaahHadirHariIni/$totalSantri)*100) : 0 }}% dari total jamaah</div>
            </div>
            <div class="icon-box" style="background:rgba(25,135,84,.12)"><i class="bi bi-person-check-fill text-success"></i></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card h-100">
            <div>
                <div class="stat-label">Ketepatan Waktu</div>
                <div class="stat-value text-dark">{{ $ketepatanWaktu }}%</div>
                <div class="stat-sub">Hari ini</div>
            </div>
            <div class="icon-box" style="background:rgba(25,135,84,.12)"><i class="bi bi-graph-up-arrow text-success"></i></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card h-100">
            <div>
                <div class="stat-label">Total Scan Hari Ini</div>
                <div class="stat-value text-dark">{{ number_format($totalScanHariIni) }}</div>
                <div class="stat-sub">Semua scan hari ini</div>
            </div>
            <div class="icon-box" style="background:rgba(25,135,84,.12)"><i class="bi bi-calendar-check-fill text-success"></i></div>
        </div>
    </div>
</div>

{{-- Prayer Times Section --}}
<div class="prayer-section mb-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock text-dark" style="font-size:1.2rem"></i>
            <div>
                <div class="fw-bold text-dark" style="font-size:1.05rem">Waktu Sholat Hari Ini</div>
                <div class="text-muted" style="font-size:.75rem">BOGOR, CIBEUREUM, KP JOGLO</div>
            </div>
        </div>
        <a href="{{ request()->fullUrl() }}" class="refresh-btn text-decoration-none"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</a>
    </div>
    @if($jadwal)
    <div class="d-flex gap-3 flex-wrap">
        @php
        $prayers = [
            ['name'=>'Subuh','icon'=>'bi-moon-stars','key'=>'Fajr'],
            ['name'=>'Syuruq','icon'=>'bi-sunrise','key'=>'Sunrise'],
            ['name'=>'Dzuhur','icon'=>'bi-sun','key'=>'Dhuhr'],
            ['name'=>'Ashar','icon'=>'bi-cloud-sun','key'=>'Asr'],
            ['name'=>'Maghrib','icon'=>'bi-sunset','key'=>'Maghrib'],
            ['name'=>'Isya','icon'=>'bi-moon','key'=>'Isha'],
        ];
        @endphp
        @foreach($prayers as $p)
        <div class="prayer-card flex-fill {{ $nextPrayer === $p['name'] ? 'next-prayer' : '' }}">
            @if($nextPrayer === $p['name'])<span class="next-badge">NEXT</span>@endif
            <div class="prayer-name"><i class="bi {{ $p['icon'] }}"></i> {{ $p['name'] }}</div>
            <div class="prayer-time">{{ $jadwal[$p['key']] ?? '-' }}</div>
        </div>
        @endforeach
    </div>
    <div class="prayer-meta">
        <i class="bi bi-calendar3"></i>
        <span>Tanggal: {{ \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->isoFormat('dddd, DD/MM/YYYY') }}</span>
        <span class="ms-2">Sumber: API Aladhan • Data real-time</span>
        <span class="ms-auto d-flex align-items-center gap-1"><span class="live-dot"></span> Live</span>
    </div>
    @else
    <div class="text-center text-muted py-4"><i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>Gagal memuat jadwal sholat.</div>
    @endif
</div>

{{-- Charts Section --}}
<div class="row g-4 mb-4">
    <div class="col-lg-7 d-flex flex-column">
        <div class="chart-card w-100 flex-grow-1 d-flex flex-column">
            <div class="mb-3">
                <div class="chart-title">Tren Kehadiran Mingguan</div>
                <div class="chart-sub">Perbandingan Hadir, Izin, dan Alfa 7 hari terakhir</div>
            </div>
            <div style="height:280px" class="flex-grow-1"><canvas id="weeklyChart"></canvas></div>
            <div class="mt-3 p-2 rounded-3 d-flex align-items-start gap-2" style="background: rgba(25, 135, 84, 0.05); border: 1px dashed rgba(25, 135, 84, 0.2);">
                <i class="bi bi-info-circle-fill text-success" style="font-size: 0.85rem; margin-top: 1px;"></i>
                <span class="text-muted" style="font-size: 0.72rem; line-height: 1.4;">{{ $weeklyInsight }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg-5 d-flex flex-column">
        <div class="chart-card w-100 flex-grow-1 d-flex flex-column">
            <div class="mb-3">
                <div class="chart-title">Proporsi Kehadiran Hari Ini</div>
                <div class="chart-sub">Persentase status kehadiran santri</div>
            </div>
            <div style="height:280px" class="position-relative flex-grow-1"><canvas id="statusChart"></canvas></div>
            <div class="mt-3 p-2 rounded-3 d-flex align-items-start gap-2" style="background: rgba(255, 193, 7, 0.05); border: 1px dashed rgba(255, 193, 7, 0.2);">
                <i class="bi bi-lightbulb-fill text-warning" style="font-size: 0.85rem; margin-top: 1px;"></i>
                <span class="text-muted" style="font-size: 0.72rem; line-height: 1.4;">{{ $todayInsight }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Izin & Alfa + Activity --}}
<div class="row g-4 mb-4">
    <div class="col-lg-6 d-flex flex-column">
        <div class="chart-card w-100 flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="chart-title mb-0">Santri Izin (Periode)</h5>
                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3">{{ $izinTodayRecords->count() }} Santri</span>
            </div>
            @if($izinTodayRecords->isNotEmpty())
            <div class="d-flex align-items-center justify-content-between bg-light p-3 rounded-4">
                <div class="avatar-group d-flex align-items-center">
                    @foreach($izinTodayRecords->take(6) as $santriId => $records)
                    @php $santri = $records->first()->santri; @endphp
                    @if($santri->display_photo)
                    <img src="{{ $santri->display_photo }}" class="rounded-circle" style="width:35px;height:35px;border:2px solid #fff;margin-left:-12px;object-fit:cover" title="{{ $santri->nama }}">
                    @else
                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width:35px;height:35px;border:2px solid #fff;margin-left:-12px" title="{{ $santri->nama }}"><i class="bi bi-person" style="font-size:.8rem"></i></div>
                    @endif
                    @endforeach
                </div>
                <span class="badge bg-info bg-opacity-10 text-info small">{{ $izinTodayRecords->count() }} orang</span>
            </div>
            @else
            <div class="text-center py-4 bg-light rounded-4"><i class="bi bi-emoji-smile fs-3 text-muted d-block mb-2"></i><p class="text-muted small mb-0">Tidak ada santri izin.</p></div>
            @endif
        </div>
    </div>
    <div class="col-lg-6 d-flex flex-column">
        <div class="chart-card w-100 flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="chart-title mb-0">Santri Alfa (Periode)</h5>
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3">{{ $alfaTodayRecords->count() }} Santri</span>
            </div>
            @if($alfaTodayRecords->isNotEmpty())
            <div class="d-flex align-items-center justify-content-between bg-light p-3 rounded-4">
                <div class="avatar-group d-flex align-items-center">
                    @foreach($alfaTodayRecords->take(6) as $santriId => $records)
                    @php $santri = $records->first()->santri; @endphp
                    @if($santri->display_photo)
                    <img src="{{ $santri->display_photo }}" class="rounded-circle" style="width:35px;height:35px;border:2px solid #fff;margin-left:-12px;object-fit:cover" title="{{ $santri->nama }}">
                    @else
                    <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width:35px;height:35px;border:2px solid #fff;margin-left:-12px" title="{{ $santri->nama }}"><i class="bi bi-person" style="font-size:.8rem"></i></div>
                    @endif
                    @endforeach
                </div>
                <span class="badge bg-danger bg-opacity-10 text-danger small">{{ $alfaTodayRecords->count() }} orang</span>
            </div>
            @else
            <div class="text-center py-4 bg-light rounded-4"><i class="bi bi-check-circle fs-3 text-success d-block mb-2"></i><p class="text-muted small mb-0">Tidak ada santri alfa.</p></div>
            @endif
        </div>
    </div>
</div>

{{-- Recent Activity --}}
<div class="row g-4">
    <div class="col-lg-12">
        <div class="chart-card">
            <h5 class="chart-title mb-4">Aktivitas Terbaru</h5>
            @forelse($recentActivities as $activity)
            <div class="d-flex align-items-center {{ $loop->last ? '' : 'mb-4' }}">
                <div class="position-relative me-3">
                    @if($activity->avatar)
                    <img src="{{ $activity->avatar }}" alt="Avatar" class="rounded-circle object-fit-cover" style="width:45px;height:45px">
                    @else
                    <div class="bg-light text-secondary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:45px;height:45px"><i class="bi bi-person fs-4"></i></div>
                    @endif
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div><span class="fw-bold text-dark small">{{ $activity->name }}</span> <span class="badge bg-light text-muted border ms-1" style="font-size:.65rem">{{ $activity->detail }}</span></div>
                        <span class="text-muted" style="font-size:.75rem">{{ $activity->scan_time->locale('id')->diffForHumans() }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-1" style="font-size:.75rem">
                        <span class="badge bg-{{ $activity->color }} bg-opacity-10 text-{{ $activity->color }} border border-{{ $activity->color }} border-opacity-25 py-0 px-2 d-inline-flex align-items-center gap-1" style="font-size:.75rem;line-height:1.5"><i class="bi {{ $activity->verify_icon }}"></i> {{ $activity->verify_method }}</span>
                        <span class="text-secondary">•</span>
                        <span class="fw-semibold text-{{ $activity->color }}">{{ $activity->status_scan_label }}</span>
                        <span class="text-secondary">•</span>
                        <span class="text-black-50" style="font-size:.7rem">{{ $activity->scan_time->format('H:i') }} WIB</span>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-4 bg-light rounded-4"><i class="bi bi-activity fs-3 text-muted d-block mb-2"></i><p class="text-muted small mb-0">Belum ada aktivitas.</p></div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded",function(){
    const isDark=document.body.classList.contains('dark-mode');
    const gridColor=isDark?'rgba(255,255,255,.06)':'#f0f0f0';
    const tickColor=isDark?'#adb5bd':'#8898aa';

    // Center text plugin for Doughnut chart
    const centerTextPlugin = {
        id: 'centerText',
        afterDraw(chart, args, options) {
            const { ctx, chartArea: { top, bottom, left, right, width, height } } = chart;
            ctx.save();
            
            const textVal = options.textValue || '0';
            const textLabel = options.textLabel || 'Total';
            
            // Value Text
            ctx.font = 'bold 2rem "Outfit", sans-serif';
            ctx.fillStyle = isDark ? '#f8f9fa' : '#333';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(textVal, left + width / 2, top + height / 2 - 8);
            
            // Label Text
            ctx.font = '600 0.8rem "Outfit", sans-serif';
            ctx.fillStyle = '#8898aa';
            ctx.fillText(textLabel, left + width / 2, top + height / 2 + 18);
            ctx.restore();
        }
    };

    // Weekly Attendance Chart (Hadir, Izin, Alfa)
    const wCtx=document.getElementById('weeklyChart').getContext('2d');
    
    const hadirGrad=wCtx.createLinearGradient(0,0,0,250);
    hadirGrad.addColorStop(0,'rgba(25,135,84,.2)');
    hadirGrad.addColorStop(1,'rgba(25,135,84,0)');

    const izinGrad=wCtx.createLinearGradient(0,0,0,250);
    izinGrad.addColorStop(0,'rgba(255,193,7,.2)');
    izinGrad.addColorStop(1,'rgba(255,193,7,0)');

    const alfaGrad=wCtx.createLinearGradient(0,0,0,250);
    alfaGrad.addColorStop(0,'rgba(220,53,69,.2)');
    alfaGrad.addColorStop(1,'rgba(220,53,69,0)');

    new Chart(wCtx,{
        type:'line',
        data:{
            labels:{!! json_encode($weeklyLabels) !!},
            datasets:[
                {
                    label:'Hadir',
                    data:{!! json_encode($weeklyHadir) !!},
                    borderColor:'#198754',
                    backgroundColor:hadirGrad,
                    borderWidth:3,
                    pointBackgroundColor:'#198754',
                    pointBorderColor:isDark?'#1e1e1e':'#fff',
                    pointBorderWidth:2,
                    pointRadius:3,
                    pointHoverRadius:5,
                    fill:true,
                    tension:.35
                },
                {
                    label:'Izin',
                    data:{!! json_encode($weeklyIzin) !!},
                    borderColor:'#ffc107',
                    backgroundColor:izinGrad,
                    borderWidth:3,
                    pointBackgroundColor:'#ffc107',
                    pointBorderColor:isDark?'#1e1e1e':'#fff',
                    pointBorderWidth:2,
                    pointRadius:3,
                    pointHoverRadius:5,
                    fill:true,
                    tension:.35
                },
                {
                    label:'Alfa',
                    data:{!! json_encode($weeklyAlfa) !!},
                    borderColor:'#dc3545',
                    backgroundColor:alfaGrad,
                    borderWidth:3,
                    pointBackgroundColor:'#dc3545',
                    pointBorderColor:isDark?'#1e1e1e':'#fff',
                    pointBorderWidth:2,
                    pointRadius:3,
                    pointHoverRadius:5,
                    fill:true,
                    tension:.35
                }
            ]
        },
        options:{
            responsive:true,
            maintainAspectRatio:false,
            plugins:{
                legend:{
                    display:true,
                    position:'top',
                    labels:{
                        color:tickColor,
                        font:{family:'Outfit, sans-serif',size:11}
                    }
                },
                tooltip:{
                    backgroundColor:isDark?'#2c2c2c':'#fff',
                    titleColor:isDark?'#f8f9fa':'#333',
                    bodyColor:isDark?'#adb5bd':'#666',
                    borderColor:isDark?'#444':'#ddd',
                    borderWidth:1,
                    padding:10,
                    displayColors:true
                }
            },
            scales:{
                x:{grid:{display:false},ticks:{color:tickColor,font:{family:'Outfit, sans-serif',size:11}}},
                y:{grid:{color:gridColor,drawBorder:false},ticks:{color:tickColor,stepSize:5},min:0}
            }
        }
    });

    // Today status doughnut chart
    const sCtx=document.getElementById('statusChart').getContext('2d');
    const statusData={!! json_encode($statusData) !!};
    const totalSantri={{ $totalSantri }};

    new Chart(sCtx,{
        type:'doughnut',
        data:{
            labels:['Hadir','Izin','Alfa'],
            datasets:[{
                data:statusData,
                backgroundColor:['#198754','#ffc107','#dc3545'],
                borderWidth:isDark?2:1,
                borderColor:isDark?'#1e1e1e':'#fff',
                hoverOffset:4
            }]
        },
        plugins:[centerTextPlugin],
        options:{
            responsive:true,
            maintainAspectRatio:false,
            cutout:'70%',
            plugins:{
                legend:{
                    display:true,
                    position:'right',
                    labels:{
                        color:tickColor,
                        font:{family:'Outfit, sans-serif',size:11}
                    }
                },
                centerText:{
                    textValue:totalSantri.toString(),
                    textLabel:'Total Santri'
                },
                tooltip:{
                    backgroundColor:isDark?'#2c2c2c':'#fff',
                    titleColor:isDark?'#f8f9fa':'#333',
                    bodyColor:isDark?'#adb5bd':'#666',
                    borderColor:isDark?'#444':'#ddd',
                    borderWidth:1,
                    padding:10,
                    callbacks:{
                        label:function(context){
                            const val=context.raw;
                            const total=context.dataset.data.reduce((a,b)=>a+b,0);
                            const pct=total>0?Math.round((val/total)*100):0;
                            return ` ${context.label}: ${val} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
