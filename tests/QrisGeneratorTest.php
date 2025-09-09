<?php

namespace Kodinus\DynamicGenQris\Tests;

use Kodinus\DynamicGenQris\DynamicQRISGenerator;
use Kodinus\DynamicGenQris\Facades\Qris;

class QrisGeneratorTest extends TestCase
{
    public function test_service_provider_binds_generator(): void
    {
        $this->assertInstanceOf(
            DynamicQRISGenerator::class,
            $this->app->make('qris.generator')
        );
    }

    public function test_it_generates_and_parses_qris(): void
    {
        $merchantData = [
            'acquirer_domain'   => 'COM.GO-JEK.WWW',
            'mpan'              => '936009143805979959',
            'terminal_id'       => 'G805979959',
            'merchant_category' => 'UMI',
            'nmid'              => 'ID1024358806544',
            'mcc'               => '5411',
            'merchant_name'     => 'Kodingin Digital Nusantara',
            'merchant_city'     => 'NGAWI',
            'postal_code'       => '63281',
        ];

        $qris = Qris::generate($merchantData, 50000);

        $this->assertNotEmpty($qris);

        $parsed = Qris::extractMerchant($qris);

        $this->assertSame('Kodingin Digital Nusantara', $parsed['merchant_name']);
        $this->assertSame('NGAWI', $parsed['merchant_city']);
        $this->assertSame(50000.0, $parsed['amount']);
        $this->assertTrue($parsed['is_dynamic']);
    }
}
