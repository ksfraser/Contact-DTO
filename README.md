# Ksfraser Contact DTO

A lightweight, self-contained Data Transfer Object for contact/payee information. Designed to be shared across multiple packages including bank import parsers (QIF, OFX, CSV) and CRM integrations.

## Features

- **Zero dependencies** - Pure PHP, no external requirements
- **PHP 7.3+ compatible** - Works with legacy and modern PHP versions
- **Normalized contact fields** - Address broken into components, comprehensive contact details
- **CRM-ready** - Fields for linking to FrontAccounting customers/suppliers
- **Parser-friendly** - Methods for recording transaction statistics and populating from arrays
- **Fully tested** - Comprehensive PHPUnit test suite with 20+ test cases

## Installation

```bash
composer require ksfraser/contact-dto
```

## Quick Start

```php
use Ksfraser\Contact\DTO\ContactData;

$contact = new ContactData();
$contact->name = 'ACME Corp';
$contact->email = 'billing@acme.com';
$contact->phone = '555-0123';
$contact->contact_type = 'vendor';

// Record a transaction
$contact->recordTransaction(150.00, '2023-06-15 10:30:00');

// Export to database
$data = $contact->toArray();
```

## Full Documentation

See [README.md](README.md) for comprehensive documentation including:
- Detailed usage examples
- All available methods
- Property reference
- Testing instructions

## Testing

Run the test suite:

```bash
composer install --dev
vendor/bin/phpunit
```

## Development Environment

- **PHP**: 7.3+ (production), 8.x (development/testing)
- **PHPUnit**: 11 (dev-only)

## License

MIT

## Repository

https://github.com/ksfraser/Contact-DTO
