<?php
namespace Ksfraser\Contact\DTO;

/**
 * ContactData DTO - Shared data transfer object for contact/payee information
 * 
 * Used across bank import parsers (QIF, OFX, CSV) to normalize and transport
 * contact information extracted from bank statements.
 * 
 * This is a standalone, dependency-free DTO designed to be shared across
 * multiple packages and services (bank import, CRM, accounting modules).
 * 
 * @package Ksfraser\Contact\DTO
 */
class ContactData
{
    /**
     * Unique identifier (database ID)
     * @var int|string
     */
    public $id = '';
    
    /**
     * Unique contact name/identifier
     * @var string
     */
    public $name = '';
    
    /**
     * Display-friendly name (may differ from $name)
     * @var string
     */
    public $display_name = '';
    
    /**
     * Contact type classification
     * @var string One of: 'vendor', 'customer', 'unknown'
     */
    public $contact_type = 'unknown';
    
    /**
     * Active/inactive flag
     * @var bool|int
     */
    public $is_active = 1;
    
    // ========== CONTACT DETAILS ==========
    
    /**
     * Email address
     * @var string
     */
    public $email = '';
    
    /**
     * Primary phone number
     * @var string
     */
    public $phone = '';
    
    /**
     * Phone extension
     * @var string
     */
    public $phone_extension = '';
    
    /**
     * Fax number
     * @var string
     */
    public $fax = '';
    
    /**
     * Mobile/cell phone number
     * @var string
     */
    public $mobile = '';
    
    /**
     * Website URL
     * @var string
     */
    public $website = '';
    
    // ========== ADDRESS - NORMALIZED FIELDS ==========
    
    /**
     * First line of street address
     * @var string
     */
    public $address_line_1 = '';
    
    /**
     * Second line of street address (suite, apt, etc)
     * @var string
     */
    public $address_line_2 = '';
    
    /**
     * City/municipality
     * @var string
     */
    public $city = '';
    
    /**
     * State/province/region
     * @var string
     */
    public $state_province = '';
    
    /**
     * Postal/ZIP code
     * @var string
     */
    public $postal_code = '';
    
    /**
     * Country name (full)
     * @var string
     */
    public $country = '';
    
    /**
     * Country code (ISO 3166-1 alpha-2, e.g., 'US', 'CA', 'UK')
     * @var string
     */
    public $country_code = '';
    
    // ========== BUSINESS DETAILS ==========
    
    /**
     * Company/organization name
     * @var string
     */
    public $company_name = '';
    
    /**
     * Department within company
     * @var string
     */
    public $department = '';
    
    /**
     * Contact person name (primary representative)
     * @var string
     */
    public $contact_person = '';
    
    /**
     * Tax identification number (VAT ID, SSN, EIN, etc.)
     * @var string
     */
    public $tax_id = '';
    
    /**
     * Business registration number
     * @var string
     */
    public $registration_number = '';
    
    // ========== CRM LINKAGE - EXTERNAL SYSTEM REFERENCES ==========
    
    /**
     * Link to FrontAccounting customer master record
     * @var string FA customer ID
     */
    public $fa_customer_id = '';
    
    /**
     * Link to FrontAccounting supplier/vendor master record
     * @var string FA supplier ID
     */
    public $fa_supplier_id = '';
    
    // ========== METADATA & STATISTICS ==========
    
    /**
     * Free-form notes about this contact
     * @var string
     */
    public $notes = '';
    
    /**
     * Comma-separated tags for categorization
     * @var string
     */
    public $tags = '';
    
    /**
     * Transaction count - number of transactions from this contact
     * @var int
     */
    public $transaction_count = 0;
    
    /**
     * Timestamp of most recent transaction
     * @var string ISO 8601 datetime or empty
     */
    public $last_transaction_ts = '';
    
    /**
     * Total transaction amount (sum of all related transactions)
     * @var float|int
     */
    public $total_amount = 0;
    
    // ========== TIMESTAMPS ==========
    
    /**
     * Record creation timestamp
     * @var string ISO 8601 datetime
     */
    public $created_ts = '';
    
    /**
     * Record last update timestamp
     * @var string ISO 8601 datetime
     */
    public $updated_ts = '';
    
    
    /**
     * Constructor - initializes timestamps
     */
    public function __construct()
    {
        $this->created_ts = date('Y-m-d H:i:s');
        $this->updated_ts = $this->created_ts;
    }
    
    /**
     * Build full address string from normalized fields
     * 
     * @return string Full address with components joined by comma-space
     */
    public function getFullAddress()
    {
        $parts = [];
        if ($this->address_line_1) $parts[] = $this->address_line_1;
        if ($this->address_line_2) $parts[] = $this->address_line_2;
        if ($this->city) $parts[] = $this->city;
        if ($this->state_province) $parts[] = $this->state_province;
        if ($this->postal_code) $parts[] = $this->postal_code;
        if ($this->country) $parts[] = $this->country;
        
        return implode(', ', $parts);
    }
    
    /**
     * Update transaction statistics
     * 
     * Used when a new transaction from this contact is processed.
     * Increments count, updates last timestamp, and accumulates amount.
     * 
     * @param float|int $amount Transaction amount
     * @param string $timestamp ISO 8601 datetime of transaction (optional)
     */
    public function recordTransaction($amount, $timestamp = '')
    {
        $this->transaction_count++;
        $this->total_amount += $amount;
        if ($timestamp) {
            $this->last_transaction_ts = $timestamp;
        } else {
            $this->last_transaction_ts = date('Y-m-d H:i:s');
        }
        $this->updated_ts = date('Y-m-d H:i:s');
    }
    
    /**
     * Link this contact to a FrontAccounting entity
     * 
     * @param string $entity_id FA customer or supplier ID
     * @param string $entity_type 'customer' or 'supplier'
     */
    public function linkToFAEntity($entity_id, $entity_type = 'supplier')
    {
        if ($entity_type === 'customer') {
            $this->fa_customer_id = $entity_id;
            $this->contact_type = 'customer';
        } elseif ($entity_type === 'supplier') {
            $this->fa_supplier_id = $entity_id;
            $this->contact_type = 'vendor';
        }
        $this->updated_ts = date('Y-m-d H:i:s');
    }
    
    /**
     * Populate from associative array (e.g., database row)
     * 
     * @param array $data Key-value pairs matching property names
     */
    public function fromArray($data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
    
    /**
     * Export to associative array
     * 
     * @return array All properties as key-value pairs
     */
    public function toArray()
    {
        return get_object_vars($this);
    }
    
    /**
     * Get display name with fallback to name
     * 
     * @return string Display name, or name if display_name is empty
     */
    public function getDisplayName()
    {
        return $this->display_name ?: $this->name;
    }
}
