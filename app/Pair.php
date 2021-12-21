<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Pair extends Model
{
    protected $table = 'pair';
    public $timestamps = false;
    protected $fillable = [       
        'initial_capital',
    ];

    public function accumulations()
    {
        return $this->hasMany('App\Accumulation','pair_id');
    }

    public function tickers()
    {
        return $this->hasMany('App\Ticker','pair_id');
    }
    public function timeframe()
    {
        return $this->belongsTo('App\Timeframe','timeframe_id');
    }
}
