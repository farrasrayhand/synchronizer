<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembelajaran extends Model
{
    public $incrementing = false;
	public $keyType = 'string';
    protected $connection = 'dapodik';
	protected $table = 'pembelajaran';
	protected $primaryKey = 'pembelajaran_id';
    protected $guarded = [];
    public $timestamps = false;
    public function rombongan_belajar()
    {
        return $this->hasOne(RombonganBelajar::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
    }
    public function mata_pelajaran(){
		return $this->hasOne(MataPelajaran::class, 'mata_pelajaran_id', 'mata_pelajaran_id');
	}
    public function ptk_terdaftar()
	{
		return $this->hasOneThrough(
            Ptk::class,
            PtkTerdaftar::class,
            'ptk_terdaftar_id', // Foreign key on the cars table...
            'ptk_id', // Foreign key on the owners table...
            'ptk_terdaftar_id', // Local key on the mechanics table...
            'ptk_id' // Local key on the cars table...
        )->where('ptk_terdaftar.soft_delete', 0)->whereNull('jenis_keluar_id');
	}
    public function guru()
    {
        return $this->hasOne(PtkTerdaftar::class, 'ptk_terdaftar_id', 'ptk_terdaftar_id');
    }
    public function sub_mapel()
    {
        return $this->hasMany(Pembelajaran::class, 'induk_pembelajaran_id', 'pembelajaran_id');
    }
}
