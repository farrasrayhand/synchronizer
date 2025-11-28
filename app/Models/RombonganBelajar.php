<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RombonganBelajar extends Model
{
    public $incrementing = false;
	public $keyType = 'string';
    protected $connection = 'dapodik';
	protected $table = 'rombongan_belajar';
	protected $primaryKey = 'rombongan_belajar_id';
    public function jurusan_sp()
    {
        return $this->hasOne(JurusanSp::class, 'jurusan_sp_id', 'jurusan_sp_id');
    }
    public function kurikulum()
	{
		return $this->hasOne(Kurikulum::class, 'kurikulum_id', 'kurikulum_id');
	}
    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'semester_id');
    }
    public function wali_kelas()
    {
        return $this->hasOne(Ptk::class, 'ptk_id', 'ptk_id');
    }
    public function kelas_ekskul()
    {
        return $this->hasOne(KelasEkskul::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
    }
    public function ruang()
    {
        return $this->hasOne(Ruang::class, 'id_ruang', 'id_ruang');
    }
}
