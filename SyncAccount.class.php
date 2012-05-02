<?php
/**
 * SyncAccount
 *
 * @author Erika Ellison
 */

require_once('SyncDateTime.class.php');
require_once('APIInterface.class.php');
require_once('TransformXML.class.php');

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
    
    protected $_api_interface;
    
    /**
     *
     * @param string $email_address
     * @param string $password
     * @param string $name
     * @param string $mos_api_key
     * @param string $mos_acct_id
     * @param string $highrise_api_key
     * @param string $highrise_username
     * @param string $last_synced_on
     * @param string $id 
     */
    public function __construct($email_address, $password, $name, $mos_api_key, $mos_acct_id, 
            $highrise_api_key, $highrise_username, $last_synced_on=NULL, $id=NULL) {
        
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
        
        $this->_api_interface = new APIInterface($mos_api_key, $mos_acct_id, $highrise_api_key, $highrise_username);
    }
    
    /** Syncs the account and returns the new value for last_synced_on
     * @return string $last_synced_on a datetime in database format
     * 
     */
    public function sync() {
        if (!(isset($this->_last_synced_on))) {
            $this->initialSync();
        }
        else {
            echo 'went into Else clause';
            //$this->incrementalSync();
        }
        $this->_last_synced_on = new SyncDateTime();
        $last_synced_on =  $this->_last_synced_on->getDatabaseFormat();
        return $last_synced_on;
    }

    /** Copies all Customers in MerchantOS to Highrise, and all People in Highrise to MerchantOS.
     *  Does not check for duplicates.
     *  @return array $unsynced An associative array of SimpleXMLElements with keys of 'people' and 'Customers'
     */
    public function initialSync() {        
        // create a custom field in Highrise to track MerchantOS's customer id for each contact
        $this->_api_interface->defineCustomHighriseField('merchantos-customerid');
        
        // sync all existing contacts
        $customers = $this->_api_interface->readAllCustomers();
        echo htmlentities($customers->asXML()), '<br /><br />';
        $people = $this->_api_interface->readAllPeople();
        $new_customers = TransformXML::peopleToCustomers($people);
        $new_people = TransformXML::customersToPeople($customers);
        echo htmlentities($new_people->asXML()), '<br /><br />';
        
        $uncreatedCustomers = $this->_api_interface->createCustomers($new_customers);
        $uncreatedPeople = $this->_api_interface->createPeople($new_people);
        
        // report any failures
    }
    
    
    /** Syncs Customers and People that have been created or modified after $_last_synced_on
     *  Does not check for duplicates.
     *  @return array $unsynced an associative array of SimpleXMLElements with keys of 'people' and 'Customers'
     */
    public function incrementalSync() {
        $customers_since = $this->_api_interface->getCustomersSince($this->_last_synced_on);
        $people_since = $this->_api_interface->getPeopleSince($this->_last_synced_on);
        
        // some contacts will have been created after last_synced_on,
        // and so their modify date will also be after last_synced_on
        // to prevent (redundantly) updating those contacts after writing them
        // first all newly created contacts are isolated for writing,
        // then contacts that have only been modified are isolate for updating
        $customers_created = TransformXML::allCustomersCreatedSince($customers_since, $this->_last_synced_on);
        $customers_modified = TransformXML::onlyCustomersModifiedSince($customers_since, $this->_last_synced_on);
        $people_created = TransformXML::allPeopleCreatedSince($people_since, $this->_last_synced_on);
        $people_modified = TransformXML::onlyPeopleModifiedSince($people_since, $this->_last_synced_on);
        
        $customers_to_create = TransformXML::peopleToCustomers($people_created);
        $customers_to_update = TransformXML::peopleToCustomers($people_modified);
        $people_to_create = TransformXML::customersToPeople($customers_created);
        $people_to_update = TransformXML::customersToPeople($customers_modified);
        
        $uncreated_customers = $this->_api_interface->createCustomers($customers_to_create);
        $unupdated_customers = $this->_api_interface->updateCustomers($customers_to_update);
        $uncreated_people = $this->_api_interface->createPeople($people_to_create);
        $unupdated_people = $this->_api_interface->updatePeople($people_to_update);
        
        // report any failures
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
