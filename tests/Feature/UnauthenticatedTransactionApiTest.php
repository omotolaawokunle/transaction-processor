<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Enums\TransactionType;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnauthenticatedTransactionApiTest extends TestCase
{
    public function test_unauthenticated_access_denied_for_transaction()
    {
        $response = $this->postJson('/api/transactions', [
            'amount' => 100,
            'type' => TransactionType::CREDIT->value
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_access_denied_for_balance()
    {
        $response = $this->getJson('/api/balance');

        $response->assertStatus(401);
    }
}
