<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kurikulum extends Model
{
    protected $connection = 'dapodik';
    protected $table = 'ref.kurikulum';
	protected $primaryKey = 'kurikulum_id';
}
