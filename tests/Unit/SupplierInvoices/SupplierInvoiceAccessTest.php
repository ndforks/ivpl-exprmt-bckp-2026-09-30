<?php

declare(strict_types=1);

namespace Tests\Unit\SupplierInvoices;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SupplierInvoiceAccess;

require_once dirname(__DIR__, 3) . '/application/modules/supplier_invoices/libraries/SupplierInvoiceAccess.php';

final class SupplierInvoiceAccessTest extends TestCase
{
    #[Test]
    public function administrators_can_read_supplier_invoices(): void
    {
        /* Arrange */
        $access = new SupplierInvoiceAccess();

        /* Act & Assert */
        self::assertTrue($access->canRead(SupplierInvoiceAccess::ADMINISTRATOR));
    }

    #[Test]
    public function administrators_can_manage_supplier_invoice_payments(): void
    {
        /* Arrange */
        $access = new SupplierInvoiceAccess();

        /* Act & Assert */
        self::assertTrue($access->canManagePayments(SupplierInvoiceAccess::ADMINISTRATOR));
    }

    #[Test]
    public function non_administrators_cannot_read_supplier_invoices(): void
    {
        /* Arrange */
        $access = new SupplierInvoiceAccess();

        /* Act & Assert */
        self::assertFalse($access->canRead(2));
    }

    #[Test]
    public function non_administrators_cannot_manage_supplier_invoice_attachments(): void
    {
        /* Arrange */
        $access = new SupplierInvoiceAccess();

        /* Act & Assert */
        self::assertFalse($access->canManageAttachments(2));
    }
}
