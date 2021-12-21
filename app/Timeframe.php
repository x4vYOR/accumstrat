<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Timeframe extends Model
{
    protected $table = 'timeframe';
    public $timestamps = false;
    protected $fillable = [    
        'name',
    ];
    
    public function pairs()
    {
        return $this->hasMany('App\Pair','timeframe_id');
    }
}
