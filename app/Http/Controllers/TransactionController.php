<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enums\TransactionType;
use App\Services\TransactionService;
use App\Http\Requests\TransactionRequest;
use App\Exceptions\InsufficientFundsException;

class TransactionController extends Controller
{
    public function __construct(protected TransactionService $transactionService) {}

    public function store(TransactionRequest $request)
    {
        try {
            $type = TransactionType::from($request->type);
            $transaction = $this->transactionService->processTransaction($request->user, $request->amount, $type);

            return response()->json([
                'status' => 1,
                'transaction' => $transaction
            ], 201);
        } catch (InsufficientFundsException $e) {
            return response()->json([
                'status' => 0,
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'status' => 0,
                'error' => 'An error occurred'
            ], 500);
        }
    }
}
