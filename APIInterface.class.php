<?php
/**
 * APIInterface provides a wrapper for calls to MerchantOS or Highrise.
 *
 * @author Erika Ellison
 */

require_once('HighriseAPICall.class.php');
require_once('MOSAPICall.class.php');
require_once('XMLTransformations.class.php');

class APIInterface {
    protected $_mos_api_key;
    protected $_mos_acct_id;
    protected $_highrise_api_key;
    protected $_highrise_username;
    
    protected $_mos_api;
    protected $_highrise_api;
    
    const HIGHRISE_PERSONS_PER_PAGE = 500;
    const MOS_CUSTOMERS_PER_PAGE = 100;
    
    /** Creates a new APIInterface
     * @param string $mos_api_key
     * @param string/int $mos_acct_id
     * @param string $highrise_api_key
     * @param string $highrise_username 
     */
    public function __construct($mos_api_key, $mos_acct_id, $highrise_api_key, $highrise_username) {
        $this->_mos_api_key = $mos_api_key;
        $this->_mos_acct_id = $mos_acct_id;
        $this->_highrise_api_key = $highrise_api_key;
        $this->_highrise_username = $highrise_username;
        
        $this->_mos_api = new MOSAPICall($this->_mos_api_key, $this->_mos_acct_id);
        $this->_highrise_api = new HighriseAPICall($this->_highrise_api_key, $this->_highrise_username);
    }
    
    /** creates a new custom field in Highrise for people to use
     * @param string $label_name the name the custom field should have
     * @return SimpleXMLElement $custom_field
     */
    public function defineCustomHighriseField($label_name) {
        $custom_field_xml = '<subject-field><label>' . $label_name . '</label></subject-field>';
        try {
            $xml_response = $this->_highrise_api->makeAPICall('subject_fields.xml', 'Create', $custom_field_xml);
        }
        catch (Exception $e) {
            throw new Exception('APIInterface::defineCustomHighriseField error: ' . $e->getMessage());
        }
        return $xml_response;
    }
    
    
    /** finds the ID of the first person found in Highrise that has the given MerchantOS customer ID
     * @param int/string $customer_id
     * @return int/string $person_id
     */
    public function findPersonFromCustomerID($custID_field_name, $customer_id) {
        $search_url = 'people/search.xml?criteria[' . $custID_field_name . ']=' . $customer_id;
        try {
            $result = $this->_highrise_api->makeAPICall($search_url, 'Read');
            if ($result->count() == 0) {
                $person_id = false;
            }
            else {
                $person_id = (string) $result->person[0]->id;
            }
            return $person_id;
        }
        catch (Exception $e) {
            throw new Exception('APIInterface::findPersonFromCustomerID error: ' . $e->getMessage());
        }
    }
    
    
    /** reads all Customers in MerchantOS
     * @return SimpleXMLElement $all_customers
     */
    public function readAllCustomers() {
        try {
            $all_customers;
            $offset = 0;
            $query_prefix = 'limit=' . self::MOS_CUSTOMERS_PER_PAGE . '&offset=';
            do {
                $query_string = $query_prefix . $offset;
                if ($offset == 0) {
                    $all_customers = $page = $this->_mos_api->makeAPICall('Account.Customer', 'Read', null, null, 'xml', $query_string);
                }
                else {
                    $page = $this->_mos_api->makeAPICall('Account.Customer', 'Read', null, null, 'xml', $query_string);
                    $all_customers = XMLTransformations::mergeXML($all_customers, $page);
                }
                $offset += self::MOS_CUSTOMERS_PER_PAGE;
            } while ($page->count() == self::MOS_CUSTOMERS_PER_PAGE);
            return $all_customers;
        }
        catch (Exception $e) {
            throw new Exception('APIInterface::readAllCustomers error: ' . $e->getMessage());
        }
    }
    

    /** Reads all People that have been created since the datetime given.
     * @param string $datetime in MerchantOS format
     * @return SimpleXMLElement $customers_since
     */
    public function readCustomersCreatedSince($datetime) {
        try {
            $query_string = 'createTime=' . urlencode('>,' . $datetime);
            echo 'query_string = ', urldecode($query_string);
            $offset = 0;
            do {
                $query_string .= '&limit=' . self::MOS_CUSTOMERS_PER_PAGE . '&offset=' . $offset;
                if ($offset == 0) {
                    $customers_since = $page = $this->_mos_api->makeAPICall('Account.Customer', 'Read', null, null, 'xml', $query_string);
                }
                else {
                    $page = $this->_mos_api->makeAPICall('Account.Customer', 'Read', null, null, 'xml', $query_string);
                    $customers_since = XMLTransformations::mergeXML($customers_since, $page);
                }
                $offset += self::MOS_CUSTOMERS_PER_PAGE;
            } WHILE ($page->count() == self::MOS_CUSTOMERS_PER_PAGE);
            
            return $customers_since;
        }
        catch (Exception $e) {
            throw new Exception('APIInterface::readCustomersSince error: ' . $e->getMessage());
        }
    }
    
    
    /** Reads all People that have been modified since the datetime given.
     * @param string $datetime in MerchantOS format
     * @return SimpleXMLElement $customers_since
     */
    public function readCustomersModifiedSince($datetime) {
        try {
            $query_string = 'timeStamp=' . urlencode('>,'. $datetime) . '&createTime=' . urlencode('<,' . $datetime);
            echo 'query_string = ', urldecode($query_string);
            $offset = 0;
            do {
                $query_string .= '&limit=' . self::MOS_CUSTOMERS_PER_PAGE . '&offset=' . $offset;
                if ($offset == 0) {
                    $customers_since = $page = $this->_mos_api->makeAPICall('Account.Customer', 'Read', null, null, 'xml', $query_string);
                }
                else {
                    $page = $this->_mos_api->makeAPICall('Account.Customer', 'Read', null, null, 'xml', $query_string);
                    $customers_since = XMLTransformations::mergeXML($customers_since, $page);
                }
                $offset += self::MOS_CUSTOMERS_PER_PAGE;
            } WHILE ($page->count() == self::MOS_CUSTOMERS_PER_PAGE);

            return $customers_since;
        }
        catch (Exception $e) {
            throw new Exception('APIInterface::readCustomersSince error: ' . $e->getMessage());
        }
    }
    
    
    /** reads all People in Highrise
     * @return SimpleXMLElement $all_people
     */
    public function readAllPeople() {
        try {
            $all_people;
            $offset = 0;
            do {
                if ($offset == 0) {
                $all_people = $page = $this->_highrise_api->makeAPICall('people.xml', 'Read');
                }
                else {
                    $page = $this->_highrise_api->makeAPICall('people.xml?n=' . $offset, 'Read');
                    $all_people = XMLTransformations::mergeXML($all_people, $page);
                }
                $offset += self::HIGHRISE_PERSONS_PER_PAGE;
            } while ($page->count() == self::HIGHRISE_PERSONS_PER_PAGE);

            return $all_people;
        }
        catch (Exception $e) {
            throw new Exception('APIInterface::readAllPeople error: ' . $e->getMessage());
        }
    }
    
    
    /** Reads all People that have been created or modified since the datetime given.
     * @param string $datetime in Highrise format
     * @return SimpleXMLElement $people_since
     */
    public function readPeopleSince($datetime) {        
        try {
            $query_string = '?since=' . $datetime;
            $offset = 0;
            do {
                if ($offset == 0) {
                    $people_since = $page = $this->_highrise_api->makeAPICall('people.xml' . $query_string, 'Read');
                }
                else {
                    $page = $this->_highrise_api->makeAPICall ('people.xml' . $query_string . '&n=' . $offset, $action);
                    $people_since = XMLTransformations::mergeXML($people_since, $page);
                }

            } while ($page->count() == self::HIGHRISE_PERSONS_PER_PAGE);

            return $people_since;
        }
        catch (Exception $e) {
            throw new Exception('APIInterface::readPeopleSince error: ' . $e->getMessage());
        }
    }
    
    
    /** Creates a customer in MerchantOS
     * @param SimpleXMLElement $customer the xml of the customer to be created
     * @return SimpleXMLElement $customer_xml the created customer's xml
     */
    public function createCustomer($customer) {
        try {
            $customer_xml = $this->_mos_api->makeAPICall('Account.Customer', 'Create', null, $customer->asXML());
        }
        catch (Exception $e) {
            throw new Exception('APIInterface::createCustomer error: ' . $e->getMessage());
        }
        return $customer_xml;
    }
    

    /** Updates a customer in MerchantOS with the given XML.
     * @param int $customer_id
     * @param SimpleXMLElement $update_xml the XML to update the customer with
     * @return SimpleXMLElement $updated the updated XML of the customer
     */
    public function updateCustomer($customer_id, $update_xml) {
        try {
            $updated = $this->_mos_api->makeAPICall('Account.Customer', 'Update', $customer_id, $update_xml->asXML());
            return $updated;
        }
        catch (Exception $e) {
            throw new Exception('APIInterface::updateCustomer error: ' . $e->getMessage());
        }
    }

    
    /** Creates a person in Highrise
     * @param SimpleXMLElement $person the xml of the person to be created
     * @return SimpleXMLElement $person_xml the created person's xml
     */
    public function createPerson($person) {
        try {
            $person_xml = $this->_highrise_api->makeAPICall('people.xml', 'Create', $person->asXML());
            
        }
        catch (Exception $e) {
            throw new Exception('APIInterface::createPerson error: ' . $e->getMessage());
        }
        return $person_xml;
    }
    
    
    /** Updates a person in Highrise with the given xml.
     * @param int $person_id
     * @param SimpleXMLElement $update_xml the XML to update the person with
     * @return SimpleXMLElement $updated the updated XML of the person
     */
    public function updatePerson($person_id, $update_xml) {
        try {
            $updated = $this->_highrise_api->makeAPICall('people/' . $person_id . '.xml?reload=true', 'Update', $update_xml->asXML());
            return $updated;
        }
        catch (Exception $e) {
            throw new Exception('APIInterface::updatePerson error: ' . $e->getMessage());
        }
    }
    

    

    
    
    /** Archives all customers in the MerchantOS account, not meant to be used for anything but testing.
     */
    public function archiveAllCustomers($begin, $end) {
        for($i = $begin; $i <= $end; $i += 2) {
            $result = $this->_mos_api->makeAPICall('Account.Customer', 'Delete', $i);
            echo htmlentities($result->asXML()), '<br />';
            usleep(1500000); // gets around the 60 requests per minute limit...takes longer, but doesn't get interrupted this way
            if ($result->httpCode == 503) {
                echo 'archiveAllCustomers quitting early.';
                break;
            }
        }
    }
}

?>
