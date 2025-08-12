<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dock extends Model
{
    protected $casts = [
        'serial_num' => 'string',
    ];
    protected $table = 'docks';
    protected $fillable = ['type', 'serial_num', 'desk', 'x', 'y'];
    public $timestamps = false;
}
