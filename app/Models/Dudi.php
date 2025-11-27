<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dudi extends Model
{
    public $keyType = 'string';
    protected $table = 'dudi';
	protected $primaryKey = 'dudi_id';
    protected $connection = 'dapodik';
    public function mou()
    {
        return $this->hasMany(Mou::class, 'dudi_id', 'dudi_id');
    }
}
