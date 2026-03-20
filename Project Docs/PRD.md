---
title: Ksfraser Contact DTO Product Requirements Document
version: 1.0
date_created: 2026-03-20
owner: Kevin Fraser
tags: [contact-dto, dto, bank-import, crm, shared-component]
---

# Product Requirements Document (PRD)
## Ksfraser Contact DTO - Shared Data Transfer Object

---

## Executive Summary

### Problem Statement

Bank import systems, CRM platforms, and accounting modules each handle contact/payee data differently, leading to fragmentation, duplicate logic, and inconsistent contact representation across the ecosystem. When multiple parsers (QIF, OFX, CSV) extract contact information from bank statements, there's no standardized format for representing this data, making it difficult to normalize, deduplicate, and integrate with external systems like FrontAccounting.

### Proposed Solution

A lightweight, zero-dependency Data Transfer Object (DTO) package that provides a standardized, normalized contact data structure. The ContactData DTO serves as a portable contract between bank import parsers, CRM modules, and accounting integrations, eliminating the need for each system to implement its own contact model.

### Success Criteria

1. **Reusability**: The package is adopted by at least 3 independent systems (bank_import, qifparser, ksf_ofxparser) with zero modifications
2. **Zero Dependencies**: Package has no external dependencies beyond PHP 7.3+, enabling use in legacy environments
3. **Performance**: All ContactData operations (getFullAddress, recordTransaction, fromArray/toArray) complete in <1ms
4. **Test Coverage**: 25+ test cases with 100% line coverage of the DTO class
5. **Cross-PHP Compatibility**: Works correctly on PHP 7.3+ (production) and PHP 8.x+ (development)

---

## Executive Objectives

### Business Goals

- **Reduce Development Friction**: One source of truth for contact structure eliminates re-implementation
- **Enable CRM Integration**: Standardized payee data enables seamless FrontAccounting customer/supplier creation
- **Support Legacy Infrastructure**: PHP 7.3 compatibility maintains support for production systems that cannot upgrade
- **Facilitate Parser Enhancement**: Cleanly separate payee extraction from transaction parsing

### Technical Goals

- **Zero Framework Dependency**: No dependency on Laravel, Symfony, or other frameworks; pure PHP
- **Extensibility**: Designed for inheritance; parsers can create domain-specific payee classes (QifPayee, OfxMerchant)
- **Database Agnostic**: Works with any storage backend; includes fromArray/toArray for persistence
- **Portability**: Package can be published to Packagist and used independently

---

## User Experience & Functionality

### User Personas

1. **Parser Developer** (OFX, QIF, CSV parser maintainers)
   - Needs: Standardized structure for extracted payee data
   - Pain: Currently maps payee info to transaction.merchant field (lossy)
   - Goal: Cleanly separate payee metadata from transaction data

2. **Bank Import System Developer** (ksf_bank_import maintainers)
   - Needs: Normalized contact data for database persistence
   - Pain: Currently uses flat merchant field; no support for structured address or CRM linkage
   - Goal: Store and match contacts; offer vendor/customer creation

3. **Contact Data Consumer** (CRM, accounting systems)
   - Needs: Portable contact structure for integration
   - Pain: Each system reinvents contact normalization
   - Goal: Accept ContactData objects directly without conversion

### Core User Stories

#### Story 1: Parser Uses ContactData for Payee Extraction
```
As a QIF parser developer,
I want to create ContactData objects from parsed QIF memo/payee fields,
So that contact information is preserved separately from transaction data.

Acceptance Criteria:
- ContactData can be instantiated and populated with payee name, email, phone
- recordTransaction() method tracks transaction statistics
- toArray() exports all properties for database persistence
- Performance: Creating and populating ContactData < 0.5ms per transaction
```

#### Story 2: Bank Import Creates Contacts from Parsed Payees
```
As a bank import service,
I want to create Contact records in the database from ContactData objects,
So that duplicate contacts can be deduplicated and linked to FrontAccounting.

Acceptance Criteria:
- fromArray() can populate ContactData from database rows
- toArray() exports all properties with correct data types for database INSERT
- linkToFAEntity() updates fa_customer_id or fa_supplier_id
- Contacts can be matched by name to identify duplicates
```

#### Story 3: CRM Module Consumes Contact Data
```
As a CRM system,
I want to receive ContactData objects from the bank import module,
So that contact information can be displayed for customer/supplier creation.

Acceptance Criteria:
- ContactData includes all address components (line_1, line_2, city, state, postal, country)
- getFullAddress() returns reconstructed full address string
- getDisplayName() provides user-friendly name with fallback
- All CRM-relevant fields are accessible (company_name, contact_person, tax_id, etc.)
```

### Core Functionality

**DTO Properties** (40+ fields covering all contact dimensions):
- **Identity**: id, name, display_name, contact_type (vendor/customer/unknown)
- **Contact**: email, phone, phone_extension, fax, mobile, website
- **Address**: address_line_1/2, city, state_province, postal_code, country, country_code
- **Business**: company_name, department, contact_person, tax_id, registration_number
- **CRM**: fa_customer_id, fa_supplier_id (FrontAccounting linkage)
- **Metadata**: notes, tags, transaction_count, last_transaction_ts, total_amount
- **Timestamps**: created_ts, updated_ts

**Core Methods**:
1. `getFullAddress()` - Reconstruct full address from normalized components
2. `recordTransaction($amount, $timestamp)` - Update transaction statistics
3. `linkToFAEntity($entity_id, $entity_type)` - Link to FA customer/supplier
4. `fromArray($data)` - Populate from associative array (database row)
5. `toArray()` - Export all properties as array
6. `getDisplayName()` - Get display_name with fallback to name

### Non-Goals

- **No ORM Implementation**: ContactData is a DTO only; no save/load methods in the class
- **No Validation Logic**: Does not validate email format, phone format, tax ID format
- **No CRM Functionality**: Package does not create customers/suppliers in FrontAccounting; only stores linkage IDs
- **No Database Abstraction**: Does not provide query builders or result mapping; fromArray/toArray only
- **No Serialization**: Does not implement JSON serialization; consumers handle that
- **Multi-Language Support**: No i18n; all labels and documentation in English only

---

## Technical Specifications

### Architecture Overview

```
┌─────────────────────────────────────────┐
│  Ksfraser Contact DTO Package            │
│  (Zero Dependencies, PHP 7.3+)           │
├─────────────────────────────────────────┤
│                                          │
│  Ksfraser\Contact\DTO\ContactData        │
│  ├── 40+ Public Properties               │
│  ├── Constructor (timestamps)            │
│  ├── getFullAddress()                    │
│  ├── recordTransaction()                 │
│  ├── linkToFAEntity()                    │
│  ├── fromArray() / toArray()             │
│  └── getDisplayName()                    │
│                                          │
└─────────────────────────────────────────┘
         ▲              ▲              ▲
         │              │              │
    Used by:       Used by:       Used by:
    ├─ QIF Parser  ├─ Bank Import ├─ CRM Module
    ├─ OFX Parser  └─ Accounting  └─ Future systems
    └─ CSV Parser
```

### Integration Points

**Parsers (QIF, OFX, CSV)**:
- Depend on: `Ksfraser\Contact\DTO\ContactData`
- Usage: Instantiate, populate from parsed payee data, return ContactData object
- No dependency on bank_import; only on this package

**Bank Import System**:
- Depends on: `Ksfraser\Contact\DTO\ContactData`
- Composer: `"ksfraser/contact-dto": "*"`
- Path repository (local dev) → GitHub VCS URL (production)
- Usage: Receive ContactData from parsers, persist to `0_bi_contact` table

**Database Schema**:
- Table: `0_bi_contact`
- Columns: 1-to-1 mapping with ContactData properties
- FK from `0_bi_transactions.contact_id` to `0_bi_contact.id`

### Dependencies & Constraints

**Production Requirements**:
- PHP: ^7.3 (minimum)
- No external composer dependencies
- Works on legacy PHP environments

**Development & Testing**:
- PHP: ^8.0+ (testing environment)
- PHPUnit: ^11 (dev-only)
- Composer: Any recent version

**Compatibility**:
- PSR-4 autoloading only; no include paths
- No framework integration required
- Works standalone in any PHP project

### Security & Privacy

**Data Handling**:
- ContactData does not encrypt or obfuscate data; consumers are responsible for transport layer security (HTTPS, encryption-at-rest)
- Sensitive fields (tax_id, registration_number) are stored as plain text; assume database is secured

**CRM Linkage**:
- fa_customer_id and fa_supplier_id are references only; package does not validate that they exist in FrontAccounting
- Consumers must validate FA entity IDs before linking

**Compliance**:
- No PII encryption or special handling; assumes GDPR compliance is handled at application layer
- fromArray/toArray do not sanitize HTML or SQL; assume input sanitization at application layer

---

## Phased Rollout & Roadmap

### Phase 1: MVP (Current - March 2026)
✅ ContactData DTO class with 40+ properties  
✅ Core methods: getFullAddress, recordTransaction, linkToFAEntity, fromArray/toArray  
✅ Comprehensive test suite (25+ tests, 100% coverage)  
✅ PHPUnit 11 in dev-require only  
✅ PHP 7.3+ compatible  
✅ Published to Packagist  

### Phase 2: Parser Integration (Q2 2026)
⏳ Update ksfraser/qifparser to use ContactData  
⏳ Update ksfraser/ksf_ofxparser to use ContactData  
⏳ Create/enhance CSV parser to use ContactData  
⏳ Update ksf_bank_import to consume ContactData and populate 0_bi_contact  

### Phase 3: CRM Enhancement (Q3 2026)
⏳ Create FrontAccounting CRM module to offer contact creation UI  
⏳ Implement merchant→contact deduplication logic  
⏳ Add customer/supplier creation workflow triggered from ContactData  

### Phase 4: Future Extensions (Q4 2026+)
⏳ Optional JSON serialization support (toJson/fromJson methods)  
⏳ Optional validation methods (validateEmail, validatePhone, etc.)  
⏳ Optional address geocoding integration  
⏳ Multi-language support (properties for contact labels)  

---

## Risks & Mitigation

### Technical Risks

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|-----------|
| **PHP 7.3 Compatibility Break** | Breaks production systems that cannot upgrade | Low | Maintain property syntax compatible with PHP 7.3; test on PHP 7.3 in CI |
| **Packagist Registration Delay** | Blocks integration in consuming packages | Low | Pre-register account; have backup: use GitHub VCS URL in composer |
| **Parser Integration Complexity** | Parsers struggle to populate ContactData correctly | Medium | Provide detailed parser examples in docs; create parser template class |
| **Performance Degradation** | ContactData operations slow down bank imports | Low | Profile on large datasets; all operations designed to be <1ms |
| **Schema Evolution** | Need to add new contact fields breaks compatibility | Medium | Design schema to be extensible; use public properties for forward compatibility |

### Mitigation Actions

1. **Testing**: Run full test suite on PHP 7.3, 8.0, 8.4 in CI/CD
2. **Documentation**: Provide parser implementation guide with code examples
3. **Performance Baseline**: Benchmark ContactData operations; alert if degradation >10%
4. **Versioning**: Use Semantic Versioning; breaking changes → major version bump

---

## Success Metrics & KPIs

### Project Completion KPIs

1. ✅ **Code Quality**: 100% test coverage, 0 linting errors
2. ✅ **Documentation**: README + 3 supplementary docs (PRD, FRD, Test Plan)
3. ✅ **Compatibility**: Passes on PHP 7.3, 8.0, 8.4+
4. ✅ **Availability**: Published to Packagist and available on GitHub

### Integration KPIs (Post-MVP)

1. **Adoption**: ContactData used by 3+ independent systems (qifparser, ksf_ofxparser, bank_import)
2. **Performance**: contactData operations (getFullAddress, recordTransaction) < 1ms P99
3. **Test Coverage**: 25+ tests, 100% line coverage maintained
4. **Zero Breaking Changes**: Maintain backward compatibility across minor versions

---

## Conclusion

The Ksfraser Contact DTO provides a foundational, portable data structure for contact/payee normalization across bank import, CRM, and accounting systems. By eliminating the need for each system to implement its own contact model, it reduces development friction, improves data consistency, and enables seamless integration between independent modules.

With zero external dependencies and PHP 7.3+ compatibility, the package is immediately deployable in legacy production environments while remaining extensible for future enhancements.
