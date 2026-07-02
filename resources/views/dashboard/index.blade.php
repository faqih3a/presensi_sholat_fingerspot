@extends('layouts.app')
@section('title', 'Dashboard')
@push('styles')
<style>
.stat-card{border-radius:8px;padding:1.25rem 1.5rem;display:flex;justify-content:space-between;align-items:center;border:1px solid var(--color-border);transition:box-shadow .2s ease,transform .2s ease;background:var(--color-surface)}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(30,29,27,.06)}
.stat-card .icon-box{width:44px;height:44px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.stat-card .stat-value{font-size:1.8rem;font-weight:700;line-height:1.1;font-family:var(--font-display);letter-spacing:-0.03em;color:var(--color-text)}
.stat-card .stat-label{font-size:.75rem;font-weight:500;color:var(--color-muted);margin-bottom:.3rem;text-transform:uppercase;letter-spacing:.05em}
.stat-card .stat-sub{font-size:.72rem;color:var(--color-muted);margin-top:.25rem}
.prayer-section{border-radius:8px;padding:1.5rem;border:1px solid var(--color-border);background:var(--color-surface)}
.prayer-card{border:1px solid var(--color-border);border-radius:6px;padding:.75rem 1rem;text-align:left;min-width:110px;position:relative;transition:border-color .15s ease,background-color .15s ease}
.prayer-card.next-prayer{border-color:var(--color-accent);background:var(--color-accent-light)}
.prayer-card .prayer-name{font-size:.72rem;color:var(--color-muted);font-weight:600;text-transform:uppercase;letter-spacing:.06em;display:flex;align-items:center;gap:.3rem}
.prayer-card .prayer-time{font-size:1.3rem;font-weight:700;color:var(--color-text);font-family:var(--font-display);letter-spacing:-0.02em;margin-top:.2rem;font-variant-numeric:tabular-nums}
.prayer-card .next-badge{position:absolute;top:.35rem;right:.4rem;background:var(--color-accent);color:#fff;font-size:.55rem;font-weight:700;padding:.12rem .35rem;border-radius:3px;text-transform:uppercase;letter-spacing:.05em}
.chart-card{border-radius:8px;padding:1.5rem;border:1px solid var(--color-border);background:var(--color-surface)}
.chart-card .chart-title{font-size:.95rem;font-weight:600;color:var(--color-text);font-family:var(--font-display);letter-spacing:-0.01em}
.chart-card .chart-sub{font-size:.72rem;color:var(--color-muted);margin-top:.15rem}
.prayer-meta{display:flex;align-items:center;gap:.5rem;font-size:.72rem;color:var(--color-muted);margin-top:1rem;padding:.65rem 1rem;background:var(--color-bg);border-radius:6px;border:1px solid var(--color-border)}
.prayer-meta .live-dot{width:7px;height:7px;border-radius:50%;background:var(--color-accent);display:inline-block;animation:pulse-live 2.5s infinite}
@keyframes pulse-live{0%,100%{box-shadow:0 0 0 0 rgba(42,107,79,.4)}50%{box-shadow:0 0 0 5px rgba(42,107,79,0)}}
.refresh-btn{border:1px solid var(--color-border);border-radius:5px;background:var(--color-surface);color:var(--color-muted);font-size:.78rem;font-weight:500;padding:.35rem .7rem;cursor:pointer;transition:all .15s ease;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem}
.refresh-btn:hover{border-color:var(--color-accent);color:var(--color-accent)}
body.dark-mode .stat-card,body.dark-mode .prayer-section,body.dark-mode .chart-card{background:var(--color-surface);border-color:var(--color-border)}
body.dark-mode .prayer-card{border-color:var(--color-border)}
body.dark-mode .prayer-card.next-prayer{border-color:var(--color-accent);background:var(--color-accent-light)}
body.dark-mode .prayer-meta{background:var(--color-bg);border-color:var(--color-border)}
body.dark-mode .refresh-btn{background:var(--color-surface);border-color:var(--color-border);color:var(--color-muted)}
</style>
@endpush
@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h1 class="h3 mb-0 fw-bold" style="color:var(--color-text);letter-spacing:-.03em">Dashboard</h1>
        <p style="color:var(--color-muted);font-size:.85rem;margin-bottom:0;margin-top:.2rem">Rekap kehadiran sholat hari ini</p>
    </div>
    @include('partials.date-filter')
</div>

{{-- 4 Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card h-100">
            <div>
                <div class="stat-label">Total Jamaah</div>
                <div class="stat-value">{{ number_format($totalSantri) }}</div>
                <div class="stat-sub">Santri terdaftar aktif</div>
            </div>
            <div class="icon-box" style="background:rgba(42,107,79,.08);color:#2A6B4F"><i class="bi bi-people"></i></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card h-100">
            <div>
                <div class="stat-label">Hadir Hari Ini</div>
                <div class="stat-value">{{ number_format($jamaahHadirHariIni) }}</div>
                <div class="stat-sub">{{ $totalSantri > 0 ? round(($jamaahHadirHariIni/$totalSantri)*100) : 0 }}% dari total jamaah</div>
            </div>
            <div class="icon-box" style="background:rgba(42,107,79,.08);color:#2A6B4F"><i class="bi bi-person-check"></i></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card h-100">
            <div>
                <div class="stat-label">Ketepatan Waktu</div>
                <div class="stat-value">{{ $ketepatanWaktu }}<span style="font-size:1.1rem;font-weight:500">%</span></div>
                <div class="stat-sub">Terhadap 5 waktu sholat</div>
            </div>
            <div class="icon-box" style="background:rgba(100,87,64,.07);color:#6B5740"><i class="bi bi-graph-up"></i></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card h-100">
            <div>
                <div class="stat-label">Scan Hari Ini</div>
                <div class="stat-value">{{ number_format($totalScanHariIni) }}</div>
                <div class="stat-sub">Total entri fingerspot</div>
            </div>
            <div class="icon-box" style="background:rgba(100,87,64,.07);color:#6B5740"><i class="bi bi-fingerprint"></i></div>
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
            <div class="text-center py-4" style="background:var(--color-bg);border-radius:6px;border:1px solid var(--color-border)">
                <i class="bi bi-check-circle d-block mb-2" style="font-size:1.5rem;color:var(--color-muted)"></i>
                <p style="color:var(--color-muted);font-size:.82rem;margin:0">Tidak ada santri izin pada periode ini.</p>
            </div>
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
            <div class="text-center py-4" style="background:var(--color-bg);border-radius:6px;border:1px solid var(--color-border)">
                <i class="bi bi-check-circle d-block mb-2" style="font-size:1.5rem;color:var(--color-muted)"></i>
                <p style="color:var(--color-muted);font-size:.82rem;margin:0">Tidak ada santri alfa pada periode ini.</p>
            </div>
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
            <div class="text-center py-4" style="background:var(--color-bg);border-radius:6px;border:1px solid var(--color-border)">
                <i class="bi bi-clock-history d-block mb-2" style="font-size:1.5rem;color:var(--color-muted)"></i>
                <p style="color:var(--color-muted);font-size:.82rem;margin:0">Belum ada aktivitas tercatat.</p>
            </div>
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
