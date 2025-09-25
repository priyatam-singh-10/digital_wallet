<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;


class WalletController extends Controller
{

public function balance()
{
    // die(' kkkkk');
    $user = auth('api')->user();
    // echo $user;die(' kk');
    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $wallet = $user->wallet;

    if (!$wallet) {
        return response()->json(['error' => 'Wallet not found'], 404);
    }

    return response()->json([
        'balance' => $wallet->balance ?? 0
    ]);
}

public function addFunds(Request $request) 
{
    $request->validate([
        'amount' => 'required|numeric|min:1'
    ]);

    $wallet = auth()->user()->wallet;

    if (!$wallet) {
        return response()->json(['error' => 'Wallet not found'], 404);
    }

    $wallet->balance += $request->amount;
    $wallet->save();

    Transaction::create([
        'wallet_id'     => $wallet->id,
        'type'          => 'credit',
        'amount'        => $request->amount,
        'balance_after' => $wallet->balance,
        'currency'      => $wallet->currency ?? 'INR',
        'remark'        => 'Funds added'
    ]);

    return response()->json([
        'message' => 'Funds added successfully',
        'balance' => $wallet->balance
    ]);
}

// public function transfer(Request $request) 
// {
//     $request->validate([
//         'email'=>'required|email|exists:users,email',
//         'amount'=>'required|numeric|min:1',
//     ]);

//     $fromWallet = auth()->user()->wallet;
//     $toUser = User::where('email',$request->email)->first();
//     $toWallet = $toUser->wallet;

//     if($fromWallet->balance < $request->amount){
//         return response()->json(['error'=>'Insufficient balance'],400);
//     }

//     $fromWallet->balance -= $request->amount;
//     $fromWallet->save();
//     Transaction::create([
//         'wallet_id'=>$fromWallet->id,
//         'type'=>'debit',
//         'amount'=>$request->amount,
//         'balance_after'=>$fromWallet->balance,
//         'currency'=>$fromWallet->currency,
//         'remark'=>"Transfer to {$toUser->email}"
//     ]);

//     $toWallet->balance += $request->amount;
//     $toWallet->save();
//     Transaction::create([
//         'wallet_id'=>$toWallet->id,
//         'type'=>'credit',
//         'amount'=>$request->amount,
//         'balance_after'=>$toWallet->balance,
//         'currency'=>$toWallet->currency,
//         'remark'=>"Received from ".auth()->user()->email
//     ]);

//     return response()->json(['message'=>'Transfer successful','balance'=>$fromWallet->balance]);
// }

public function transfer(Request $request) 
{
    $request->validate([
        'email'=>'required|email|exists:users,email',
        'amount'=>'required|numeric|min:1',
    ]);

    $fromUser = auth()->user();
    $toUser = User::where('email', $request->email)->first();

    $fromWallet = $fromUser->wallet;
    $toWallet = $toUser->wallet;

    $dailyLimit = 10000;
    $todayTotal = Transaction::where('wallet_id', $fromWallet->id)
        ->whereDate('created_at', now()->toDateString())
        ->sum('amount');

    if (($todayTotal + $request->amount) > $dailyLimit) {
        return response()->json(['error'=>'Daily transaction limit exceeded'], 403);
    }

    $recentTransactions = Transaction::where('wallet_id', $fromWallet->id)
        ->where('created_at', '>=', now()->subMinutes(5))
        ->count();

    if ($recentTransactions >= 5 && $request->amount > 2000) {
        return response()->json(['error'=>'Suspicious activity detected'], 403);
    }

    $finalAmount = $request->amount;
    if ($fromWallet->currency != $toWallet->currency) {
        $rate = ExchangeRate::where('from_currency', $fromWallet->currency)
            ->where('to_currency', $toWallet->currency)
            ->value('rate');

        if (!$rate) {
            return response()->json(['error'=>'Exchange rate not found'], 400);
        }

        $finalAmount = $request->amount * $rate;
    }

    if ($fromWallet->balance < $request->amount) {
        return response()->json(['error'=>'Insufficient balance'], 400);
    }

    $fromWallet->balance -= $request->amount;
    $fromWallet->save();
    Transaction::create([
        'wallet_id'=>$fromWallet->id,
        'type'=>'debit',
        'amount'=>$request->amount,
        'balance_after'=>$fromWallet->balance,
        'currency'=>$fromWallet->currency,
        'remark'=>"Transfer to {$toUser->email}"
    ]);

    $toWallet->balance += $finalAmount;
    $toWallet->save();
    Transaction::create([
        'wallet_id'=>$toWallet->id,
        'type'=>'credit',
        'amount'=>$finalAmount,
        'balance_after'=>$toWallet->balance,
        'currency'=>$toWallet->currency,
        'remark'=>"Received from {$fromUser->email}"
    ]);

    return response()->json([
        'message'=>'Transfer successful',
        'balance'=>$fromWallet->balance,
        'currency'=>$fromWallet->currency
    ]);
}

public function withdraw(Request $request) 
{
    $request->validate(['amount'=>'required|numeric|min:1']);
    $wallet = auth()->user()->wallet;

    if($wallet->balance < $request->amount){
        return response()->json(['error'=>'Insufficient balance'],400);
    }

    $wallet->balance -= $request->amount;
    $wallet->save();

    Transaction::create([
        'wallet_id'=>$wallet->id,
        'type'=>'debit',
        'amount'=>$request->amount,
        'balance_after'=>$wallet->balance,
        'currency'=>$wallet->currency,
        'remark'=>'Withdrawal'
    ]);

    return response()->json(['message'=>'Withdrawal successful','balance'=>$wallet->balance]);
}


public function transactions() 
{
    $wallet = auth()->user()->wallet;
    $history = $wallet->transactions()->orderBy('created_at','desc')->get();
    return response()->json($history);
}

public function convertCurrency($amount, $fromCurrency, $toCurrency) {
    if ($fromCurrency === $toCurrency) return $amount;

    $rate = \App\Models\ExchangeRate::where('from_currency', $fromCurrency)
        ->where('to_currency', $toCurrency)
        ->value('rate');

    if (!$rate) throw new \Exception("Exchange rate not available for $fromCurrency to $toCurrency");

    return $amount * $rate;
}

}
