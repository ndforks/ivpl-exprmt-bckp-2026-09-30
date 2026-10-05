<?php

declare(strict_types=1);

namespace Tests\Unit\SupplierInvoices;

use FrenchSupplierInvoiceDataValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3) . '/application/modules/supplier_invoices/libraries/FrenchSupplierInvoiceDataValidator.php';

final class FrenchSupplierInvoiceDataValidatorTest extends TestCase
{
    #[Test]
    public function it_accepts_a_consistent_french_supplier_invoice(): void
    {
        /* Arrange */
        $invoice = [
            'supplier' => [
                'supplier_name' => 'French Supplier',
                'supplier_address_1' => '1 Rue de Paris',
                'supplier_country' => 'FR',
                'supplier_tax_code' => '123456789',
                'supplier_vat_id' => 'FR32123456789',
            ],
            'invoice' => [
                'supplier_invoice_number' => 'INV-2026/001',
                'supplier_invoice_date' => '2026-10-04',
                'currency_code' => 'EUR',
                'subtotal' => 100,
                'tax_total' => 20,
                'total' => 120,
            ],
            'items' => [['item_name' => 'Consulting']],
        ];

        /* Act */
        $errors = (new FrenchSupplierInvoiceDataValidator())->validate($invoice);

        /* Assert */
        self::assertSame([], $errors);
    }

    #[Test]
    public function it_rejects_invalid_siren_in_french_invoices(): void
    {
        /* Arrange */
        $invoice = [
            'supplier' => ['supplier_country' => 'FR', 'supplier_tax_code' => '123'],
            'invoice' => [],
            'items' => [],
        ];

        /* Act */
        $errors = (new FrenchSupplierInvoiceDataValidator())->validate($invoice);

        /* Assert */
        self::assertContains('France: supplier SIREN must contain exactly 9 digits.', $errors);
    }

    #[Test]
    public function it_rejects_invalid_vat_key_in_french_invoices(): void
    {
        /* Arrange */
        $invoice = [
            'supplier' => ['supplier_country' => 'FR', 'supplier_tax_code' => '123456789', 'supplier_vat_id' => 'FR00123456789'],
            'invoice' => [],
            'items' => [],
        ];

        /* Act */
        $errors = (new FrenchSupplierInvoiceDataValidator())->validate($invoice);

        /* Assert */
        self::assertContains('France: supplier VAT ID key does not match the SIREN.', $errors);
    }

    #[Test]
    public function it_rejects_invalid_invoice_number_format_for_french_invoices(): void
    {
        /* Arrange */
        $invoice = [
            'supplier' => ['supplier_country' => 'FR'],
            'invoice' => ['supplier_invoice_number' => 'INV 001'],
            'items' => [],
        ];

        /* Act */
        $errors = (new FrenchSupplierInvoiceDataValidator())->validate($invoice);

        /* Assert */
        self::assertContains('France: invoice number must use only letters, digits, +, -, _, or /.', $errors);
    }

    #[Test]
    public function it_rejects_invalid_invoice_date_in_french_invoices(): void
    {
        /* Arrange */
        $invoice = [
            'supplier' => ['supplier_country' => 'FR'],
            'invoice' => ['supplier_invoice_date' => '2026-02-30'],
            'items' => [],
        ];

        /* Act */
        $errors = (new FrenchSupplierInvoiceDataValidator())->validate($invoice);

        /* Assert */
        self::assertContains('France: invoice date must be a valid ISO date.', $errors);
    }

    #[Test]
    public function it_rejects_invalid_currency_code_for_french_invoices(): void
    {
        /* Arrange */
        $invoice = [
            'supplier' => ['supplier_country' => 'FR'],
            'invoice' => ['currency_code' => 'EURO'],
            'items' => [],
        ];

        /* Act */
        $errors = (new FrenchSupplierInvoiceDataValidator())->validate($invoice);

        /* Assert */
        self::assertContains('France: invoice currency must be a three-letter ISO 4217 code.', $errors);
    }

    #[Test]
    public function it_rejects_empty_line_items_in_french_invoices(): void
    {
        /* Arrange */
        $invoice = [
            'supplier' => ['supplier_country' => 'FR'],
            'invoice' => [],
            'items' => [],
        ];

        /* Act */
        $errors = (new FrenchSupplierInvoiceDataValidator())->validate($invoice);

        /* Assert */
        self::assertContains('France: at least one invoice line is required for structured supplier invoice import.', $errors);
    }

    #[Test]
    public function it_rejects_mismatched_totals_in_french_invoices(): void
    {
        /* Arrange */
        $invoice = [
            'supplier' => ['supplier_country' => 'FR'],
            'invoice' => ['subtotal' => 100, 'tax_total' => 20, 'total' => 125],
            'items' => [['item_name' => 'Test']],
        ];

        /* Act */
        $errors = (new FrenchSupplierInvoiceDataValidator())->validate($invoice);

        /* Assert */
        self::assertContains('France: invoice total must equal subtotal plus VAT within two cents.', $errors);
    }

    #[Test]
    public function it_does_not_apply_french_rules_to_foreign_suppliers(): void
    {
        /* Arrange */
        $invoice = [
            'supplier' => ['supplier_country' => 'DE'],
            'invoice' => [],
            'items' => [],
        ];

        /* Act */
        $errors = (new FrenchSupplierInvoiceDataValidator())->validate($invoice);

        /* Assert */
        self::assertSame([], $errors);
    }
}
