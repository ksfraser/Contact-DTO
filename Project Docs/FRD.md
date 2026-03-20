---
title: Ksfraser Contact DTO Feature Requirements Document
version: 1.0
date_created: 2026-03-20
owner: Kevin Fraser
tags: [contact-dto, dto, feature-spec, contact-data-transfer]
---

# Feature Requirements Document (FRD)
## ContactData DTO - Detailed Feature Specification

---

## 1. Purpose & Scope

This FRD details the technical requirements and behavioral specifications for the ContactData Data Transfer Object within the `ksfraser/contact-dto` package.

**Intended Audience**: Developers implementing parser integrations, bank import services, and CRM modules that consume ContactData.

**Assumptions**:
- Consumers have access to PHP 7.3+ environment
- Consumers use Composer for dependency management
- Contact data maps to a normalized database schema with separate address fields

---

## 2. Definitions & Terminology

| Term | Definition |
|------|-----------|
| **DTO** | Data Transfer Object - a simple object for carrying data between processes |
| **Payee** | The party receiving or providing funds in a bank transaction (typically from OFX/QIF/CSV statements) |
| **Contact** | A normalized, structured representation of a payee/vendor/customer with separated address components |
| **FA** | FrontAccounting - the target ERP system for customer/supplier linkage |
| **PSR-4** | PHP Standard Recommendation 4 for autoloading; used for namespace-to-directory mapping |
| **ISO 8601** | Date/time format standard; used for all timestamps (YYYY-MM-DD HH:MM:SS) |
| **Idempotent** | An operation that produces the same result when called multiple times |

---

## 3. Detailed Requirements & Specifications

### 3.1 Class Definition

**Namespace**: `Ksfraser\Contact\DTO`  
**Class Name**: `ContactData`  
**Visibility**: Public (no access restrictions on instantiation)  

```php
class ContactData {
    // Properties (all public, no private/protected)
    // Constructor with timestamp initialization
    // 6 public methods
}
```

### 3.2 Property Specifications

All properties are **public** and **untyped** (PHP 7.3 compatible; typed properties added in PHP 7.4).

#### 3.2.1 Core Identifiers

| Property | Type | Initial Value | Mutable | Purpose |
|----------|------|---------------|---------|---------|
| `id` | int/string | `''` | Yes | Database primary key; can be int or UUID string |
| `name` | string | `''` | Yes | Unique identifier; used for deduplication; UNIQUE constraint in DB |
| `display_name` | string | `''` | Yes | User-friendly display name; may differ from `name` |
| `contact_type` | string | `'unknown'` | Yes | One of: `'vendor'`, `'customer'`, `'unknown'` |
| `is_active` | int/bool | `1` | Yes | Boolean flag; 1 = active, 0 = inactive |

#### 3.2.2 Contact Details

| Property | Type | Initial Value | Mutable | Purpose |
|----------|------|---------------|---------|---------|
| `email` | string | `''` | Yes | Email address; not validated by DTO |
| `phone` | string | `''` | Yes | Primary phone number; formatted as string (e.g., "555-0123") |
| `phone_extension` | string | `''` | Yes | Phone extension; stored separately for structured queries |
| `fax` | string | `''` | Yes | Fax number; formatted as string |
| `mobile` | string | `''` | Yes | Mobile/cell phone number |
| `website` | string | `''` | Yes | Website URL; not validated (http/https) |

#### 3.2.3 Address (Normalized Fields)

| Property | Type | Initial Value | Mutable | Purpose |
|----------|------|---------------|---------|---------|
| `address_line_1` | string | `''` | Yes | First line of street address; typically street number + name |
| `address_line_2` | string | `''` | Yes | Second line; suite number, unit, etc. |
| `city` | string | `''` | Yes | City/municipality name |
| `state_province` | string | `''` | Yes | State/province code or full name |
| `postal_code` | string | `''` | Yes | ZIP/postal code; stored as string (allows leading zeros) |
| `country` | string | `''` | Yes | Full country name (e.g., "United States", not "US") |
| `country_code` | string | `''` | Yes | ISO 3166-1 alpha-2 country code (e.g., "US", "CA", "UK") |

#### 3.2.4 Business Details

| Property | Type | Initial Value | Mutable | Purpose |
|----------|------|---------------|---------|---------|
| `company_name` | string | `''` | Yes | Legal company/organization name |
| `department` | string | `''` | Yes | Department within the company |
| `contact_person` | string | `''` | Yes | Primary point of contact (individual name) |
| `tax_id` | string | `''` | Yes | Tax ID, VAT ID, EIN, SSN, etc.; format varies by country |
| `registration_number` | string | `''` | Yes | Business registration number; jurisdiction-specific |

#### 3.2.5 CRM Linkage

| Property | Type | Initial Value | Mutable | Purpose |
|----------|------|---------------|---------|---------|
| `fa_customer_id` | string | `''` | Yes | FrontAccounting customer ID; empty if not yet linked |
| `fa_supplier_id` | string | `''` | Yes | FrontAccounting supplier ID; empty if not yet linked |

#### 3.2.6 Metadata & Statistics

| Property | Type | Initial Value | Mutable | Purpose |
|----------|------|---------------|---------|---------|
| `notes` | string | `''` | Yes | Free-form notes/comments about the contact |
| `tags` | string | `''` | Yes | Comma-separated tags (e.g., "vip,preferred,active") |
| `transaction_count` | int | `0` | Yes | Cumulative transaction count; incremented by recordTransaction() |
| `last_transaction_ts` | string | `''` | Yes | ISO 8601 timestamp of most recent transaction |
| `total_amount` | float/int | `0` | Yes | Cumulative transaction amount in primary currency |

#### 3.2.7 Timestamps

| Property | Type | Initial Value | Mutable | Purpose |
|----------|------|---------------|---------|---------|
| `created_ts` | string | date('Y-m-d H:i:s') | No | Set in constructor; ISO 8601 format |
| `updated_ts` | string | date('Y-m-d H:i:s') | Yes | Set in constructor; updated when methods modify properties |

### 3.3 Method Specifications

#### 3.3.1 Constructor

```php
public function __construct()
```

**Purpose**: Initialize ContactData with current timestamps.

**Behavior**:
- Sets `created_ts` to current datetime (ISO 8601)
- Sets `updated_ts` to current datetime (ISO 8601)
- All other properties remain at their initial values (empty strings, 0)

**Performance**: < 0.1ms

**Example**:
```php
$contact = new ContactData();
// $contact->created_ts = "2026-03-20 15:30:45"
// $contact->updated_ts = "2026-03-20 15:30:45"
```

#### 3.3.2 getFullAddress()

```php
public function getFullAddress(): string
```

**Purpose**: Reconstruct a full address string from normalized address components.

**Behavior**:
- Iterates through address properties in order: address_line_1, address_line_2, city, state_province, postal_code, country
- Collects non-empty values into an array
- Joins with `', '` (comma-space)
- Returns string (empty if no address components set)

**Performance**: < 0.1ms

**Example**:
```php
$contact->address_line_1 = "123 Main St";
$contact->address_line_2 = "Suite 100";
$contact->city = "New York";
$contact->state_province = "NY";
$contact->postal_code = "10001";
$contact->country = "United States";

echo $contact->getFullAddress();
// Output: "123 Main St, Suite 100, New York, NY, 10001, United States"
```

**Edge Cases**:
- Partial address (only city): "New York"
- Empty address: ""
- Single component: "New York"

#### 3.3.3 recordTransaction()

```php
public function recordTransaction($amount, $timestamp = ''): void
```

**Parameters**:
- `$amount` (float|int): Transaction amount; added to `total_amount`
- `$timestamp` (string): Optional ISO 8601 datetime; if not provided, uses current time

**Purpose**: Update transaction statistics and timestamp when a new transaction is recorded from this contact.

**Behavior**:
1. Increment `transaction_count` by 1
2. Add `$amount` to `total_amount`
3. If `$timestamp` provided, set `last_transaction_ts` = `$timestamp`; else set to current datetime
4. Update `updated_ts` to current datetime

**Performance**: < 0.1ms

**Example**:
```php
$contact->recordTransaction(150.50, "2026-03-20 10:15:00");
// $contact->transaction_count = 1
// $contact->total_amount = 150.50
// $contact->last_transaction_ts = "2026-03-20 10:15:00"

$contact->recordTransaction(50.25);
// $contact->transaction_count = 2
// $contact->total_amount = 200.75
// $contact->last_transaction_ts = current datetime
```

**Note**: No currency conversion or multi-currency support; assumes all amounts in same currency.

#### 3.3.4 linkToFAEntity()

```php
public function linkToFAEntity($entity_id, $entity_type = 'supplier'): void
```

**Parameters**:
- `$entity_id` (string): FrontAccounting customer or supplier ID
- `$entity_type` (string): One of `'customer'` or `'supplier'` (default: `'supplier'`)

**Purpose**: Link this contact to a FrontAccounting customer or supplier master record.

**Behavior**:
1. If `$entity_type === 'customer'`:
   - Set `fa_customer_id` = `$entity_id`
   - Set `contact_type` = `'customer'`
2. Else if `$entity_type === 'supplier'`:
   - Set `fa_supplier_id` = `$entity_id`
   - Set `contact_type` = `'vendor'`
3. Update `updated_ts` to current datetime

**Performance**: < 0.1ms

**Example**:
```php
$contact->linkToFAEntity("CUST001", "customer");
// $contact->fa_customer_id = "CUST001"
// $contact->contact_type = "customer"

$contact->linkToFAEntity("SUP005", "supplier");
// $contact->fa_supplier_id = "SUP005"
// $contact->contact_type = "vendor"
```

#### 3.3.5 fromArray()

```php
public function fromArray($data): void
```

**Parameters**:
- `$data` (array): Associative array with keys matching property names

**Purpose**: Populate ContactData properties from an associative array (typically a database row).

**Behavior**:
1. Iterate through `$data` array
2. For each key-value pair, if a matching property exists on this object, set `$this->$key = $value`
3. Silently skip any keys that don't match properties
4. Does NOT update `updated_ts` (preserves original timestamp from database)

**Performance**: < 0.2ms for typical row (40 properties)

**Example**:
```php
$row = [
    'name' => 'ACME Corp',
    'email' => 'info@acme.com',
    'city' => 'Boston',
    'nonexistent_field' => 'ignored',
];

$contact->fromArray($row);
// $contact->name = "ACME Corp"
// $contact->email = "info@acme.com"
// $contact->city = "Boston"
// nonexistent_field is not set
```

#### 3.3.6 toArray()

```php
public function toArray(): array
```

**Purpose**: Export all properties as an associative array for database persistence.

**Behavior**:
- Calls PHP's `get_object_vars($this)` to capture all public properties
- Returns array with all properties (including empty strings, zeros)

**Performance**: < 0.2ms

**Returns**: Associative array with 40+ keys

**Example**:
```php
$contact->name = "ACME Corp";
$contact->email = "info@acme.com";

$data = $contact->toArray();
// Returns: [
//     'id' => '',
//     'name' => 'ACME Corp',
//     'email' => 'info@acme.com',
//     'phone' => '',
//     ... (all 40+ properties)
// ]
```

#### 3.3.7 getDisplayName()

```php
public function getDisplayName(): string
```

**Purpose**: Get display name with fallback to generic name if display_name is empty.

**Behavior**:
- If `display_name` is not empty, return `display_name`
- Else return `name`
- If both empty, return empty string

**Performance**: < 0.1ms

**Example**:
```php
$contact->name = "Internal Name";
$contact->display_name = "Public Display Name";
echo $contact->getDisplayName();  // Output: "Public Display Name"

$contact->display_name = "";
echo $contact->getDisplayName();  // Output: "Internal Name"
```

---

## 4. Data Contracts & Type System

### 4.1 Property Type Guidance

While ContactData uses untyped properties for PHP 7.3 compatibility, consumers should observe these types:

| Property | Type | Notes |
|----------|------|-------|
| Numeric IDs (id) | int \| string | Can be integer PK or UUID string |
| Counters (transaction_count) | int | Always non-negative |
| Amounts (total_amount) | float \| int | Signed; supports negative for refunds |
| Booleans (is_active) | int (0\|1) | Use 1 for true, 0 for false |
| Strings (all others) | string | Can be empty; not null |
| Timestamps (created_ts, updated_ts) | string | ISO 8601 format: "YYYY-MM-DD HH:MM:SS" |

### 4.2 Serialization Contracts

**toArray() Output Contract**:
- All 40+ properties are present in the array
- Empty strings represent null/empty values
- 0 represents zero or unset counts/amounts
- No property is ever absent from toArray() output

**fromArray() Input Contract**:
- Keys matching property names are accepted
- Non-matching keys are silently ignored (idempotent, no errors)
- Empty string values are accepted
- NULL values are converted to empty string (or 0 for numeric properties)

---

## 5. Acceptance Criteria

- **AC-001**: ContactData class instantiates without parameters; constructor sets created_ts and updated_ts
- **AC-002**: All 40+ public properties are initialized to empty string or 0 (except contact_type = 'unknown')
- **AC-003**: getFullAddress() returns comma-separated address components; returns empty string if no components
- **AC-004**: recordTransaction() increments transaction_count and accumulates total_amount
- **AC-005**: linkToFAEntity() sets fa_customer_id or fa_supplier_id and updates contact_type accordingly
- **AC-006**: fromArray() populates matching properties; silently ignores non-matching keys
- **AC-007**: toArray() returns associative array with all 40+ properties
- **AC-008**: getDisplayName() returns display_name if set; falls back to name if empty
- **AC-009**: All methods execute in < 1ms for typical data
- **AC-010**: ContactData is PSR-4 autoloadable at namespace `Ksfraser\Contact\DTO\ContactData`

---

## 6. Constraints & Design Decisions

### 6.1 Why No Validation?

ContactData is intentionally validation-free. Email format, phone format, country codes are not validated by the DTO. This keeps the package lightweight and allows consuming systems to apply their own validation rules.

### 6.2 Why Public Properties?

PHP 7.3 compatibility (no typed properties). Public properties allow simple array-to-object mapping. Consumers can subclass and add getters/setters if needed.

### 6.3 Why Separate from FrontAccounting?

ContactData is independent and reusable across multiple systems. It doesn't import FA libraries or depend on FA infrastructure, maintaining portability.

### 6.4 Why String Timestamps?

ISO 8601 strings are database-friendly and don't require serialization. Each system can parse to their preferred DateTime representation.

---

## 7. Error Handling & Edge Cases

### 7.1 Invalid Entity Types in linkToFAEntity()

If `$entity_type` is neither `'customer'` nor `'supplier'`, the method silently does nothing (no error thrown).

```php
$contact->linkToFAEntity("CUST001", "invalid_type");
// fa_customer_id and fa_supplier_id remain unchanged
// contact_type remains unchanged
```

### 7.2 Negative Amounts in recordTransaction()

Negative amounts are accepted without validation (supports refunds/chargebacks).

```php
$contact->recordTransaction(-50.00);
// total_amount decreases by 50; no error
```

### 7.3 Null Values in fromArray()

NULL values in the input array are converted to empty string or 0.

```php
$contact->fromArray(['name' => null]);
// $contact->name remains unchanged (not null, not set)
```

---

## 8. Future Extensions (Out of Scope for MVP)

- Optional JSON serialization methods (toJson/fromJson)
- Optional validation methods (validateEmail, validatePhone)
- Optional country/currency helpers
- Multi-language support (labels, descriptions)
- Hashing for deduplication (hash based on name + address)

---

## Conclusion

The ContactData DTO provides a minimal, portable data structure for contact normalization. By defining clear property specifications and method contracts, this FRD enables consistent integration across independent systems without requiring a heavy framework or external dependencies.
