<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $primaryKey = 'userID';
    protected $table = 'employees'; // will automatically assume the table name is 'employees' based on the model name, but it can be stated explicitly here
    protected $fillable = ['userID', 'fullName', 'dockID']; // If you're inserting/updating specific fields add them here
    public $timestamps = false;
}
