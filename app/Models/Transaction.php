<?php

namespace App\Models;

use App\Models\Payment;
use App\Models\PricePoint;
use App\Models\TransactionDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $keyType = "string";

    protected $primaryKey = "transaction_id";
    protected $guarded = [];


    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    public function pricepoints()
    {
        return $this->belongsTo(PricePoint::class, 'pricepoint_id', 'pricepoint_id');
    }

    public function gamelist()
    {
        return $this->belongsTo(GameList::class, 'game_id', 'game_id');
    }

    public function transactionDetail()
    {
        return $this->hasOne(TransactionDetail::class, 'transaction_id', 'transaction_id');
    }
}
