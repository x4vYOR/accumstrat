<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Accumulation extends Model
{
    protected $table = 'accumulation';
    public $timestamps = false;
    protected $fillable = [       
        'pair_id',
        'status',
    ];

    public function pair()
    {
        return $this->belongsTo('App\Pair','pair_id');
    }

    public function trades()
    {
        return $this->hasMany('App\Trade','accumulation_id');
    }
}
