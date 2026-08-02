<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class Izin extends Model
{
    use MassPrunable;
    protected $fillable = [
        'user_id',
        'jenis_izin',
        'waktu_sholat',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
        'lampiran',
        'status',
        'keterangan_admin',
    ];

    /**
     * Jumlah hari data izin disimpan sebelum dihapus otomatis.
     */
    const RETENTION_DAYS = 90;

    /**
     * Tentukan query untuk menentukan data mana yang boleh di-prune.
     * Menghapus data izin yang sudah lebih dari 90 hari sejak dibuat.
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(self::RETENTION_DAYS));
    }

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
