# 🏦 Dynamic QRIS Generator (PHP)

[![Latest Stable Version](https://poser.pugx.org/kodinus/dynamicgen-qris/v)](https://packagist.org/packages/kodinus/dynamicgen-qris)
[![Total Downloads](https://poser.pugx.org/kodinus/dynamicgen-qris/downloads)](https://packagist.org/packages/kodinus/dynamicgen-qris)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A lightweight and powerful PHP library to **generate**, **parse**, and **convert** QRIS (Quick Response Code Indonesian Standard).
It follows the **EMVCo QR standard** with **QRIS Indonesia** specifications defined by Bank Indonesia.

> ⚠️ **Note**: This package does **not** provide transaction monitoring or callback features from QRIS providers.
> It is designed for **learning, research, and development purposes only**. Use official PSP/bank services for production.

---

## 🚀 Features

* ✅ **Generate QRIS** (Static & Dynamic)
* 📖 **Parse QRIS** (decode QRIS string into structured data)
* 🔄 **Convert** Static QRIS → Dynamic QRIS
* 🛡️ **CRC Validation** (CRC16-CCITT-FALSE implementation)
* 📊 **Merchant Data Extraction** (acquirer domain, MPAN, NMID, merchant info, etc.)

---

## 📦 Installation

```bash
composer require kodinus/dynamicgen-qris
```

---

## 🔧 Basic Setup

```php
<?php
require_once 'vendor/autoload.php';

use Kodinus\DynamicGenQris\DynamicQRISGenerator;

$generator = new DynamicQRISGenerator();
```

---

## 📚 Usage Examples

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

---

### 2. Generate Dynamic QRIS

```php
$amount = 50000; // Rp 50,000
$merchantData['invoice_id'] = 'INV20250908001';

$dynamicQris = $generator->generate($merchantData, $amount);
echo $dynamicQris;
```

---

### 3. Parse QRIS String

```php
$qrisString = "00020101021126610014COM..."; // example QRIS string
$parsedData = $generator->parse($qrisString);

print_r($parsedData);
```

---

### 4. Extract Merchant Data

```php
$merchantInfo = $generator->extractMerchant($qrisString);

print_r($merchantInfo);
/*
Array (
    [acquirer_domain] => COM.GO-JEK.WWW
    [mpan] => 936009143805979959
    [merchant_name] => Kodingin Digital Nusantara
    [merchant_city] => NGAWI
    ...
)
*/
```

---

### 5. Convert Static → Dynamic QRIS

```php
$dynamicQris = $generator->convertToDynamic($staticQris, 100000, 'INV20250908002');
echo $dynamicQris;
```

---

### 6. Validate QRIS

```php
$isValid = $generator->validateQris($qrisString);

if ($isValid) {
    echo "✅ QRIS is valid!";
} else {
    echo "❌ Invalid QRIS!";
}
```

---

## 📋 Merchant Data Parameters

| Parameter           | Type   | Required | Description                        | Example                      |
| ------------------- | ------ | -------- | ---------------------------------- | ---------------------------- |
| `acquirer_domain`   | string | Yes      | Acquirer domain / payment provider | `COM.GO-JEK.WWW`             |
| `mpan`              | string | Yes      | Merchant Primary Account Number    | `936009143805979959`         |
| `terminal_id`       | string | No       | Terminal ID                        | `G805979959`                 |
| `merchant_category` | string | Yes      | Category: UMI / MID / Large        | `UMI`                        |
| `nmid`              | string | Yes      | National Merchant ID               | `ID1024358806544`            |
| `mcc`               | string | No       | Merchant Category Code             | `5411`                       |
| `merchant_name`     | string | Yes      | Merchant name                      | `Kodingin Digital Nusantara` |
| `merchant_city`     | string | Yes      | Merchant city                      | `NGAWI`                      |
| `postal_code`       | string | No       | Postal code                        | `63281`                      |
| `invoice_id`        | string | No       | Invoice ID (for dynamic QRIS)      | `INV20250908001`             |

---

## 🔑 Constants & Standards

* **Static QRIS (PIM)**: `12` → reusable QR code
* **Dynamic QRIS (PIM)**: `11` → one-time QR with fixed amount
* **Currency Code**: `360` (Indonesian Rupiah)
* **Country Code**: `ID` (Indonesia)
* **Switching Domain**: `ID.CO.QRIS.WWW`

---

## ⚠️ Important Notes

This library is intended for **educational and development purposes only**.

❌ Not included:

* Transaction monitoring
* Callback/payment notifications
* Direct PSP integration

✅ For production:

* Always use official QRIS from PSP/bank
* Implement proper transaction monitoring
* Test thoroughly before deployment
* Follow Bank Indonesia QRIS regulations

---

## 📁 Project Structure

```
kodinus/dynamicgen-qris/
├── src/
│   ├── DynamicQRISGenerator.php
│   ├── Facades/Qris.php
│   └── QrisServiceProvider.php
├── composer.json
├── README.md
└── LICENSE
```

---

## 🤝 Contributing

1. Fork this repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📄 License

Licensed under the [MIT License](LICENSE) © 2025 PT Kodingin Digital Nusantara

---

💡 Pro tip: you can link this `README.en.md` from your main README with a flag icon, e.g.:

```md
[🇮🇩 Bahasa Indonesia](README.md) | [🇬🇧 English](README.en.md)