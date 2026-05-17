<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = ['name','points','syrian_price','american_price','category_id'];
 
    public function products()
{
    return $this->hasMany(Product::class);
}
}
 