<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BimbingPd extends Model
{
    public $keyType = 'string';
    protected $table = 'bimbing_pd';
	protected $primaryKey = 'id_bimb_pd';
    protected $connection = 'dapodik';
}
