<?php

namespace App\Services;

use App\Models\User;
use App\Models\Balance;
use Illuminate\Support\Str;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use Illuminate\Support\Facades\DB;
use App\Exceptions\InsufficientFundsException;

class TransactionService
{
    public function processTransaction(User $user, $amount, TransactionType $type)
    {
        return DB::transaction(function () use ($user, $amount, $type) {
            $balance = Balance::lockForUpdate()
                ->where('user_id', $user->id)
                ->first();
            if (!$balance) {
                $balance = new Balance(['user_id' => $user->id, 'balance' => 0]);
            }

            if ($type->value === TransactionType::DEBIT->value && $balance->balance < $amount) {
                throw new InsufficientFundsException('Insufficient funds!');
            }
            $signedAmount = $amount * ($type->value === TransactionType::CREDIT->value ? 1 : -1);
            $balance->balance += $signedAmount;
            $balance->save();

            return $user->transactions()->create([
                'amount' => $signedAmount,
                'type' => $type->value,
                'status' => TransactionStatus::COMPLETED->value,
                'user_id' => $user->id,
                'reference' => Str::uuid(),
            ]);
        });
    }
}
