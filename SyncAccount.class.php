<?php
/**
 * SyncAccount
 *
 * @author Erika Ellison
 */

require_once('HighriseAPICall.class.php');
require_once('MOSAPICall.class.php');
require_once('TransformXML.class.php');

class SyncAccount {

    protected $_mos_api_key;
    protected $_mos_acct_id;
    protected $_highrise_api_key;
    protected $_highrise_username;

    protected $_last_synced_on;
    
    protected $_mos_api;
    protected $_highrise_api;
    
    public function __construct($mos_api_key, $mos_acct_id, $highrise_api_key, $highrise_username) {
        $this->_mos_api_key = $mos_api_key;
        $this->_mos_acct_id = $mos_acct_id;
        $this->_highrise_api_key = $highrise_api_key;
        $this->_highrise_username = $highrise_username;
        
        $this->_mos_api = new MOSAPICall($this->_mos_api_key, $this->_mos_acct_id);
        $this->_highrise_api = new HighriseAPICall($this->_highrise_api_key, $this->_highrise_username);
    }

    /** Moves all Customers in MerchantOS to Highrise, and all People in Highrise to MerchantOS.
     *  Does not check for duplicates.
     *  @return array $uncreated An associative array of SimpleXMLElements with keys of 'people' and 'Customers'
     */
    public function initialSync() {
        // create custom fields in Highrise to correspond to selected MerchantOS Customer fields
        // to be implemented later
        
        // attempt to sync all existing contacts
        $customers = $this->readAllCustomers();
        $people = $this->readAllPeople();
        $new_customers = TransformXML::peopleToCustomers($people);
        $new_people = TransformXML::customersToPeople($customers);
        $uncreatedCustomers = $this->createCustomers($new_customers);
        $uncreatedPeople = $this->createPeople($new_people);
        
        // report any failures
        $uncreated = array(
            'Customers' => $uncreatedCustomers,
            'people' => $uncreatedPeople,
        );
        return $uncreated;
    }
    
    
    /** Syncs Customers and People that have been created or modified after $_last_synced_on
     * 
     *
     */
    public function incrementalSync() {
        
    }
    
    
    
   
    /** reads all Customers in MerchantOS
     * @return SimpleXMLElement $customers
     */
    public function readAllCustomers() {
        $customers = $this->_mos_api->makeAPICall('Account.Customer', 'Read');
        return $customers;
    }

    
    /** reads all People in Highrise
     * @return SimpleXMLElement $people
     */
    public function readAllPeople() {
        $people = $this->_highrise_api->makeAPICall('people.xml', 'Read');
        return $people;
    }

    /** creates all Customers passed in
     * @param SimpleXMLElement $customers 
     * @return SimpleXMLElement $notCreated a list of any Customers that couldn't be created
     */
    public function createCustomers($customers) {
        $notCreated = '';
        foreach($customers->Customer as $customer) {
            $customer_xml = $customer->asXML();
            try {
                $response = $this->_mos_api->makeAPICall('Account.Customer', 'Create', null, $customer_xml);
                // confirm creation from XML response
                if (!($response->customerID)) {
                    $notCreated .= $customer_xml;
                }
                
            }
            catch (Exception $e) {
                $notCreated .= $customer_xml;
                // send $cusotmer_xml and $e->getMessage() to some error log?
            }
        }
        $notCreated = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Customers>' . $notCreated . '</Customers>');        
        return $notCreated;
    }
    
    /** creates all people passed in
     * @param SimpleXMLElement $people
     * @return SimpleXMLElement type 
     */
    public function createPeople($people) {
        $notCreated = '';
        foreach($people->person as $person) {
            $person_xml = $person->asXML();
            try {
                $response = $this->_highrise_api->makeAPICall('people.xml', 'Create', $person_xml);
                // confirm creation from XML response
                if (!($response->id)) {
                    $notCreated .= $person_xml;
                }
            }
            catch (Exception $e) {
                $notCreated .= $person_xml;
                // send $cusotmer_xml and $e->getMessage() to some error log?
            }
        }
        $notCreated = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><people>' . $notCreated . '</people>'); 
        return $notCreated;
    }
  
}
?>
