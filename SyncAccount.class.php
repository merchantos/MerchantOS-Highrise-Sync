<?php
/**
 * SyncAccount represents a subscriber to the MerchantOS-Highrise-Sync service
 * @author Erika Ellison
 */

require_once('SyncDateTime.class.php');
require_once('APIInterface.class.php');
require_once('XMLTransformations.class.php');
require_once('SyncAccountDAO.class.php');

/**
 * SyncAccount class
 * @author Erika Ellison 
 */
class SyncAccount {
    /**
     * Unique id, primary key in database
     * @var int 
     */
    protected $_id;
    
    /**
     * MerchantOS account key
     * @var string
     */
    protected $_mos_acct_key;
    
    /**
     * MerchantOS API key
     * @var string
     */
    protected $_mos_api_key;
    
    /**
     * MerchantOS account id
     * @var int
     */
    protected $_mos_acct_id;
    
    /**
     * Highrise API key
     * @var string
     */
    protected $_highrise_api_key;
    
    /**
     * Highrise username
     * @var string
     */
    protected $_highrise_username;

    /**
     * The id number assigned to the custom field used in Highrise to store the MerchantOS customer id of a synced contact
     * @var int
     */
    protected $_custom_field_id;
    
    /**
     * The datetime the account was last synced
     * @var SyncDateTime
     */
    protected $_last_synced_on;
    
    
    /**
     * The name of the 'foreign key' field that this application defines and then uses to sync updates to contact data.
     * Changing the value of this constant will break the application for any existing users
     * unless (their Highrise accounts) or (their database records, this class, and the DAO class) have been appropriately refactored and updated
     * @const HIGHRISE_CUST_ID_FIELD_NAME 
     */
    const HIGHRISE_CUST_ID_FIELD_NAME = 'MerchantOS_CustomerID_DoNotModify';
    
    
    /**
     * Creates a new SyncAccount. If not being instantiated from a database record, $id should be set to null.
     * @param int $id
     * @param string $mos_acct_key
     * @param string $mos_api_key
     * @param int $mos_acct_id
     * @param string $highrise_api_key
     * @param string $highrise_username
     * @param int $custom_field_id
     * @param string $last_synced_on
     */
    public function __construct($id, $mos_acct_key, 
            $mos_api_key, $mos_acct_id, 
            $highrise_api_key, $highrise_username,
            $custom_field_id=NULL, $last_synced_on=NULL) {
        
        $this->_id = $id;
        $this->_mos_acct_key = $mos_acct_key;
        
        $this->_mos_api_key = $mos_api_key;
        $this->_mos_acct_id = $mos_acct_id;
        
        $this->_highrise_api_key = $highrise_api_key;
        $this->_highrise_username = $highrise_username;
        
        $this->_custom_field_id = $custom_field_id;

        if ($last_synced_on) {
            $this->_last_synced_on = new SyncDateTime($last_synced_on);
        }
    }
    
    
    /** 
     * Syncs the contact data in MerchantOS and Highrise
     * @return boolean $was_synced
     */
    public function sync() {
        $was_synced = false;
        if ($this->hasValidCredentialsMerchantOS() && $this->hasValidCredentialsHighrise()) {
            if (!(isset($this->_last_synced_on))) {
                $this->initialSync();
            }
            else {
                $this->incrementalSync();
            }
            $this->_last_synced_on = new SyncDateTime();
            $dao = new SyncAccountDAO();
            $dao->updateLastSyncedOn($this);
            $was_synced = true;
        }
        else {
            // this may be an appropriate place to add the feature
            // of auto-notification of bad account credentials
        }
        return $was_synced;
    }
    
    /**
     * Saves the account to the database
     * @return boolean $was_saved
     */
    public function save() {
        $dao = new SyncAccountDAO();
        $was_saved = $dao->saveSyncAccount($this);
        return $was_saved;
    }

    /**
     * Checks for valid MerchantOS API credentials
     * @return boolean $valid 
     */
    public function hasValidCredentialsMerchantOS() {
        $valid = false;
        try {
            $api_interface = new APIInterface($this->_mos_api_key, $this->_mos_acct_id, $this->_highrise_api_key, $this->_highrise_username);
            $valid = $api_interface->hasValidCredentialsMerchantOS();
        }
        catch (Exception $e) {
            $this->logException(new Exception('hasValidCredentialsMerchantOS Error: ' . $e->getMessage()));
        }
        return $valid;
    }

    /**
     * Checks for valid Highrise API credentials
     * @return boolean $valid 
     */
    public function hasValidCredentialsHighrise() {
        $valid = false;
        try {
            $api_interface = new APIInterface($this->_mos_api_key, $this->_mos_acct_id, $this->_highrise_api_key, $this->_highrise_username);
            $valid = $api_interface->hasValidCredentialsHighrise();
        }
        catch (Exception $e) {
            $this->logException(new Exception('hasValidCredentialsHighrise Error: ' . $e->getMessage()));
        }
        return $valid;
    }
    
    /** 
     * Copies all Customers in MerchantOS to Highrise, and all People in Highrise to MerchantOS
     */
    protected function initialSync() {        
        $api_interface = new APIInterface($this->_mos_api_key, $this->_mos_acct_id, $this->_highrise_api_key, $this->_highrise_username);
        // create a custom field in Highrise to track MerchantOS's customer id for each contact
        $custom_field = $api_interface->defineCustomHighriseField(self::HIGHRISE_CUST_ID_FIELD_NAME);    
        $this->_custom_field_id = $custom_field->id;
        
        $dao = new SyncAccountDAO();
        $dao->updateCustomFieldID($this);
        
        // get all existing contacts from each service
        $customers = $api_interface->readAllCustomers();
        $people = $api_interface->readAllPeople();
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
    
    
    /** 
     * Syncs Customers and People that have been created or modified after $_last_synced_on.
     * For contacts that were created before the account was last synced, and have been modified since,
     * attempts to find and overwrite the corresponding contact in the other application.
     * If a corresponding contact is not found, creates a new one.
     */
    protected function incrementalSync() {
        $api_interface = new APIInterface($this->_mos_api_key, $this->_mos_acct_id, $this->_highrise_api_key, $this->_highrise_username);
        $customers_created_since = $api_interface->readCustomersCreatedSince($this->_last_synced_on->getMerchantOSFormat());
        $customers_modified_since = $api_interface->readCustomersModifiedSince($this->_last_synced_on->getMerchantOSFormat());
        $people_since = $api_interface->readPeopleSince($this->_last_synced_on->getHighriseFormat());
        foreach($customers_created_since->Customer as $customer) {
            $customer_xml = new SimpleXMLElement($customer->asXML());
            $this->createPersonFromCustomer($customer_xml);
        }
        foreach($customers_modified_since->Customer as $customer) {
            $customer_xml = new SimpleXMLElement($customer->asXML());
            $this->updatePersonFromCustomer($customer_xml);
        }
        // Highrise only supports searching by combined created/updated since
        foreach($people_since->person as $person) {
            $person_xml = new SimpleXMLElement($person->asXML());
            $created_at = new SyncDateTime($person->{'created-at'});
            // so if person was created since last sync, create the customer, otherwise update
            if ($created_at->getInt() > $this->_last_synced_on->getInt()) {
                $this->createCustomerFromPerson($person_xml);
            }
            else {
                $this->updateCustomerFromPerson($person_xml);
            }
        }
    }
    

    /** 
     * Creates a customer in MerchantOS from XML in Highrise person schema
     * @param SimpleXMLElement $person
     * @return SimpleXMLElement $customer
     */
    protected function createCustomerFromPerson($person) {
        try {
            $api_interface = new APIInterface($this->_mos_api_key, $this->_mos_acct_id, $this->_highrise_api_key, $this->_highrise_username);
            $new_customer = XMLTransformations::personToCustomer($person);
            $customer = $api_interface->createCustomer($new_customer);
            // put new MOS customer ID in Highrise custom field
            $highrise_person_id = $person->id;
            $mos_customer_id = $customer->customerID;
            $this->updatePersonWithCustomerID($highrise_person_id, $mos_customer_id);
        }
        catch (Exception $e) {
            $this->logException(new Exception ('createCustomerFromPerson Error: ' . $e->getMessage()), $person->asXML());
        }
        return $customer;
    }
    
    
    /**
     * Updates a customer in MerchantOS from XML in Highrise person schema
     * @param SimpleXMLElement $person
     * @return SimpleXMLElement $customer
     */
    protected function updateCustomerFromPerson($person) {
        try {
            $updated_customer = XMLTransformations::personToCustomer($person);
            foreach($person->subject_datas->subject_data as $subject_data) {
                if ($subject_data->subject_field_label == self::HIGHRISE_CUST_ID_FIELD_NAME) {
                    $customer_id = $subject_data->value;
                    break;
                }
            }
            $api_interface = new APIInterface($this->_mos_api_key, $this->_mos_acct_id, $this->_highrise_api_key, $this->_highrise_username);
            $customer = $api_interface->updateCustomer($customer_id, $updated_customer);
        }
        catch (Exception $e) {
            $this->logException(new Exception('updateCustomerFromPerson Error: ' . $e->getMessage()), $person->asXML());
        }
        return $customer;
    }
    
    /**
     * Creates a person in Highrise from XML in MerchantOS customer schema
     * @param SimpleXMLElement $customer
     * @return SimpleXMLElement $person
     */
    protected function createPersonFromCustomer($customer) {
        try {
            $new_person = XMLTransformations::customerToPerson($customer, $this->_custom_field_id);
            $api_interface = new APIInterface($this->_mos_api_key, $this->_mos_acct_id, $this->_highrise_api_key, $this->_highrise_username);
            $person = $api_interface->createPerson($new_person);
        }
        catch (Exception $e) {
            $this->logException(new Exception('createPersonFromCustomer Error: ' . $e->getMessage()), $customer->asXML());
        }
        return $person;
    }
    
    /**
     * Updates a person in Highrise from XML in MerchantOS customer schema
     * @param SimpleXMLElement $customer
     * @return SimpleXMLElement $person
     */
    protected function updatePersonFromCustomer($customer) {
        try {
            $api_interface = new APIInterface($this->_mos_api_key, $this->_mos_acct_id, $this->_highrise_api_key, $this->_highrise_username);
            $person_id = $api_interface->findPersonFromCustomerID(self::HIGHRISE_CUST_ID_FIELD_NAME, $customer->customerID);
            if (!$person_id) {
                // entering this branch means that the MerchantOS customer was created before last_synced_on
                // and modified since last_synced_on, but we can't locate a corresponding Highrise Person
                // so we should probably create the person instead
                $this->createPersonFromCustomer($customer);
            }
            else {
                $updated_person = XMLTransformations::customerToPerson($customer, $this->_custom_field_id);
                $person = $api_interface->updatePerson($person_id, $updated_person);
            }
        }
        catch (Exception $e) {
            $this->logException(new Exception('updatePersonFromCustomer Error: ' . $e->getMessage()), $customer->asXML());
        }
        return $person;
    }
    
    
    /**
     * Updates a person in Highrise with a MerchantOS customer id
     * @param SimpleXMLElement $person
     * @param int $mos_customer_id
     * @return SimpleXMLElement $updated_person
     */
    protected function updatePersonWithCustomerID($highrise_person_id, $mos_customer_id) {
       $update_xml = new SimpleXMLElement('<person><subject_datas type="array"><subject_data><subject_field_id type="integer">' . 
                $this->_custom_field_id . '</subject_field_id><value>' . $mos_customer_id . 
                '</value></subject_data></subject_datas></person>');
        try {
            $api_interface = new APIInterface($this->_mos_api_key, $this->_mos_acct_id, $this->_highrise_api_key, $this->_highrise_username);
            $updated_person = $api_interface->updatePerson($highrise_person_id, $update_xml);
        }
        catch (Exception $e) {
            $data_involved = 'highrise_person_id = ' . $highrise_person_id . ' mos_customer_id = ' . $mos_customer_id;
            $this->logException(new Exception('updatePersonWithCustomerID Error: ' . $e->getMessage()), $data_involved);
        }
        return $updated_person;
    }
        
    /**
     * Logs an exception and any data involved.
     * @param Exception $e 
     * @param string $data_involved
     */
    protected function logException($e, $data_involved=NULL) {
        // write date, SyncAccount id, exception message and any data involved to the exception table in the database   
        $now = new SyncDateTime();
        $exception_message = 'SyncAccount::' . $e->getMessage();
        $dao = new SyncAccountDAO();
        $was_logged = $dao->logException($this->_id, $now->getDatabaseFormat(), $exception_message, $data_involved);
    }
    
    
    
    /**
     * Set the MerchantOS API key
     * @param string $mos_api_key 
     */
    public function setMOSAPIKey($mos_api_key) {
        $this->_mos_api_key = $mos_api_key;
    }
    
    
    /**
     * Set the MerchantOS account ID
     * @param int $mos_account_id 
     */
    public function setMOSAccountID($mos_account_id) {
        $this->_mos_acct_id = $mos_account_id;
    }
    
    
    /**
     * Set the Highrise API key
     * @param string $highrise_api_key 
     */
    public function setHighriseAPIKey($highrise_api_key) {
        $this->_highrise_api_key = $highrise_api_key;
    }
    
    
    /**
     * Set the Highrise username
     * @param string $highrise_username 
     */
    public function setHighriseUsername($highrise_username) {
        $this->_highrise_username = $highrise_username;
    }
    
    
    /**
     * Set the id, database primary key
     * @param int $new_id 
     */
    public function setID($new_id) {
        $this->_id = $new_id;
    }
    
    
    /**
     * Get the id (database primary key)
     * @return int $id
     */
    public function getID() {
        return $this->_id;
    }
    
    /**
     * Get the MerchantOS account key
     * @return string $mos_account_key
     */
    public function getMOSAccountKey() {
        return $this->_mos_acct_key;
    }
    
    /**
     * Get the MerchantOS API key
     * @return string $mos_api_key
     */
    public function getMOSAPIKey() {
        return $this->_mos_api_key;
    }
    
    /**
     * Get the MerchantOS account ID
     * @return int $mos_account_id
     */
    public function getMOSAccountID() {
        return $this->_mos_acct_id;
    }
    
    /**
     * Get the Highrise API key
     * @return string $highrise_api_key
     */
    public function getHighriseAPIKey() {
        return $this->_highrise_api_key;
    }
    
    /**
     * Get the Highrise username
     * @return string $highrise_username
     */
    public function getHighriseUsername() {
        return $this->_highrise_username;
    }
    
    /**
     * Get the custom field id
     * @return int $custom_field_id
     */
    public function getCustomFieldID() {
        return $this->_custom_field_id;
    }
    
    /**
     * Get the last synced on in database datetime format
     * @return string $last_synced_on
     */
    public function getLastSyncedOn() {
        $last_synced_on = NULL;
        if ($this->_last_synced_on) {
            $last_synced_on = $this->_last_synced_on->getDatabaseFormat();
        }
        return $last_synced_on;
    }
    

    /**
     * Returns an HTML string describing the values of each field, really only meant for testing use
     * @return string $s
     */
    public function toString() {
        $last_synced_on = 'NULL';
        if ($this->_last_synced_on) {
            $last_synced_on = $this->_last_synced_on->getDatabaseFormat();
        }
        
        $s = '<p>SyncAccount ID: ' . $this->_id . 
                '<br />  mos_acct_key: ' . $this->_mos_acct_key . 
                '<br />  mos_api_key: ' . $this->_mos_api_key . 
                '<br />  mos_acct_id: ' . $this->_mos_acct_id . 
                '<br />  highrise_api_key: ' . $this->_highrise_api_key . 
                '<br />  highrise_username: ' . $this->_highrise_username . 
                '<br />  custom_field_id: ' . $this->_custom_field_id . 
                '<br />  last_synced_on: ' . $last_synced_on . 
                '</p>';
        return $s;
    }

}
?>
