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
        // Jika foto_referensi tidak kosong dan BUKAN default.jpg
        if (!empty($this->foto_referensi) && $this->foto_referensi !== 'default.jpg') {
            return str_starts_with($this->foto_referensi, 'http') 
                ? $this->foto_referensi 
                : asset('storage/santri_fotos/' . $this->foto_referensi);
        }

        // Jika kosong atau default.jpg, coba ambil foto riwayat presensi terbaru dari mesin
        $latest = $this->presensis()->whereNotNull('photo_url')->orderBy('waktu_scan', 'desc')->first();
        return $latest ? $latest->photo_url : null;
    }
}
