<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ticker extends Model
{
    protected $table = 'ticker';
    public $timestamps = false;
    protected $fillable = [       
        'pair_id',
        'timeframe_id',
        'current_price',
    ];

    public function pair()
    {
        return $this->belongsTo('App\Pair','pair_id');
    }


}
