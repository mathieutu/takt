<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;

function invoicePayload(array $overrides = []): array
{
    return array_merge([
        'amount' => 100000,
        'discount_amount' => 0,
        'paid_at' => null,
        'notes' => null,
        'created_at' => today()->toDateString(),
    ], $overrides);
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = Client::factory()->for($this->user)->create();
    $this->project = Project::factory()->for($this->client)->create();
});

describe('store', function () {
    it('creates an invoice with a valid discount_amount', function () {
        $this->actingAs($this->user)
            ->post(route('invoices.store', $this->project), invoicePayload(['discount_amount' => 20000]))
            ->assertRedirect();

        expect(Invoice::where('project_id', $this->project->id)->firstOrFail()->discount_amount)->toBe(20000);
    });

    it('accepts an explicit zero discount_amount', function () {
        $this->actingAs($this->user)
            ->post(route('invoices.store', $this->project), invoicePayload(['discount_amount' => 0]))
            ->assertRedirect();

        expect(Invoice::where('project_id', $this->project->id)->firstOrFail()->discount_amount)->toBe(0);
    });

    it('requires discount_amount', function () {
        $payload = invoicePayload();
        unset($payload['discount_amount']);

        $this->actingAs($this->user)
            ->post(route('invoices.store', $this->project), $payload)
            ->assertInvalid(['discount_amount']);
    });

    it('rejects a negative discount_amount', function () {
        $this->actingAs($this->user)
            ->post(route('invoices.store', $this->project), invoicePayload(['discount_amount' => -1]))
            ->assertInvalid(['discount_amount']);
    });

    it('rejects a non-integer discount_amount', function () {
        $this->actingAs($this->user)
            ->post(route('invoices.store', $this->project), invoicePayload(['discount_amount' => 'not-a-number']))
            ->assertInvalid(['discount_amount']);
    });

    it('rejects a discount_amount greater than amount', function () {
        $this->actingAs($this->user)
            ->post(route('invoices.store', $this->project), invoicePayload(['amount' => 1000, 'discount_amount' => 1001]))
            ->assertInvalid(['discount_amount']);
    });

    it('accepts a discount_amount equal to amount (100% discount)', function () {
        $this->actingAs($this->user)
            ->post(route('invoices.store', $this->project), invoicePayload(['amount' => 1000, 'discount_amount' => 1000]))
            ->assertRedirect();

        $invoice = Invoice::where('project_id', $this->project->id)->firstOrFail();
        expect($invoice->discount_amount)->toBe(1000)
            ->and($invoice->netAmount())->toBe(0);
    });
});

describe('update', function () {
    it('updates the discount_amount', function () {
        $invoice = Invoice::factory()->for($this->project)->create(['amount' => 100000, 'discount_amount' => 0]);

        $this->actingAs($this->user)
            ->put(route('invoices.update', $invoice), invoicePayload(['amount' => 100000, 'discount_amount' => 30000]))
            ->assertRedirect();

        expect($invoice->refresh()->discount_amount)->toBe(30000);
    });

    it('requires discount_amount', function () {
        $invoice = Invoice::factory()->for($this->project)->create();
        $payload = invoicePayload(['amount' => $invoice->amount]);
        unset($payload['discount_amount']);

        $this->actingAs($this->user)
            ->put(route('invoices.update', $invoice), $payload)
            ->assertInvalid(['discount_amount']);
    });

    it('rejects a discount_amount greater than amount', function () {
        $invoice = Invoice::factory()->for($this->project)->create(['amount' => 1000]);

        $this->actingAs($this->user)
            ->put(route('invoices.update', $invoice), invoicePayload(['amount' => 1000, 'discount_amount' => 1001]))
            ->assertInvalid(['discount_amount']);
    });
});

describe('destroy', function () {
    it('deletes the invoice', function () {
        $invoice = Invoice::factory()->for($this->project)->create();

        $this->actingAs($this->user)
            ->delete(route('invoices.destroy', $invoice))
            ->assertRedirect();

        expect(Invoice::find($invoice->id))->toBeNull();
    });
});
