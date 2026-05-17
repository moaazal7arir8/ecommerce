<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = ['name','image','offer_id','gift_id'];
    public function getImageAttribute($value)
{
    return $value
        ? url(Storage::url($value))
        : null;
}
}
