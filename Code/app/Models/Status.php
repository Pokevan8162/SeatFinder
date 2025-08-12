<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $primaryKey = 'userID';
    public $incrementing = false;  // since it's a string, not an auto-increment int
    protected $keyType = 'string';  // tells Laravel primary key is a string
    protected $table = 'statuses';
    protected $fillable = ['status', 'userID'];
    public $timestamps = true;
}
