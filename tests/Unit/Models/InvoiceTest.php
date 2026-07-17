<?php

use App\Models\Invoice;

describe('netAmount', function () {
    it('subtracts the discount from the gross amount', function () {
        $invoice = new Invoice(['amount' => 100000, 'discount_amount' => 20000]);

        expect($invoice->netAmount())->toBe(80000);
    });

    it('equals the gross amount when there is no discount', function () {
        $invoice = new Invoice(['amount' => 100000, 'discount_amount' => 0]);

        expect($invoice->netAmount())->toBe(100000);
    });

    it('is zero when the discount equals the full amount, without erroring', function () {
        $invoice = new Invoice(['amount' => 100000, 'discount_amount' => 100000]);

        expect($invoice->netAmount())->toBe(0);
    });
});

describe('discountPercentForDisplay', function () {
    it('is null when there is no discount', function () {
        $invoice = new Invoice(['amount' => 100000, 'discount_amount' => 0]);

        expect($invoice->discountPercentForDisplay())->toBeNull();
    });

    it('rounds the percentage for a normal discount', function () {
        $invoice = new Invoice(['amount' => 100000, 'discount_amount' => 33000]);

        expect($invoice->discountPercentForDisplay())->toBe(33);
    });

    it('returns 100 without dividing by zero when the invoice is fully discounted', function () {
        $invoice = new Invoice(['amount' => 100000, 'discount_amount' => 100000]);

        expect($invoice->discountPercentForDisplay())->toBe(100);
    });
});
