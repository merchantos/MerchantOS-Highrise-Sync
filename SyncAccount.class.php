<?php
/**
 * SyncAccount
 *
 * @author Erika Ellison
 * 
 * RE: exceptions
 * All exceptions thrown from write calls are caught individually.
 */

require_once('SyncDateTime.class.php');
require_once('APIInterface.class.php');
require_once('XMLTransformations.class.php');

class SyncAccount {
    protected $_email_address;
    protected $_password;
    protected $_name;
    protected $_mos_api_key;
    protected $_mos_acct_id;
    protected $_highrise_api_key;
    protected $_highrise_username;
    protected $_last_synced_on;  // type SyncDateTime
    protected $_id; // primary key in database table
    
    protected $_custom_field_id;    
    
    protected $_api_interface;
    
    // a custom-defined foreign key field in Highrise for MerchantOS customerID
    // changing the value of this constant will break the application for any existing users
    // unless (their Highrise accounts) or (their database records and this class) are updated accordingly
    const HIGHRISE_CUST_ID_FIELD_NAME = 'merchantos_customerid';
    
    /**
     * @param string $mos_api_key
     * @param string $mos_acct_id
     * 
     * @param string $highrise_api_key
     * @param string $highrise_username
     * 
     * @param string $email_address
     * @param string $password
     * @param string $name
     * 
     * @param string/int
     * @param string/datetime $last_synced_on
     * @param string/int $id 
     */
    public function __construct($mos_api_key, $mos_acct_id, 
            $highrise_api_key, $highrise_username, 
            $email_address, $password, $name,
            $custom_field_id=NULL, $id=NULL, $last_synced_on=NULL) {
        
        $this->_email_address = $email_address;
        $this->_password = $password;
        $this->_name = $name;
        $this->_mos_api_key = $mos_api_key;
        $this->_mos_acct_id = $mos_acct_id;
        $this->_highrise_api_key = $highrise_api_key;
        $this->_highrise_username = $highrise_username;
        if ($last_synced_on) {
            $this->_last_synced_on = new SyncDateTime($last_synced_on);
        }
        $this->_id = $id;
        
        
        $this->_custom_field_id = $custom_field_id;
        
        $this->_api_interface = new APIInterface($mos_api_key, $mos_acct_id, $highrise_api_key, $highrise_username);
    }
    
    
    /** Syncs the account and returns the new value for last_synced_on
     * @return string $last_synced_on a datetime in database format
     * 
     */
    public function sync() {
        try {
            if (!(isset($this->_last_synced_on))) {
                $this->initialSync();
            }
            else {
                $this->incrementalSync();
            }
            $this->_last_synced_on = new SyncDateTime();
            $last_synced_on =  $this->_last_synced_on->getDatabaseFormat();
            return $last_synced_on;
        }
        catch (Exception $e) {
            // should probably only be exceptions for which the sync attempt should be aborted
            // like credentials not being good or a read all call failing
            // individual write failures should be handled and reported
            
        }
    }

    /** Copies all Customers in MerchantOS to Highrise, and all People in Highrise to MerchantOS.
     *  Does not check for duplicates.
     */
    public function initialSync() {        
        // create a custom field in Highrise to track MerchantOS's customer id for each contact
        $custom_field = $this->_api_interface->defineCustomHighriseField(self::HIGHRISE_CUST_ID_FIELD_NAME);    
        $this->_custom_field_id = $custom_field->id;
        
        // get all existing contacts from each service
        $customers = $this->_api_interface->readAllCustomers();
        $people = $this->_api_interface->readAllPeople();
        // turn each customer into a new person
        foreach($customers->Customer as $customer) {
            $customer_xml = new SimpleXMLElement($customer->asXML());
            $this->createPersonFromCustomer($customer_xml);
        }
        // turn each person into a new customer
        foreach($people->person as $person) {
            $person_xml = new SimpleXMLElement($person->asXML());
            $this->createCustomerFromPerson($person_xml);
        }
        
        // do something about any problems that were encountered?
    }
    
    
    /** Syncs Customers and People that have been created or modified after $_last_synced_on.
     */
    public function incrementalSync() {
        $customers_created_since = $this->_api_interface->readCustomersCreatedSince($this->_last_synced_on->getMerchantOSFormat());
        $customers_modified_since = $this->_api_interface->readCustomersModifiedSince($this->_last_synced_on->getMerchantOSFormat());
        $people_since = $this->_api_interface->readPeopleSince($this->_last_synced_on->getHighriseFormat());
        foreach($customers_created_since->Customer as $customer) {
            $this->createPersonFromCustomer($customer);
        }
        foreach($customers_modified_since->Customer as $customer) {
            $this->updatePersonFromCustomer($customer);
        }
        // Highrise only supports searching by combined created/updated since
        // so if the person has been created since last_synced_on, create Customer
        // else, update the customer
        foreach($people_since->person as $person) {
            $created_at = new SyncDateTime($person->{'created-at'});
            // if person was created since last sync, create the customer
            if ($created_at->getInt() > $this->_last_synced_on->getInt()) {
                $this->createCustomerFromPerson($person);
            }
            // otherwise, update the customer
            else {
                $this->updateCustomerFromPerson($person);
            }
        } 
    }
    

    /** 
     * @param SimpleXMLElement $person
     * @return SimpleXMLElement $customer
     */
    public function createCustomerFromPerson($person) {
        $new_customer = XMLTransformations::personToCustomer($person);
        try {
            $customer = $this->_api_interface->createCustomer($new_customer);
            // put new MOS customer ID in Highrise custom field
            $highrise_person_id = $person->id;
            $mos_customer_id = $customer->customerID;
            $this->updatePersonWithCustomerID($highrise_person_id, $mos_customer_id);
        }
        catch (Exception $e) {
            $this->writeExceptionToLog($e);
        }
        return $customer;
    }
    
    
    /**
     * @param SimpleXMLElement $person
     * @return SimpleXMLElement $customer
     */
    public function updateCustomerFromPerson($person) {
        $updated_customer = XMLTransformations::personToCustomer($person);
        foreach($person->subject_datas->subject_data as $subject_data) {
            if ($subject_data->subject_field_label == self::HIGHRISE_CUST_ID_FIELD_NAME) {
                $customer_id = $subject_data->value;
                break;
            }
        }
        try {
            $customer = $this->_api_interface->updateCustomer($customer_id, $updated_customer);
        }
        catch (Exception $e) {
            $this->writeExceptionToLog($e);
        }
        return $customer;
    }
    
    /**
     * @param SimpleXMLElement $customer
     * @return SimpleXMLElement $person
     */
    public function createPersonFromCustomer($customer) {
        $new_person = XMLTransformations::customerToPerson($customer);
        try {
            $person = $this->_api_interface->createPerson($new_person);
        }
        catch (Exception $e) {
            $this->writeExceptionToLog($e);
        }
        return $person;
    }
    
    /**
     * @param SimpleXMLElement $customer
     * @return SimpleXMLElement $person
     */
    public function updatePersonFromCustomer($customer) {
        $updated_person = XMLTransformations::customerToPerson($customer);
        try {
            $person_id = $this->_api_interface->findPersonFromCustomerID(self::HIGHRISE_CUST_ID_FIELD_NAME, $customer->customerID);
            $person = $this->_api_interface->updatePerson($person_id, $updated_person);
        }
        catch (Exception $e) {
            $this->writeExceptionToLog($e);
        }
        return $person;
    }
    
    
    /**
     * @param SimpleXMLElement $person
     * @param int $mos_customer_id
     * @return SimpleXMLElement $updated_person
     */
    public function updatePersonWithCustomerID($highrise_person_id, $mos_customer_id) {
       $update_xml = new SimpleXMLElement('<person><subject_datas type="array"><subject_data><subject_field_id type="integer">' . 
                $this->_custom_field_id . '</subject_field_id><value>' . $mos_customer_id . 
                '</value></subject_data></subject_datas></person>');
        try {
            $updated_person = $this->_api_interface->updatePerson($highrise_person_id, $update_xml);
        }
        catch (Exception $e) {
            $this->writeExceptionToLog($e);
        }
        return $updated_person;
    }
    
    
    
    public function writeExceptionToLog($e) {
        // write SyncAccount idenitifying details and exception message to a log
        // depending on the type of exception, possibly notify the subscriber to the sync service?
        echo 'EXCEPTION: ' . $e->getMessage();
    }
    
     /** returns a string describing the values of each field
     * @return string $s
     */
    public function toString() {
        $last_synced_on = 'NULL';
        if ($this->_last_synced_on) {
            $last_synced_on = $this->_last_synced_on->getDatabaseFormat();
        }
        
        $s = 'SyncAccount {' . 
                'ID: ' . $this->_id . 
                ', email_address: ' . $this->_email_address .
                ', password: ' . $this->_password . 
                ', name: ' . $this->_name . 
                ', mos_api_key: ' . $this->_mos_api_key . 
                ', mos_acct_id: ' . $this->_mos_acct_id . 
                ', highrise_api_key: ' . $this->_highrise_api_key . 
                ', highrise_username: ' . $this->_highrise_username . 
                ', last_synced_on: ' . $last_synced_on . 
                '}';
        return $s;
    }
  
}
?>
