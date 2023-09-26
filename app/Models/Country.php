<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    protected $primary = 'country_id';
    protected $guarded = [];
}
