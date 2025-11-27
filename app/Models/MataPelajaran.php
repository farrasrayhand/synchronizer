<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MataPelajaran extends Model
{
    public $incrementing = false;
	public $keyType = 'string';
    protected $connection = 'dapodik';
	protected $table = 'ref.mata_pelajaran';
	protected $primaryKey = 'mata_pelajaran_id';
    protected $guarded = [];
    public $timestamps = false;
}
