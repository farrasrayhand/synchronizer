<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurusanSp extends Model
{
    public $keyType = 'string';
    protected $connection = 'dapodik';
    protected $table = 'jurusan_sp';
	protected $primaryKey = 'jurusan_sp_id';
    
    public function rombongan_belajar()
    {
        return $this->hasMany(RombonganBelajar::class, 'jurusan_sp_id', 'jurusan_sp_id');
    }
    public function jurusan()
    {
        return $this->hasOne(Jurusan::class, 'jurusan_id', 'jurusan_id');
    }
    public function siswa(){
        return $this->hasManyThrough(
            AnggotaRombel::class,
            RombonganBelajar::class,
            'jurusan_sp_id', // Foreign key on the cars table...
            'rombongan_belajar_id', // Foreign key on the owners table...
            'jurusan_sp_id', // Local key on the mechanics table...
            'rombongan_belajar_id' // Local key on the cars table...
        );
    }
}
