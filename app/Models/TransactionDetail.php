<?php

namespace App\Models;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionDetail extends Model
{
    use HasFactory;
    protected $guarded = [];



    public function Transaction()
    {
        return $this->hasOne(Transaction::class, 'transaction_id', 'transaction_id');
    }
}
