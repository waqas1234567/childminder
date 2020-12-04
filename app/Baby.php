<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Baby extends Model
{
    protected $fillable=['name','age','macAddress','device','userId','image','identifier'];
}
