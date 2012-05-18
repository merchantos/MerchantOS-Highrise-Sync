<?php
/**
 * SyncAccount, a business object representing a subscriber to the MerchantOS-Highrise-Sync service
 * @author Erika Ellison
 * 
 */

require_once('SyncDateTime.class.php');
require_once('APIInterface.class.php');
require_once('XMLTransformations.class.php');
require_once('SyncAccountDAO.class.php');

class SyncAccount {
    protected $_mos_api_key;
    protected $_mos_acct_id;
    protected $_highrise_api_key;
    protected $_highrise_username;
    
    protected $_email_address;
    protected $_password;
    protected $_name;
    
    protected $_last_synced_on;
    protected $_id; // primary key in database table
    protected $_custom_field_id; // 
    
    protected $_api_interface;
    protected $_dao;
    
    
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
     * @param int $custom_field_id
     * @param int $id
     * @param string $last_synced_on
     */
    public function __construct($mos_api_key, $mos_acct_id, 
            $highrise_api_key, $highrise_username, 
            $email_address, $password, $name,
            $custom_field_id=NULL, $id=NULL, $last_synced_on=NULL) {
        
        $this->_mos_api_key = $mos_api_key;
        $this->_mos_acct_id = $mos_acct_id;
        $this->_highrise_api_key = $highrise_api_key;
        $this->_highrise_username = $highrise_username;
        
        $this->_email_address = $email_address;
        $this->_password = $password;
        $this->_name = $name;
        
        $this->_custom_field_id = $custom_field_id;
        $this->_id = $id;
        if ($last_synced_on) {
            $this->_last_synced_on = new SyncDateTime($last_synced_on);
        }
        
        $this->_api_interface = new APIInterface($mos_api_key, $mos_acct_id, $highrise_api_key, $highrise_username);
        $this->_dao = new SyncAccountDAO();
    }
    
    
    /** Syncs the account
     * @return boolean $was_synced
     */
    public function sync() {
        $was_synced = false;
        if ($this->hasValidCredentials()) {
            if (!(isset($this->_last_synced_on))) {
                $this->initialSync();
            }
            else {
                $this->incrementalSync();
            }
            $this->_last_synced_on = new SyncDateTime();
            $this->_dao->updateLastSyncedOn($this);
            $was_synced = true;
        }
        else {
            // deactivate the SyncAccount so it is not pulled to be synced again
            // until credentials have been updated??
            echo $this->_name . '\'s account does not have valid API credentials.<br />';
        }
        return $was_synced;
    }
    
    /** Saves the account
     * @return boolean $was_saved
     */
    public function save() {
        $was_saved = $this->_dao->saveSyncAccount($this);
        return $was_saved;
    }


    /**
     * @return boolean $both_valid
     */
    public function hasValidCredentials() {
        $mos = false;
        $highrise = false;
        try {
            $mos = $this->_api_interface->hasValidCredentialsMerchantOS();
            $highrise = $this->_api_interface->hasValidCredentialsHighrise();
        }
        catch (Exception $e) {
            $this->logException(new Exception('hasValidCredentials Error: ' . $e->getMessage()));
        }
        $both_valid = ($highrise && $mos);
        return $both_valid;
    }
    
    /** Copies all Customers in MerchantOS to Highrise, and all People in Highrise to MerchantOS
     */
    private function initialSync() {        
        // create a custom field in Highrise to track MerchantOS's customer id for each contact
        $custom_field = $this->_api_interface->defineCustomHighriseField(self::HIGHRISE_CUST_ID_FIELD_NAME);    
        $this->_custom_field_id = $custom_field->id;
        $this->_dao->updateCustomFieldID($this);
        
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
    }
    
    
    /** Syncs Customers and People that have been created or modified after $_last_synced_on.
     */
    private function incrementalSync() {
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
        foreach($people_since->person as $person) {
            $created_at = new SyncDateTime($person->{'created-at'});
            // so if person was created since last sync, create the customer
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
    private function createCustomerFromPerson($person) {
        $new_customer = XMLTransformations::personToCustomer($person);
        try {
            $customer = $this->_api_interface->createCustomer($new_customer);
            // put new MOS customer ID in Highrise custom field
            $highrise_person_id = $person->id;
            $mos_customer_id = $customer->customerID;
            $this->updatePersonWithCustomerID($highrise_person_id, $mos_customer_id);
        }
        catch (Exception $e) {
            $this->logException(new Exception ('createCsutomerFromPerson Error: ' . $e->getMessage()));
        }
        return $customer;
    }
    
    
    /**
     * @param SimpleXMLElement $person
     * @return SimpleXMLElement $customer
     */
    private function updateCustomerFromPerson($person) {
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
            $this->logException(new Exception('updateCustomerFromPerson Error: ' . $e->getMessage()));
        }
        return $customer;
    }
    
    /**
     * @param SimpleXMLElement $customer
     * @return SimpleXMLElement $person
     */
    private function createPersonFromCustomer($customer) {
        $new_person = XMLTransformations::customerToPerson($customer, $this->_custom_field_id);
        try {
            $person = $this->_api_interface->createPerson($new_person);
        }
        catch (Exception $e) {
            $this->logException(new Exception('createPersonFromCustomer Error: ' . $e->getMessage()));
        }
        return $person;
    }
    
    /**
     * @param SimpleXMLElement $customer
     * @return SimpleXMLElement $person
     */
    private function updatePersonFromCustomer($customer) {
        try {
            $person_id = $this->_api_interface->findPersonFromCustomerID(self::HIGHRISE_CUST_ID_FIELD_NAME, $customer->customerID);
            if (!$person_id) {
                // entering this branch means that the MerchantOS customer was created before last_synced_on
                // and modified since last_synced_on, but we can't locate a corresponding Highrise Person
                // so we should probably create the person instead
                $this->createPersonFromCustomer($customer);
            }
            else {
                $updated_person = XMLTransformations::customerToPerson($customer, $this->_custom_field_id);
                $person = $this->_api_interface->updatePerson($person_id, $updated_person);
            }
        }
        catch (Exception $e) {
            $this->logException(new Exception('updatePersonFromCustomer Error: ' . $e->getMessage()));
        }
        return $person;
    }
    
    
    /**
     * @param SimpleXMLElement $person
     * @param int $mos_customer_id
     * @return SimpleXMLElement $updated_person
     */
    private function updatePersonWithCustomerID($highrise_person_id, $mos_customer_id) {
       $update_xml = new SimpleXMLElement('<person><subject_datas type="array"><subject_data><subject_field_id type="integer">' . 
                $this->_custom_field_id . '</subject_field_id><value>' . $mos_customer_id . 
                '</value></subject_data></subject_datas></person>');
        try {
            $updated_person = $this->_api_interface->updatePerson($highrise_person_id, $update_xml);
        }
        catch (Exception $e) {
            $this->logException(new Exception('updatePersonWithCustomerID Error: ' . $e->getMessage()));
        }
        return $updated_person;
    }
        
    /**
     * @param Exception $e 
     * @param  $data_involved
     */
    public function logException($e, $data_involved=NULL) {
        // write date, SyncAccount id, and exception message to a log   
        $now = new SyncDateTime();
        $acct_id = $this->_id;
        $exception_message = 'SyncAccount::' . $e->getMessage();

        $string = $now . ' ACCT_ID: ' . $acct_id . ' DESCRIPTION: ' . $exception_message . '\n\n';
        $filename = 'error_log.txt';
        $f = fopen($filename, "a");
        fwrite($f, $string);
        fclose($f);
    }
    
    
    
    /* a bunch of getter methods */
    
    public function getMOSAPIKey() {
        return $this->_mos_api_key;
    }
    
    public function getMOSAccountID() {
        return $this->_mos_acct_id;
    }
    
    public function getHighriseAPIKey() {
        return $this->_highrise_api_key;
    }
    
    public function getHighriseUsername() {
        return $this->_highrise_username;
    }
    
    public function getEmailAddress() {
        return $this->_email_address;
    }
    
    public function getPassword() {
        return $this->_password;
    }
    
    public function getName() {
        return $this->_name;
    }
    
    public function getLastSyncedOn() {
        $last_synced_on = NULL;
        if ($this->_last_synced_on) {
            $last_synced_on = $this->_last_synced_on->getDatabaseFormat();
        }
        return $last_synced_on;
    }
    
    public function getID() {
        return $this->_id;
    }
    
    public function getCustomFieldID() {
        return $this->_custom_field_id;
    }
    
    
     /** returns a string describing the values of each field
     * @return string $s
     */
    public function toString() {
        $last_synced_on = 'NULL';
        if ($this->_last_synced_on) {
            $last_synced_on = $this->_last_synced_on->getDatabaseFormat();
        }
        
        $s = '<pre>SyncAccount ID: ' . $this->_id . 
                '<br />  email_address: ' . $this->_email_address .
                '<br />  password: ' . $this->_password . 
                '<br />  name: ' . $this->_name . 
                '<br />  mos_api_key: ' . $this->_mos_api_key . 
                '<br />  mos_acct_id: ' . $this->_mos_acct_id . 
                '<br />  highrise_api_key: ' . $this->_highrise_api_key . 
                '<br />  highrise_username: ' . $this->_highrise_username . 
                '<br />  last_synced_on: ' . $last_synced_on . 
                '</pre>';
        return $s;
    }

}
?>
