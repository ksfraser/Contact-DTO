<?php
namespace Ksfraser\Contact\Tests;

use Ksfraser\Contact\DTO\ContactData;
use PHPUnit\Framework\TestCase;

class ContactDataTest extends TestCase
{
    private ContactData $contact;

    protected function setUp(): void
    {
        $this->contact = new ContactData();
    }

    /**
     * Test ContactData instantiation
     */
    public function testContactDataCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ContactData::class, $this->contact);
    }

    /**
     * Test default values are set on construction
     */
    public function testDefaultValuesOnConstruction(): void
    {
        $this->assertNotEmpty($this->contact->created_ts);
        $this->assertNotEmpty($this->contact->updated_ts);
        $this->assertEquals('unknown', $this->contact->contact_type);
        $this->assertEquals(1, $this->contact->is_active);
        $this->assertEquals(0, $this->contact->transaction_count);
        $this->assertEquals(0, $this->contact->total_amount);
    }

    /**
     * Test setting and reading properties
     */
    public function testPropertyAssignment(): void
    {
        $this->contact->name = 'ACME Corp';
        $this->contact->email = 'info@acme.com';
        $this->contact->phone = '555-0123';
        $this->contact->contact_type = 'vendor';

        $this->assertEquals('ACME Corp', $this->contact->name);
        $this->assertEquals('info@acme.com', $this->contact->email);
        $this->assertEquals('555-0123', $this->contact->phone);
        $this->assertEquals('vendor', $this->contact->contact_type);
    }

    /**
     * Test address normalization
     */
    public function testAddressNormalization(): void
    {
        $this->contact->address_line_1 = '123 Main St';
        $this->contact->address_line_2 = 'Suite 100';
        $this->contact->city = 'New York';
        $this->contact->state_province = 'NY';
        $this->contact->postal_code = '10001';
        $this->contact->country = 'United States';

        $fullAddress = $this->contact->getFullAddress();
        $this->assertStringContainsString('123 Main St', $fullAddress);
        $this->assertStringContainsString('Suite 100', $fullAddress);
        $this->assertStringContainsString('New York', $fullAddress);
        $this->assertStringContainsString('NY', $fullAddress);
        $this->assertStringContainsString('10001', $fullAddress);
        $this->assertStringContainsString('United States', $fullAddress);
    }

    /**
     * Test getFullAddress with partial address
     */
    public function testGetFullAddressWithPartialData(): void
    {
        $this->contact->address_line_1 = '123 Main St';
        $this->contact->city = 'New York';

        $fullAddress = $this->contact->getFullAddress();
        $this->assertStringContainsString('123 Main St', $fullAddress);
        $this->assertStringContainsString('New York', $fullAddress);
    }

    /**
     * Test recordTransaction increments count and updates timestamp
     */
    public function testRecordTransactionIncrementsCount(): void
    {
        $initialCount = $this->contact->transaction_count;
        $this->contact->recordTransaction(100.00);

        $this->assertEquals($initialCount + 1, $this->contact->transaction_count);
        $this->assertNotEmpty($this->contact->last_transaction_ts);
    }

    /**
     * Test recordTransaction accumulates amount
     */
    public function testRecordTransactionAccumulatesAmount(): void
    {
        $this->contact->recordTransaction(100.00);
        $this->contact->recordTransaction(50.00);
        $this->contact->recordTransaction(25.00);

        $this->assertEquals(175.00, $this->contact->total_amount);
        $this->assertEquals(3, $this->contact->transaction_count);
    }

    /**
     * Test recordTransaction with custom timestamp
     */
    public function testRecordTransactionWithCustomTimestamp(): void
    {
        $customTimestamp = '2023-06-15 10:30:00';
        $this->contact->recordTransaction(100.00, $customTimestamp);

        $this->assertEquals($customTimestamp, $this->contact->last_transaction_ts);
    }

    /**
     * Test linkToFAEntity for supplier
     */
    public function testLinkToFAEntityForSupplier(): void
    {
        $supplierId = 'SUP001';
        $this->contact->linkToFAEntity($supplierId, 'supplier');

        $this->assertEquals($supplierId, $this->contact->fa_supplier_id);
        $this->assertEquals('vendor', $this->contact->contact_type);
        $this->assertEmpty($this->contact->fa_customer_id);
    }

    /**
     * Test linkToFAEntity for customer
     */
    public function testLinkToFAEntityForCustomer(): void
    {
        $customerId = 'CUST001';
        $this->contact->linkToFAEntity($customerId, 'customer');

        $this->assertEquals($customerId, $this->contact->fa_customer_id);
        $this->assertEquals('customer', $this->contact->contact_type);
        $this->assertEmpty($this->contact->fa_supplier_id);
    }

    /**
     * Test fromArray populates properties from array
     */
    public function testFromArrayPopulatesProperties(): void
    {
        $data = [
            'name' => 'Test Company',
            'email' => 'test@example.com',
            'city' => 'Boston',
            'contact_type' => 'vendor',
            'transaction_count' => 5,
            'total_amount' => 500.00,
        ];

        $this->contact->fromArray($data);

        $this->assertEquals('Test Company', $this->contact->name);
        $this->assertEquals('test@example.com', $this->contact->email);
        $this->assertEquals('Boston', $this->contact->city);
        $this->assertEquals('vendor', $this->contact->contact_type);
        $this->assertEquals(5, $this->contact->transaction_count);
        $this->assertEquals(500.00, $this->contact->total_amount);
    }

    /**
     * Test fromArray ignores non-existent properties
     */
    public function testFromArrayIgnoresNonExistentProperties(): void
    {
        $data = [
            'name' => 'Test Company',
            'non_existent_field' => 'should be ignored',
        ];

        $this->contact->fromArray($data);

        $this->assertEquals('Test Company', $this->contact->name);
        $this->assertFalse(property_exists($this->contact, 'non_existent_field'));
    }

    /**
     * Test toArray exports all properties
     */
    public function testToArrayExportsAllProperties(): void
    {
        $this->contact->name = 'Test Company';
        $this->contact->email = 'test@example.com';
        $this->contact->city = 'Boston';

        $array = $this->contact->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Test Company', $array['name']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertEquals('Boston', $array['city']);
    }

    /**
     * Test getDisplayName returns display_name when set
     */
    public function testGetDisplayNameReturnsDisplayName(): void
    {
        $this->contact->name = 'Internal Name';
        $this->contact->display_name = 'Public Display Name';

        $this->assertEquals('Public Display Name', $this->contact->getDisplayName());
    }

    /**
     * Test getDisplayName falls back to name
     */
    public function testGetDisplayNameFallsBackToName(): void
    {
        $this->contact->name = 'Only Name Set';
        $this->contact->display_name = '';

        $this->assertEquals('Only Name Set', $this->contact->getDisplayName());
    }

    /**
     * Test business details can be set
     */
    public function testBusinessDetails(): void
    {
        $this->contact->company_name = 'ACME Corporation';
        $this->contact->department = 'Billing';
        $this->contact->contact_person = 'John Doe';
        $this->contact->tax_id = '12-3456789';
        $this->contact->registration_number = 'REG123456';

        $this->assertEquals('ACME Corporation', $this->contact->company_name);
        $this->assertEquals('Billing', $this->contact->department);
        $this->assertEquals('John Doe', $this->contact->contact_person);
        $this->assertEquals('12-3456789', $this->contact->tax_id);
        $this->assertEquals('REG123456', $this->contact->registration_number);
    }

    /**
     * Test notes and tags
     */
    public function testNotesAndTags(): void
    {
        $this->contact->notes = 'Important customer - handle with care';
        $this->contact->tags = 'vip,preferred,active';

        $this->assertEquals('Important customer - handle with care', $this->contact->notes);
        $this->assertEquals('vip,preferred,active', $this->contact->tags);
    }

    /**
     * Test all contact detail fields
     */
    public function testAllContactDetailFields(): void
    {
        $this->contact->phone = '555-0123';
        $this->contact->phone_extension = '123';
        $this->contact->fax = '555-0124';
        $this->contact->mobile = '555-0125';
        $this->contact->website = 'https://example.com';

        $this->assertEquals('555-0123', $this->contact->phone);
        $this->assertEquals('123', $this->contact->phone_extension);
        $this->assertEquals('555-0124', $this->contact->fax);
        $this->assertEquals('555-0125', $this->contact->mobile);
        $this->assertEquals('https://example.com', $this->contact->website);
    }

    /**
     * Test roundtrip: fromArray -> toArray
     */
    public function testRoundtripFromArrayToArray(): void
    {
        $originalData = [
            'name' => 'Complete Company',
            'display_name' => 'Complete Company Inc',
            'email' => 'contact@complete.com',
            'phone' => '555-1234',
            'address_line_1' => '123 Business Ave',
            'city' => 'New York',
            'state_province' => 'NY',
            'contact_type' => 'vendor',
        ];

        $this->contact->fromArray($originalData);
        $exportedData = $this->contact->toArray();

        $this->assertEquals('Complete Company', $exportedData['name']);
        $this->assertEquals('Complete Company Inc', $exportedData['display_name']);
        $this->assertEquals('contact@complete.com', $exportedData['email']);
        $this->assertEquals('555-1234', $exportedData['phone']);
        $this->assertEquals('123 Business Ave', $exportedData['address_line_1']);
        $this->assertEquals('New York', $exportedData['city']);
        $this->assertEquals('NY', $exportedData['state_province']);
        $this->assertEquals('vendor', $exportedData['contact_type']);
    }

    /**
     * Test country code field
     */
    public function testCountryCode(): void
    {
        $this->contact->country = 'United States';
        $this->contact->country_code = 'US';

        $this->assertEquals('United States', $this->contact->country);
        $this->assertEquals('US', $this->contact->country_code);
    }

    /**
     * Test is_active flag
     */
    public function testIsActiveFlag(): void
    {
        $this->assertEquals(1, $this->contact->is_active);
        $this->contact->is_active = 0;
        $this->assertEquals(0, $this->contact->is_active);
    }

    /**
     * Test id field
     */
    public function testIdField(): void
    {
        $this->contact->id = 42;
        $this->assertEquals(42, $this->contact->id);

        $this->contact->id = 'uuid-1234-5678';
        $this->assertEquals('uuid-1234-5678', $this->contact->id);
    }
}
