<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issue = fake()->dateTimeBetween('-60 days', 'now');

        return [
            'client_id' => Client::factory(),
            'company_id' => fn (array $attributes) => Client::withoutGlobalScopes()->find($attributes['client_id'])->company_id,
            'number' => 'INV-'.fake()->unique()->numerify('#####'),
            'issue_date' => $issue,
            'due_date' => (clone $issue)->modify('+30 days'),
            'amount' => fake()->randomFloat(2, 500, 25000),
            'paid_amount' => 0,
            'currency' => 'AED',
            'status' => 'unpaid',
        ];
    }

    /**
     * An open invoice whose due date passed the given number of days ago.
     */
    public function overdueBy(int $days): static
    {
        return $this->state(fn () => [
            'issue_date' => today()->subDays($days + 30),
            'due_date' => today()->subDays($days),
        ]);
    }

    /**
     * An open invoice due in the given number of days.
     */
    public function dueIn(int $days): static
    {
        return $this->state(fn () => [
            'issue_date' => today()->subDays(30 - $days),
            'due_date' => today()->addDays($days),
        ]);
    }
}
