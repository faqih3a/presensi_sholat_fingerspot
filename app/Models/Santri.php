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

    public function getDisplayPhotoAttribute()
    {
        if (!empty($this->foto_referensi)) {
            return str_starts_with($this->foto_referensi, 'http') 
                ? $this->foto_referensi 
                : asset('storage/santri_fotos/' . $this->foto_referensi);
        }

        // Jika kosong, coba ambil foto riwayat presensi terbaru
        $latest = $this->presensis()->whereNotNull('photo_url')->orderBy('waktu_scan', 'desc')->first();
        return $latest ? $latest->photo_url : null;
    }
}
