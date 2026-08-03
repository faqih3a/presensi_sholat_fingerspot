<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Santri extends Model
{
    protected $fillable = [
        'user_id',
        'nama',
        'kelas',
        'foto_referensi',
        'finger_count',
        'face_count',
        'template',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function presensis()
    {
        return $this->hasMany(Presensi::class);
    }

    /**
     * Eager-loadable relationship: foto presensi terbaru dari mesin.
     *
     * Gunakan Santri::with('latestPresensiPhoto') untuk menghindari
     * N+1 query saat menampilkan daftar santri.
     */
    public function latestPresensiPhoto()
    {
        return $this->hasOne(Presensi::class)
            ->whereNotNull('photo_url')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Accessor: URL foto santri yang paling sesuai.
     *
     * Prioritas:
     * 1. foto_referensi (upload manual, bukan default.jpg)
     * 2. Foto dari presensi terbaru via mesin (eager-loaded jika tersedia)
     * 3. null (fallback ke avatar placeholder di view)
     */
    public function getDisplayPhotoAttribute()
    {
        // Prioritas 1: foto referensi yang valid
        if (!empty($this->foto_referensi) && $this->foto_referensi !== 'default.jpg') {
            return str_starts_with($this->foto_referensi, 'http') 
                ? $this->foto_referensi 
                : asset('storage/santri_fotos/' . $this->foto_referensi);
        }

        // Prioritas 2: foto mesin — gunakan relasi yang sudah di-eager-load
        // Jika belum di-eager-load, fallback ke lazy query (backward compatible)
        $latest = $this->relationLoaded('latestPresensiPhoto')
            ? $this->latestPresensiPhoto
            : $this->presensis()->whereNotNull('photo_url')->orderBy('created_at', 'desc')->first();

        return $latest ? $latest->photo_url : null;
    }
}

