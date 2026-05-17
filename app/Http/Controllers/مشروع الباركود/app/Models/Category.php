<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name'];

    public function barcodes()
{
    return $this->hasMany(Barcode::class);
}

public function users()
{
    return $this->belongsToMany(User::class);
}
public function offers()
{
    return $this->hasMany(Offer::class);
}
}