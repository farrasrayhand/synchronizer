<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ptk extends Model
{
    public $incrementing = false;
	public $keyType = 'string';
    protected $connection = 'dapodik';
	protected $table = 'ptk';
	protected $primaryKey = 'ptk_id';
    protected $guarded = [];
    public $timestamps = false;
    public function ptk_terdaftar()
    {
        return $this->HasOne(PtkTerdaftar::class, 'ptk_id', 'ptk_id');
    }
    public function tugas_tambahan()
    {
        return $this->hasMany(TugasTambahan::class, 'ptk_id', 'ptk_id');
    }
    public function rwy_pend_formal()
    {
        return $this->hasMany(RwyPendFormal::class, 'ptk_id', 'ptk_id');
    }
    public function rwy_kerja()
    {
        return $this->hasMany(RwyKerja::class, 'ptk_id', 'ptk_id');
    }
}
