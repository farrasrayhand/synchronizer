<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelasEkskul extends Model
{
    public $keyType = 'string';
    protected $connection = 'dapodik';
    protected $table = 'kelas_ekskul';
	protected $primaryKey = 'id_kelas_ekskul';
    public function rombongan_belajar()
    {
        return $this->hasOne(RombonganBelajar::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
    }
}
