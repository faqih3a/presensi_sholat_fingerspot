<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    protected $fillable = [
        'santri_id',
        'waktu_sholat',
        'tanggal',
        'waktu_hadir',
        'status',
        'photo_url',
        'verify',
        'status_scan',
    ];

    public function santri()
    {
        return $this->belongsTo(Santri::class);
    }

    public function getVerifyMethodLabelAttribute()
    {
        if ($this->verify === null) return '-';
        switch ((int)$this->verify) {
            case 1: return 'Fingerprint';
            case 2: return 'Password';
            case 3: return 'Card';
            case 4: return 'Face';
            case 6: return 'Vein';
            case 7: return 'QR Code';
            default: return 'Unknown (' . $this->verify . ')';
        }
    }
    
    public function getVerifyIconAttribute()
    {
        if ($this->verify === null) return 'bi-question-circle';
        switch ((int)$this->verify) {
            case 1: return 'bi-fingerprint';
            case 2: return 'bi-key-fill';
            case 3: return 'bi-card-list';
            case 4: return 'bi-person-bounding-box';
            case 6: return 'bi-hand-index-thumb';
            case 7: return 'bi-qr-code-scan';
            default: return 'bi-question-circle';
        }
    }

    public function getStatusScanLabelAttribute()
    {
        if ($this->status_scan === null) return '-';
        switch ((int)$this->status_scan) {
            case 0: return 'Scan Masuk';
            case 1: return 'Scan Keluar';
            case 2: return 'Break In';
            case 3: return 'Break Out';
            case 4: return 'Overtime In';
            case 5: return 'Overtime Out';
            case 6: return 'Rapat In';
            case 7: return 'Rapat Out';
            case 8: return 'Custom 1';
            case 9: return 'Custom 2';
            default: return 'Scan (' . $this->status_scan . ')';
        }
    }
}
