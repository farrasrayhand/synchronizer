<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ruang extends Model
{
    public $keyType = 'string';
    protected $table = 'ruang';
	protected $primaryKey = 'id_ruang';
    protected $connection = 'dapodik';
}
