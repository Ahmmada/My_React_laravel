<?php

namespace Tests\Feature;

use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_receipt_with_counterparties()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a receipt with initial counterparties
        $receipt = Receipt::factory()->create();
        $counterparties = [
            [
                'ledger_id' => 1,
                'amount' => 100,
                'description' => 'Description 1',
                'related_id' => 1,
                'related_type' => 'App\Models\CashBox',
            ],
            [
                'ledger_id' => 2,
                'amount' => 200,
                'description' => 'Description 2',
                'related_id' => 2,
                'related_type' => 'App\Models\Supplier',
            ],
        ];

        $receipt->transactions()->createMany($counterparties);

        // Update receipt data with new counterparties
        $newCounterparties = [
            [
                'ledger_id' => 1,
                'amount' => 150,
                'description' => 'Updated Description 1',
                'related_id' => 1,
                'related_type' => 'App\Models\CashBox',
            ],
            [
                'ledger_id' => 3,
                'amount' => 250,
                'description' => 'Updated Description 3',
                'related_id' => 3,
                'related_type' => 'App\Models\Customer',
            ],
        ];

        $response = $this->actingAs($user)->put(route('receipts.update', $receipt), [
            'receipt_date' => now()->format('Y-m-d'),
            'cash_box_id' => 1,
            'description' => 'Updated description',
            'counterparties' => $newCounterparties,
        ]);

        $response->assertRedirect(route('receipts.index'));
        $response->assertSessionHas('success');

        // Verify the receipt has been updated
        $this->assertDatabaseHas('receipts', [
            'id' => $receipt->id,
            'description' => 'Updated description',
        ]);

        // Verify the transactions have been updated
        foreach ($newCounterparties as $counterparty) {
            $this->assertDatabaseHas('transactions', [
                'receipt_id' => $receipt->id,
                'ledger_id' => $counterparty['ledger_id'],
                'amount' => $counterparty['amount'],
                'description' => $counterparty['description'],
            ]);
        }
    }
}