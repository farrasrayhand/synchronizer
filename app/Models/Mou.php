<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mou extends Model
{
    public $keyType = 'string';
    protected $table = 'mou';
	protected $primaryKey = 'mou_id';
    protected $connection = 'dapodik';
    public function akt_pd()
    {
        return $this->hasMany(AktPd::class, 'mou_id', 'mou_id');
    }
}
