<?php

namespace App\Models;

use App\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PricePoint extends Model
{
    use HasFactory;

    protected $primary = 'pricepoint_id';
    protected $keyType = 'string';

    protected $guarded = [];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'country_id');
    }
}
