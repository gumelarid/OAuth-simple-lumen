<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    use HasFactory;

    protected $table = 'refresh_tokens';

    protected $keyType = 'string';

    protected $guarded = [];

    public $timestamps = false;
}
