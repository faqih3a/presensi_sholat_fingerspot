<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    use MassPrunable;
    protected $fillable = [
        'santri_id', 'waktu_sholat',
        'tanggal',
        'waktu_hadir',
        'status',
        'photo_url',
    ];

    /**
     * Jumlah hari data presensi disimpan sebelum dihapus otomatis.
     */
    const RETENTION_DAYS = 90;

    /**
     * Tentukan query untuk menentukan data mana yang boleh di-prune.
     * Menghapus data presensi yang tanggalnya sudah lebih dari 90 hari.
     */
    public function prunable()
    {
        return static::where('tanggal', '<=', now()->subDays(self::RETENTION_DAYS));
    }

    public function santri()
    {
        return $this->belongsTo(Santri::class);
    }
}
