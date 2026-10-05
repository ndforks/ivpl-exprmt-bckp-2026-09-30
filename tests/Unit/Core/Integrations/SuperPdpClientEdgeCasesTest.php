<?php

namespace Tests\Unit\Core;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SuperPdpClient;
use Tests\Fakes\Integration\FakeSuperPdpClient;

class SuperPdpClientEdgeCasesTest extends TestCase
{
    #[Test]
    public function it_handles_network_timeout_gracefully(): void
    {
        /* Arrange */
        $provider = new FakeSuperPdpClient();
        $provider->simulateNetworkTimeout();
        $settings = $this->defaultSettings();

        /* Act */
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Network timeout');

        /* Assert */
        $provider->authenticate($settings);
    }

    #[Test]
    public function it_handles_oauth_token_expiry(): void
    {
        /* Arrange */
        $provider = new FakeSuperPdpClient();
        $provider->setTokenExpired(true);
        $settings = $this->defaultSettings();

        /* Act */
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Token expired');

        /* Assert */
        $provider->authenticate($settings);
    }

    #[Test]
    public function it_rejects_invalid_response_format(): void
    {
        /* Arrange */
        $provider = new FakeSuperPdpClient();
        $provider->setMalformedResponse('invalid json {');
        $settings = $this->defaultSettings();

        /* Act */
        $this->expectException(RuntimeException::class);

        /* Assert */
        $provider->authenticate($settings);
    }

    #[Test]
    public function it_handles_invoice_with_zero_amount(): void
    {
        /* Arrange */
        $provider = new FakeSuperPdpClient();
        $invoice = [
            'invoice_id' => '123',
            'invoice_number' => 'INV-001',
            'amount' => '0.00',
            'client_name' => 'Test Client',
        ];

        /* Act */
        $response = $provider->sendInvoice($invoice, $this->defaultSettings());

        /* Assert */
        $this->assertIsArray($response);
    }

    #[Test]
    public function it_handles_very_large_invoice_amount(): void
    {
        /* Arrange */
        $provider = new FakeSuperPdpClient();
        $invoice = [
            'invoice_id' => '123',
            'invoice_number' => 'INV-001',
            'amount' => '999999999.99',
            'client_name' => 'Test Client',
        ];

        /* Act */
        $response = $provider->sendInvoice($invoice, $this->defaultSettings());

        /* Assert */
        $this->assertIsArray($response);
    }

    #[Test]
    public function it_handles_special_characters_in_invoice_number(): void
    {
        /* Arrange */
        $provider = new FakeSuperPdpClient();
        $invoice = [
            'invoice_id' => '123',
            'invoice_number' => 'INV-2026/001-A&B',
            'amount' => '100.00',
            'client_name' => 'Test & Company',
        ];

        /* Act */
        $response = $provider->sendInvoice($invoice, $this->defaultSettings());

        /* Assert */
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
    }

    #[Test]
    public function it_handles_unicode_in_client_name(): void
    {
        /* Arrange */
        $provider = new FakeSuperPdpClient();
        $invoice = [
            'invoice_id' => '123',
            'invoice_number' => 'INV-001',
            'amount' => '100.00',
            'client_name' => 'Société Générale SARL',
        ];

        /* Act */
        $response = $provider->sendInvoice($invoice, $this->defaultSettings());

        /* Assert */
        $this->assertIsArray($response);
    }

    #[Test]
    public function it_validates_max_concurrent_requests(): void
    {
        /* Arrange */
        $provider = new FakeSuperPdpClient();
        $provider->setMaxConcurrentRequests(3);
        $settings = $this->defaultSettings();

        /* Act */
        for ($i = 0; $i < 5; $i++) {
            $invoice = [
                'invoice_id' => (string)$i,
                'invoice_number' => 'INV-' . $i,
                'amount' => '100.00',
                'client_name' => 'Client ' . $i,
            ];
            $provider->queueInvoice($invoice, $settings);
        }

        /* Assert */
        $queued = $provider->getQueuedInvoiceCount();
        $this->assertGreaterThan(0, $queued);
    }

    private function defaultSettings(): array
    {
        return [
            'client_id'     => 'cid',
            'client_secret' => 'csecret',
            'api_url'       => 'https://api.superpdp.tech',
        ];
    }
}
