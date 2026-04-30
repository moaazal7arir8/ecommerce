<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['price', 'item_quantity', 'order_id', 'product_id'];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
{
    return $this->belongsTo(Product::class)->withTrashed();
}
}
