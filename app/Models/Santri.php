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
        'face_descriptor',
        'fingerspot_pin',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function presensis()
    {
        return $this->hasMany(Presensi::class);
    }
}
