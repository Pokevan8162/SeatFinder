<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $primaryKey = 'userID';
    protected $table = 'users';
    protected $fillable = ['email', 'password'];
    public $timestamps = false;
    protected $hidden = ['password'];
}
