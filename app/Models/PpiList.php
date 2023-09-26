<?php

namespace App\Models;

use App\Models\Country;
use App\Models\Payment;
use App\Models\PricePoint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PpiList extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'country_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    public function pricepoint()
    {
        return $this->belongsTo(PricePoint::class, 'pricepoint_id', 'pricepoint_id');
    }
}
