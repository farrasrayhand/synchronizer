<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AktPd extends Model
{
    protected $keyType = 'string';
    protected $table = 'akt_pd';
	protected $primaryKey = 'id_akt_pd';
    protected $connection = 'dapodik';

    public function anggota_akt_pd()
    {
        return $this->hasMany(AnggotaAktPd::class, 'id_akt_pd', 'id_akt_pd');
    }
    public function bimbing_pd()
    {
        return $this->hasMany(BimbingPd::class, 'id_akt_pd', 'id_akt_pd');
    }
}
