<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map_Image extends Model
{
    protected $table = 'map_image';
    protected $fillable = ['image_path', 'image_type'];
    public $timestamps = false;
}