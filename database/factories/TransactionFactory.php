<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Models\User;
use Illuminate\Support\Str;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'type' => $this->faker->randomElement(array_column(TransactionType::cases(), 'value')),
            'status' => $this->faker->randomElement(array_column(TransactionStatus::cases(), 'value')),
            'reference' => Str::uuid()
        ];
    }

    public function credit()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => TransactionType::CREDIT,
            ];
        });
    }

    public function debit()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => TransactionType::DEBIT
            ];
        });
    }
}
