<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Wallet;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = ['wallet_id','type','amount','balance_after','currency','remark'];

    public function wallet() {
        return $this->belongsTo(Wallet::class);
    }
}
