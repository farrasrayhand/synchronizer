<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AnggotaRombel extends Model
{
    use HasUuids;
    protected $connection = 'dapodik';
	protected $table = 'anggota_rombel';
	protected $primaryKey = 'anggota_rombel_id';
    protected $guarded = [];
    public $timestamps = false;
    public function rombongan_belajar()
    {
        return $this->hasOne(RombonganBelajar::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
    }
    public function peserta_didik()
    {
        return $this->hasOneThrough(
            RegistrasiPesertaDidik::class,
            PesertaDidik::class,
            'peserta_didik_id', // Foreign key on the cars table...
            'peserta_didik_id', // Foreign key on the owners table...
            'peserta_didik_id', // Local key on the mechanics table...
            'peserta_didik_id' // Local key on the cars table...
        );
    }
    public function pd()
    {
        return $this->hasOne(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
