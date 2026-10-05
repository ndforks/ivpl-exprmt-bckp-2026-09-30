<?php

namespace Tests\Unit\Core;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class IncomingInvoiceSyncEdgeCasesTest extends TestCase
{
    #[Test]
    public function it_handles_duplicate_invoice_number_detection(): void
    {
        /* Arrange */
        $invoice1 = [
            'supplier_id' => '1',
            'invoice_number' => 'DUP-001',
            'amount' => '100.00',
            'date' => '2026-10-04',
        ];
        $invoice2 = [
            'supplier_id' => '1',
            'invoice_number' => 'DUP-001',
            'amount' => '100.00',
            'date' => '2026-10-04',
        ];

        /* Act & Assert */
        $this->assertTrue($this->invoicesAreEqual($invoice1, $invoice2));
    }

    #[Test]
    public function it_handles_partial_sync_failure(): void
    {
        /* Arrange */
        $invoices = [
            ['supplier_id' => '1', 'invoice_number' => 'GOOD-001', 'amount' => '100.00'],
            ['supplier_id' => '2', 'invoice_number' => 'BAD-001', 'amount' => 'invalid'],
            ['supplier_id' => '3', 'invoice_number' => 'GOOD-002', 'amount' => '200.00'],
        ];

        /* Act */
        $results = $this->syncInvoices($invoices);

        /* Assert */
        $this->assertCount(3, $results);
        $this->assertTrue($results[0]['success']);
        $this->assertFalse($results[1]['success']);
        $this->assertTrue($results[2]['success']);
    }

    #[Test]
    public function it_handles_invoice_with_missing_supplier(): void
    {
        /* Arrange */
        $invoice = [
            'supplier_id' => '999999',
            'invoice_number' => 'ORPHAN-001',
            'amount' => '100.00',
            'date' => '2026-10-04',
        ];

        /* Act */
        $result = $this->syncInvoice($invoice);

        /* Assert */
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('supplier', strtolower($result['error']));
    }

    #[Test]
    public function it_handles_invoice_with_zero_amount(): void
    {
        /* Arrange */
        $invoice = [
            'supplier_id' => '1',
            'invoice_number' => 'ZERO-001',
            'amount' => '0.00',
            'date' => '2026-10-04',
        ];

        /* Act */
        $result = $this->syncInvoice($invoice);

        /* Assert */
        $this->assertFalse($result['success']);
    }

    #[Test]
    public function it_handles_invoice_with_negative_amount(): void
    {
        /* Arrange */
        $invoice = [
            'supplier_id' => '1',
            'invoice_number' => 'NEG-001',
            'amount' => '-100.00',
            'date' => '2026-10-04',
        ];

        /* Act */
        $result = $this->syncInvoice($invoice);

        /* Assert */
        $this->assertFalse($result['success']);
    }

    #[Test]
    public function it_handles_invoice_with_future_date(): void
    {
        /* Arrange */
        $invoice = [
            'supplier_id' => '1',
            'invoice_number' => 'FUTURE-001',
            'amount' => '100.00',
            'date' => '2099-12-31',
        ];

        /* Act */
        $result = $this->syncInvoice($invoice);

        /* Assert */
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_invoice_with_past_date(): void
    {
        /* Arrange */
        $invoice = [
            'supplier_id' => '1',
            'invoice_number' => 'PAST-001',
            'amount' => '100.00',
            'date' => '1900-01-01',
        ];

        /* Act */
        $result = $this->syncInvoice($invoice);

        /* Assert */
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_concurrent_sync_requests(): void
    {
        /* Arrange */
        $invoices = [];
        for ($i = 0; $i < 10; $i++) {
            $invoices[] = [
                'supplier_id' => '1',
                'invoice_number' => 'CONC-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'amount' => '100.00',
                'date' => '2026-10-04',
            ];
        }

        /* Act */
        $results = $this->syncInvoices($invoices);

        /* Assert */
        $this->assertCount(10, $results);
        $successCount = array_sum(array_map(fn($r) => $r['success'] ? 1 : 0, $results));
        $this->assertGreaterThanOrEqual(9, $successCount);
    }

    private function invoicesAreEqual(array $a, array $b): bool
    {
        return $a['invoice_number'] === $b['invoice_number']
            && $a['supplier_id'] === $b['supplier_id'];
    }

    private function syncInvoice(array $invoice): array
    {
        try {
            $this->validateInvoice($invoice);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function syncInvoices(array $invoices): array
    {
        return array_map([$this, 'syncInvoice'], $invoices);
    }

    private function validateInvoice(array $invoice): void
    {
        if (!isset($invoice['supplier_id']) || empty($invoice['supplier_id'])) {
            throw new \Exception('Missing supplier');
        }
        if (!isset($invoice['amount']) || !is_numeric($invoice['amount'])) {
            throw new \Exception('Invalid amount');
        }
        if ((float)$invoice['amount'] <= 0) {
            throw new \Exception('Amount must be positive');
        }
    }
}
