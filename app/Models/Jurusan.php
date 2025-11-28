<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jurusan extends Model
{
    protected $connection = 'dapodik';
    protected $table = 'ref.jurusan';
	protected $primaryKey = 'jurusan_id';
}
