<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dock_Type extends Model
{
    protected $primaryKey = 'type';
    protected $table = 'dock_types';
    protected $fillable = ['type', 'name', 'in_use'];
    protected $casts = ['type' => 'string',];
    public $timestamps = false;
}