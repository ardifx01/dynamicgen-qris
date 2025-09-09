# 🏦 Dynamic QRIS Generator (PHP)

[![Latest Stable Version](https://poser.pugx.org/kodinus/dynamicgen-qris/v)](https://packagist.org/packages/kodinus/dynamicgen-qris)
[![Total Downloads](https://poser.pugx.org/kodinus/dynamicgen-qris/downloads)](https://packagist.org/packages/kodinus/dynamicgen-qris)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Library PHP yang powerful untuk **generate**, **parse**, dan **convert** QRIS (Quick Response Code Indonesian Standard). Mengikuti standar **EMVCo QR** dengan spesifikasi **QRIS Indonesia** resmi dari Bank Indonesia.

## 🚀 Fitur Utama

- ✅ **Generate QRIS** - Static dan Dynamic
- 📖 **Parse QRIS** - Decode string QRIS ke data terstruktur  
- 🔄 **Convert** - Ubah Static ke Dynamic QRIS
- 🛡️ **Validasi CRC** - Implementasi CRC16-CCITT-FALSE
- 📊 **Ekstraksi Data** - Extract informasi merchant lengkap

## 📦 Instalasi

```bash
composer require kodinus/dynamicgen-qris
```

## 🔧 Setup Awal

```php
<?php
require_once 'vendor/autoload.php';

use Kodinus\DynamicGenQris\DynamicQRISGenerator;

$generator = new DynamicQRISGenerator();
```

## 📚 Dokumentasi Penggunaan

### 1. Generate Static QRIS

```php
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

$staticQris = $generator->generate($merchantData);
echo $staticQris;
```

### 2. Generate Dynamic QRIS

```php
$amount = 50000; // Rp 50,000
$invoiceId = 'INV20250908001';

$merchantData['invoice_id'] = $invoiceId;
$dynamicQris = $generator->generate($merchantData, $amount);
echo $dynamicQris;
```

### 3. Parse QRIS String

```php
$qrisString = "00020101021126610014COM..."; // QRIS string lengkap
$parsedData = $generator->parse($qrisString);

print_r($parsedData);
// Output: Array berisi semua tag-value QRIS
```

### 4. Ekstraksi Data Merchant

```php
$merchantInfo = $generator->extractMerchant($qrisString);

print_r($merchantInfo);
/* Output:
Array (
    [acquirer_domain] => COM.GO-JEK.WWW
    [mpan] => 936009143805979959
    [merchant_name] => Kodingin Digital Nusantara
    [merchant_city] => NGAWI
    ... dst
)
*/
```

### 5. Convert Static ke Dynamic

```php
$staticQris = "00020101021126610014COM..."; // Static QRIS
$amount = 100000; // Rp 100,000
$newInvoice = 'INV20250908002';

$dynamicQris = $generator->convertToDynamic($staticQris, $amount, $newInvoice);
echo $dynamicQris;
```

### 6. Validasi QRIS

```php
$isValid = $generator->validateQris($qrisString);

if ($isValid) {
    echo "✅ QRIS valid!";
} else {
    echo "❌ QRIS tidak valid!";
}
```

## 📋 Parameter Merchant Data

Pastikan parameter _Merchant Data_ sesuai dengan hasil pembacaan QR Code yang ada dari QRIS milik anda.

| Parameter           | Tipe     | Wajib | Deskripsi                           | Contoh                       |
|---------------------|----------|-------|-------------------------------------|------------------------------|
| `acquirer_domain`   | string   | Ya    | Domain acquirer/penyedia layanan    | `COM.GO-JEK.WWW`            |
| `mpan`              | string   | Ya    | Merchant Primary Account Number     | `936009143805979959`         |
| `terminal_id`       | string   | Tidak | ID Terminal                         | `G805979959`                 |
| `merchant_category` | string   | Ya    | Kategori: UMI/MID/Large            | `UMI`                        |
| `nmid`              | string   | Ya    | National Merchant ID                | `ID1024358806544`            |
| `mcc`               | string   | Tidak | Merchant Category Code              | `5411`                       |
| `merchant_name`     | string   | Ya    | Nama merchant                       | `Kodingin Digital Nusantara` |
| `merchant_city`     | string   | Ya    | Kota merchant                       | `NGAWI`                      |
| `postal_code`       | string   | Tidak | Kode pos                            | `63281`                      |
| `invoice_id`        | string   | Tidak | ID Invoice (untuk dynamic QRIS)     | `INV20250908001`             |

## 🔑 Konstanta & Standar

### Point of Initiation Method (PIM)
- **Static QRIS**: `12` - Dapat digunakan berkali-kali
- **Dynamic QRIS**: `11` - Sekali pakai dengan nominal tetap

### Standar Lainnya
- **Currency Code**: `360` (Indonesian Rupiah)
- **Country Code**: `ID` (Indonesia)
- **Switching Domain**: `ID.CO.QRIS.WWW`

## 🛡️ Keamanan & Validasi

Library ini mengimplementasikan:

- **CRC16-CCITT-FALSE** untuk validasi integritas data
- Format standar **EMVCo QR Code** 
- Spesifikasi **QRIS Indonesia** dari Bank Indonesia
- Validasi struktur dan format data merchant

## 📁 Struktur Project

```
kodinus/dynamicgen-qris/
├── src/
│   ├── DynamicQRISGenerator.php    # Class utama
│   ├── Facades/
│   │   └── Qris.php                # Laravel Facade
│   └── QrisServiceProvider.php     # Laravel Service Provider
├── composer.json
├── README.md
└── LICENSE
```

## 🔗 Framework Integration

### Laravel
```php
// config/app.php
'providers' => [
    Kodinus\DynamicGenQris\QrisServiceProvider::class,
],

// Hanya untuk Laravel 10 kebawah
'aliases' => [
    'Qris' => Kodinus\DynamicGenQris\Facades\Qris::class,
],

// Usage
use Qris;

$qris = Qris::generate($merchantData, 50000);
```

## ⚠️ Penting untuk Diperhatikan

> **Disclaimer**: Library ini dibuat untuk keperluan **pembelajaran**, **riset**, dan **development**.

### Tidak Tersedia:
- ❌ Fitur checking mutasi/callback transaksi
- ❌ Integrasi langsung dengan Payment Service Provider (PSP)
- ❌ Sistem notifikasi pembayaran real-time

### Untuk Produksi:
- ✅ Gunakan QRIS resmi dari bank/PSP terpercaya
- ✅ Implementasikan sistem monitoring transaksi
- ✅ Lakukan testing menyeluruh sebelum deploy
- ✅ Patuhi regulasi Bank Indonesia terkait QRIS

## 🤝 Kontribusi

Kontribusi sangat welcome! Silakan:

1. Fork repository ini
2. Buat branch untuk fitur baru (`git checkout -b feature/amazing-feature`)
3. Commit perubahan (`git commit -m 'Add amazing feature'`)
4. Push ke branch (`git push origin feature/amazing-feature`)
5. Buat Pull Request

## 📞 Support & Bantuan

- 🐛 **Bug Reports**: [GitHub Issues](https://github.com/kodinus/dynamicgen-qris/issues)
- 💡 **Feature Requests**: [GitHub Discussions](https://github.com/kodinus/dynamicgen-qris/discussions)
- 📧 **Email**: halo@kodinus.id
- 📱 **Whatsapp**: [081216446031](https://wa.me/6281216446031?text=Halo+Kodinus!+Mau+tanya+package+QRIS+dynamic+generator+dong)⚠️ Hanya pesan teks aja, tidak melayani telfon untuk menghindari penipuan!

## 📄 Lisensi

Dilisensikan di bawah [MIT License](LICENSE) © 2025 PT Kodingin Digital Nusantara

## 💰 Dukung Kami Melalui Donasi

- Bank BSI: 7308120467 a/n PT Kodingin Digital Nusantara

---

**Dibuat dengan ❤️ di Indonesia** 🇮🇩