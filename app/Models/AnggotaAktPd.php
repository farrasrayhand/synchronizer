<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnggotaAktPd extends Model
{
    protected $keyType = 'string';
    protected $table = 'anggota_akt_pd';
	protected $primaryKey = 'id_ang_akt_pd';
    protected $connection = 'dapodik';

    public function registrasi_peserta_didik()
    {
        return $this->hasOne(RegistrasiPesertaDidik::class, 'registrasi_id', 'registrasi_id');
    }
    public function peserta_didik()
    {
        return $this->hasOneThrough(
            PesertaDidik::class,
            RegistrasiPesertaDidik::class,
            'registrasi_id', // Foreign key on the cars table...
            'peserta_didik_id', // Foreign key on the owners table...
            'registrasi_id', // Local key on the mechanics table...
            'peserta_didik_id' // Local key on the cars table...
        );
    }
}
