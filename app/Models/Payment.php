<?php

namespace App\Models;

use App\Models\Country;
use App\Models\CodePayment;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;
    protected $primary = 'payment_id';
    protected $keyType = 'string';
    protected $guarded = [];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'country_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function codePayment()
    {
        return $this->belongsTo(CodePayment::class, 'code_payment', 'id');
    }
}
