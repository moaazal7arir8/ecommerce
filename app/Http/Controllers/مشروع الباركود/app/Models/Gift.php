<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    protected $fillable = ['name','points'];
    
    public function products()
{
    return $this->hasMany(Product::class);
}
}
