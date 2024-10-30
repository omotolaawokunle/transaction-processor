<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GetBalance extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $balance = $request->user()->balance->balance ?? 0;

        return response()->json([
            'status' => 1,
            'data' => [
                'balance' => $balance
            ]
        ], 200);
    }
}
