<?php

namespace Kodinus\DynamicGenQris;

use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * QRIS Generator & Parser
 *
 * Utility untuk membuat, membaca, dan mengkonversi QRIS (Quick Response Code Indonesian Standard).
 * Mengikuti format EMVCo QR dengan tambahan spesifikasi QRIS Indonesia.
 *
 * @author  Amir Zuhdi Wibowo <cakadi190@gmail.com>
 * @author  PT Kodingin Digital Nusantara <halo@kodinus.id>
 * @version 1.0.0
 * @since   2025-09-08
 */
class DynamicQRISGenerator
{
    /**
     * QRIS string yang telah di-generate
     *
     * @var string
     */
    private string $qris = '';

    /**
     * Konstanta untuk Point of Initiation Method
     */
    private const PIM_DYNAMIC = '11';
    private const PIM_STATIC = '12';

    /**
     * Konstanta untuk Currency Code
     */
    private const CURRENCY_IDR = '360';

    /**
     * Konstanta untuk Country Code
     */
    private const COUNTRY_CODE = 'ID';

    /**
     * Konstanta untuk QRIS switching domain
     */
    private const SWITCHING_DOMAIN = 'ID.CO.QRIS.WWW';

    /**
     * Konstanta untuk CRC calculation
     */
    private const CRC_INITIAL = 0xFFFF;
    private const CRC_POLYNOMIAL = 0x1021;

    /**
     * EMV Tag structure untuk berbagai field
     */
    private const EMV_TAGS = [
        'PAYLOAD_FORMAT' => '00',
        'POINT_OF_INITIATION' => '01',
        'MERCHANT_ACCOUNT_INFO' => '26',
        'SWITCHING' => '51',
        'MERCHANT_CATEGORY_CODE' => '52',
        'TRANSACTION_CURRENCY' => '53',
        'TRANSACTION_AMOUNT' => '54',
        'COUNTRY_CODE' => '58',
        'MERCHANT_NAME' => '59',
        'MERCHANT_CITY' => '60',
        'POSTAL_CODE' => '61',
        'ADDITIONAL_DATA' => '62',
        'CRC' => '63'
    ];

    /**
     * Sub-tags untuk Merchant Account Info
     */
    private const MAI_SUBTAGS = [
        'ACQUIRER_DOMAIN' => '00',
        'MPAN' => '01',
        'TERMINAL_ID' => '02',
        'MERCHANT_CATEGORY' => '03'
    ];

    /**
     * Sub-tags untuk Switching
     */
    private const SWITCHING_SUBTAGS = [
        'SWITCHING_DOMAIN' => '00',
        'NMID' => '02',
        'MERCHANT_CATEGORY' => '03'
    ];

    /**
     * Sub-tags untuk Additional Data
     */
    private const ADDITIONAL_SUBTAGS = [
        'INVOICE_ID' => '01'
    ];

    /**
     * Membuat EMV Tag dengan format: tag + length + value
     *
     * @param string $id    Tag identifier (2 karakter)
     * @param string $value Nilai yang akan di-encode
     *
     * @return string EMV formatted tag
     *
     * @throws InvalidArgumentException Jika tag ID tidak valid
     */
    private function emvTag(string $id, string $value): string
    {
        if (strlen($id) !== 2) {
            throw new InvalidArgumentException('Tag ID harus 2 karakter');
        }

        if (strlen($value) > 99) {
            throw new InvalidArgumentException('Nilai terlalu panjang untuk EMV tag');
        }

        $length = strlen($value);
        return $id . str_pad((string) $length, 2, '0', STR_PAD_LEFT) . $value;
    }

    /**
     * Generate QRIS string dari data merchant
     *
     * @param array      $merchantData Data merchant yang diperlukan
     * @param float|null $amount       Nominal transaksi (null untuk static QRIS)
     *
     * @return string QRIS string yang siap digunakan
     *
     * @throws InvalidArgumentException Jika data merchant tidak lengkap atau tidak valid
     */
    public function generate(array $merchantData, ?float $amount = null): string
    {
        $this->validateMerchantData($merchantData);

        $qris = '';

        $qris .= $this->emvTag(self::EMV_TAGS['PAYLOAD_FORMAT'], '01');
        $qris .= $this->emvTag(
            self::EMV_TAGS['POINT_OF_INITIATION'],
            $amount ? self::PIM_DYNAMIC : self::PIM_STATIC
        );

        $qris .= $this->buildMerchantAccountInfo($merchantData);
        $qris .= $this->buildSwitchingInfo($merchantData);

        $qris .= $this->emvTag(
            self::EMV_TAGS['MERCHANT_CATEGORY_CODE'],
            $merchantData['mcc'] ?? '0000'
        );
        $qris .= $this->emvTag(self::EMV_TAGS['TRANSACTION_CURRENCY'], self::CURRENCY_IDR);

        if ($amount !== null) {
            $qris .= $this->emvTag(
                self::EMV_TAGS['TRANSACTION_AMOUNT'],
                number_format($amount, 2, '.', '')
            );

            if (empty($merchantData['invoice_id'])) {
                $merchantData['invoice_id'] = $this->generateInvoiceId();
            }
        }

        $qris .= $this->emvTag(self::EMV_TAGS['COUNTRY_CODE'], self::COUNTRY_CODE);
        $qris .= $this->emvTag(self::EMV_TAGS['MERCHANT_NAME'], $merchantData['merchant_name']);
        $qris .= $this->emvTag(self::EMV_TAGS['MERCHANT_CITY'], $merchantData['merchant_city']);

        if (!empty($merchantData['postal_code'])) {
            $qris .= $this->emvTag(self::EMV_TAGS['POSTAL_CODE'], $merchantData['postal_code']);
        }

        if (!empty($merchantData['invoice_id'])) {
            $additionalData = $this->emvTag(
                self::ADDITIONAL_SUBTAGS['INVOICE_ID'],
                $merchantData['invoice_id']
            );
            $qris .= $this->emvTag(self::EMV_TAGS['ADDITIONAL_DATA'], $additionalData);
        }

        $qris .= $this->generateCrc($qris);

        $this->qris = $qris;
        return $qris;
    }

    /**
     * Membangun Merchant Account Info section
     *
     * @param array $merchantData Data merchant
     *
     * @return string EMV formatted Merchant Account Info
     */
    private function buildMerchantAccountInfo(array $merchantData): string
    {
        $mai = '';
        $mai .= $this->emvTag(self::MAI_SUBTAGS['ACQUIRER_DOMAIN'], $merchantData['acquirer_domain']);
        $mai .= $this->emvTag(self::MAI_SUBTAGS['MPAN'], $merchantData['mpan']);

        if (!empty($merchantData['terminal_id'])) {
            $mai .= $this->emvTag(self::MAI_SUBTAGS['TERMINAL_ID'], $merchantData['terminal_id']);
        }

        $mai .= $this->emvTag(self::MAI_SUBTAGS['MERCHANT_CATEGORY'], $merchantData['merchant_category']);

        return $this->emvTag(self::EMV_TAGS['MERCHANT_ACCOUNT_INFO'], $mai);
    }

    /**
     * Membangun Switching Info section
     *
     * @param array $merchantData Data merchant
     *
     * @return string EMV formatted Switching Info
     */
    private function buildSwitchingInfo(array $merchantData): string
    {
        $switching = '';
        $switching .= $this->emvTag(self::SWITCHING_SUBTAGS['SWITCHING_DOMAIN'], self::SWITCHING_DOMAIN);
        $switching .= $this->emvTag(self::SWITCHING_SUBTAGS['NMID'], $merchantData['nmid']);
        $switching .= $this->emvTag(self::SWITCHING_SUBTAGS['MERCHANT_CATEGORY'], $merchantData['merchant_category']);

        return $this->emvTag(self::EMV_TAGS['SWITCHING'], $switching);
    }

    /**
     * Generate CRC untuk QRIS
     *
     * @param string $qris QRIS string tanpa CRC
     *
     * @return string EMV formatted CRC tag
     */
    private function generateCrc(string $qris): string
    {
        $qrisWithCrcPlaceholder = $qris . self::EMV_TAGS['CRC'] . '04';
        $crc = $this->crc16CcittFalse($qrisWithCrcPlaceholder);
        $crcHex = strtoupper(dechex($crc));
        $crcPadded = str_pad($crcHex, 4, '0', STR_PAD_LEFT);

        return $this->emvTag(self::EMV_TAGS['CRC'], $crcPadded);
    }

    /**
     * Validasi data merchant yang diperlukan
     *
     * @param array $merchantData Data merchant untuk divalidasi
     *
     * @throws InvalidArgumentException Jika data tidak lengkap atau tidak valid
     */
    private function validateMerchantData(array $merchantData): void
    {
        $requiredFields = [
            'acquirer_domain',
            'mpan',
            'merchant_category',
            'nmid',
            'merchant_name',
            'merchant_city'
        ];

        foreach ($requiredFields as $field) {
            if (empty($merchantData[$field])) {
                throw new InvalidArgumentException("Field '{$field}' diperlukan dan tidak boleh kosong");
            }
        }
    }

    /**
     * Parse QRIS string menjadi array key-value
     *
     * @param string $qris QRIS string yang akan di-parse
     *
     * @return array Array dengan format [tag => value]
     *
     * @throws InvalidArgumentException Jika QRIS string tidak valid
     */
    public function parse(string $qris): array
    {
        if (empty($qris)) {
            throw new InvalidArgumentException('QRIS string tidak boleh kosong');
        }

        $data = [];
        $pos = 0;
        $length = strlen($qris);

        while ($pos < $length - 4) {
            if ($pos + 4 > $length) {
                break;
            }

            $tag = substr($qris, $pos, 2);
            $tagLength = intval(substr($qris, $pos + 2, 2));

            if ($pos + 4 + $tagLength > $length) {
                break;
            }

            $value = substr($qris, $pos + 4, $tagLength);
            $data[$tag] = $value;
            $pos += 4 + $tagLength;
        }

        return $data;
    }

    /**
     * Parse subfields dari tag tertentu (seperti tag 26, 51, 62)
     *
     * @param string $subfield String subfield yang akan di-parse
     *
     * @return array Array dengan format [subtag => value]
     */
    private function parseSubfields(string $subfield): array
    {
        if (empty($subfield)) {
            return [];
        }

        $data = [];
        $pos = 0;
        $length = strlen($subfield);

        while ($pos < $length) {
            if ($pos + 4 > $length) {
                break;
            }

            $tag = substr($subfield, $pos, 2);
            $tagLength = intval(substr($subfield, $pos + 2, 2));

            if ($pos + 4 + $tagLength > $length) {
                break;
            }

            $value = substr($subfield, $pos + 4, $tagLength);
            $data[$tag] = $value;
            $pos += 4 + $tagLength;
        }

        return $data;
    }

    /**
     * Ekstrak data merchant dari QRIS string
     *
     * @param string $qris QRIS string yang akan diekstrak
     *
     * @return array Data merchant dalam format array
     *
     * @throws InvalidArgumentException Jika QRIS string tidak valid
     */
    public function extractMerchant(string $qris): array
    {
        $parsed = $this->parse($qris);
        $mai = $this->parseSubfields($parsed[self::EMV_TAGS['MERCHANT_ACCOUNT_INFO']] ?? '');
        $switching = $this->parseSubfields($parsed[self::EMV_TAGS['SWITCHING']] ?? '');

        $additionalData = [];
        if (isset($parsed[self::EMV_TAGS['ADDITIONAL_DATA']])) {
            $additionalData = $this->parseSubfields($parsed[self::EMV_TAGS['ADDITIONAL_DATA']]);
        }

        return [
            'acquirer_domain' => $mai[self::MAI_SUBTAGS['ACQUIRER_DOMAIN']] ?? '',
            'mpan' => $mai[self::MAI_SUBTAGS['MPAN']] ?? '',
            'terminal_id' => $mai[self::MAI_SUBTAGS['TERMINAL_ID']] ?? '',
            'merchant_category' => $mai[self::MAI_SUBTAGS['MERCHANT_CATEGORY']] ?? '',
            'nmid' => $switching[self::SWITCHING_SUBTAGS['NMID']] ?? '',
            'mcc' => $parsed[self::EMV_TAGS['MERCHANT_CATEGORY_CODE']] ?? '0000',
            'merchant_name' => $parsed[self::EMV_TAGS['MERCHANT_NAME']] ?? '',
            'merchant_city' => $parsed[self::EMV_TAGS['MERCHANT_CITY']] ?? '',
            'postal_code' => $parsed[self::EMV_TAGS['POSTAL_CODE']] ?? '',
            'invoice_id' => $additionalData[self::ADDITIONAL_SUBTAGS['INVOICE_ID']] ?? '',
            'amount' => isset($parsed[self::EMV_TAGS['TRANSACTION_AMOUNT']])
                ? floatval($parsed[self::EMV_TAGS['TRANSACTION_AMOUNT']])
                : null,
            'is_dynamic' => ($parsed[self::EMV_TAGS['POINT_OF_INITIATION']] ?? '') === self::PIM_DYNAMIC
        ];
    }

    /**
     * Konversi QRIS static menjadi dynamic dengan nominal dan invoice ID tertentu
     *
     * @param string      $staticQris     QRIS static yang akan dikonversi
     * @param float       $amount         Nominal transaksi
     * @param string|null $newInvoiceId   Invoice ID baru (optional, akan di-generate otomatis jika null)
     *
     * @return string QRIS dynamic yang siap digunakan
     *
     * @throws InvalidArgumentException Jika QRIS static tidak valid atau amount negatif
     */
    public function convertToDynamic(string $staticQris, float $amount, ?string $newInvoiceId = null): string
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount harus lebih besar dari 0');
        }

        $merchantData = $this->extractMerchant($staticQris);

        if ($merchantData['is_dynamic']) {
            throw new InvalidArgumentException('QRIS yang diberikan sudah dalam format dynamic');
        }

        $merchantData['invoice_id'] = $newInvoiceId
            ?? $merchantData['invoice_id']
            ?? $this->generateInvoiceId();

        return $this->generate($merchantData, $amount);
    }

    /**
     * Generate Invoice ID otomatis (opsional).
     * Kalau kamu ingin manual, cukup isi langsung di $merchantData['invoice_id'].
     * Kalau tidak mau ada invoice, biarkan kosong/null.
     *
     * @param string|null $prefix Prefix custom, default "INV"
     * @return string
     */
    private function generateInvoiceId(?string $prefix = 'INV'): string
    {
        if ($prefix === null) {
            return '';
        }

        return $prefix . date('YmdHis') . sprintf('%03d', mt_rand(0, 999));
    }

    /**
     * Implementasi CRC16-CCITT-FALSE untuk validasi QRIS
     *
     * @param string $data Data yang akan dihitung CRC-nya
     *
     * @return int Nilai CRC dalam format integer
     */
    private function crc16CcittFalse(string $data): int
    {
        $crc = self::CRC_INITIAL;
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= (ord($data[$i]) << 8);

            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ self::CRC_POLYNOMIAL;
                } else {
                    $crc <<= 1;
                }
                $crc &= 0xFFFF;
            }
        }

        return $crc;
    }

    /**
     * Validasi QRIS string dengan mengecek CRC
     *
     * @param string $qris QRIS string yang akan divalidasi
     *
     * @return bool True jika QRIS valid, false jika tidak
     */
    public function validateQris(string $qris): bool
    {
        try {
            $parsed = $this->parse($qris);

            if (!isset($parsed[self::EMV_TAGS['CRC']])) {
                return false;
            }

            $providedCrc = $parsed[self::EMV_TAGS['CRC']];
            $qrisWithoutCrc = substr($qris, 0, -8);
            $qrisWithCrcPlaceholder = $qrisWithoutCrc . self::EMV_TAGS['CRC'] . '04';

            $calculatedCrc = strtoupper(dechex($this->crc16CcittFalse($qrisWithCrcPlaceholder)));
            $calculatedCrc = str_pad($calculatedCrc, 4, '0', STR_PAD_LEFT);

            return $providedCrc === $calculatedCrc;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Cetak QR Code dalam bentuk teks ke terminal menggunakan bacon/bacon-qr-code.
     *
     * @param string $string String yang akan di-encode menjadi QR Code.
     * @return string Representasi teks dari QR Code.
     *
     * @throws RuntimeException Jika dependensi bacon/bacon-qr-code tidak terpasang.
     */
    public function printQrCode(string $string): string
    {
        if (!class_exists(\BaconQrCode\Writer::class)) {
            throw new RuntimeException('bacon/bacon-qr-code package is required. Install via composer require bacon/bacon-qr-code.');
        }

        $renderer = new \BaconQrCode\Renderer\PlainTextRenderer();
        $writer = new \BaconQrCode\Writer($renderer);
        $qrCode = $writer->writeString($string);

        echo $qrCode . PHP_EOL;

        return $qrCode;
    }

    /**
     * Get QRIS string yang terakhir di-generate
     *
     * @return string QRIS string atau empty string jika belum ada yang di-generate
     */
    public function getLastGeneratedQris(): string
    {
        return $this->qris;
    }
}