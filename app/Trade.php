<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    protected $table = 'trade';
    public $timestamps = false;
    protected $fillable = [       
        'accumulation_id',
        'active',
    ];

    public function accumulation()
    {
        return $this->belongsTo('App\Accumulation','accumulation_id');
    }
}
