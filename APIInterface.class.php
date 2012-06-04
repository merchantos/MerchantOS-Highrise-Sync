<?php
/**
 * APIInterface provides a wrapper for calls to MerchantOS or Highrise.
 * @author Erika Ellison
 */

require_once('HighriseAPICall.class.php');
require_once('MOSAPICall.class.php');
require_once('XMLTransformations.class.php');

/** 
 * APIInterface class
 * @author Erika Ellison
 */
class APIInterface {
    protected $_mos_api_key;
    protected $_mos_acct_id;
    protected $_highrise_api_key;
    protected $_highrise_username;
    
    protected $_mos_api;
    protected $_highrise_api;
    
    const HIGHRISE_PERSONS_PER_PAGE = 500;
    const MOS_CUSTOMERS_PER_PAGE = 100;
    
    /**
     * Creates a new APIInterface
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
    
    /**
     * Checks for valid Highrise API credentials
     * @return boolean $credentials_valid
     * @throws Exception 
     */
    public function hasValidCredentialsHighrise() {
        $credentials_valid = false;
        if ($this->_highrise_api_key && $this->_highrise_username) {
            $credentials_valid = true;
            try {
                $result = $this->_highrise_api->makeAPICall('account.xml', 'Read');
            }
            catch (Exception $e) {
                if (strpos($e->getMessage(), 'HTTP Basic: Access denied') !== FALSE) {
                    // good username, bad API key
                    $credentials_valid = false;
                }
                else {
                    // check for difference between exception caused by bad username and exception caused by something else
                    $error = $e->getMessage();
                    $error = str_replace('Highrise API Call Error: String could not be parsed as XML, Response: ', '', $error);
                    if ($error == ' ') {
                        // bad url because username does not exist in Highrise
                        $credentials_valid = false;
                    }
                    else {
                        // some exception possibly unrelated to whether or not credentials are valid
                        throw new Exception('APIInterface::testCredentialsHighrise Error: ' . $e->getMessage());
                    }
                }
            }
        }
        return $credentials_valid;
    }
    
    
    /**
     * Checks for valid MerchantOS API credentials
     * @return boolean $credentials_valid
     * @throws Exception 
     */
    public function hasValidCredentialsMerchantOS() {
        $credentials_valid = false;
        if ($this->_mos_api_key && $this->_mos_acct_id) {
            $credentials_valid = true;
            try {
                $result = $this->_mos_api->makeAPICall('Account', 'Read');
                if ($result->httpCode == 401) {
                    $credentials_valid = false;
                }
            }
            catch (Exception $e) {
                throw new Exception('APIInterface::testCredentialsMerchantOS Error: ' . $e->getMessage());
            }
        }
        return $credentials_valid;
    }
    
    /**
     * Creates a new custom field in Highrise
     * @param string $label_name the name the custom field should have
     * @return SimpleXMLElement $custom_field
     * @throws Exception
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
    
    
    /**
     * finds the ID of the first person in Highrise associated with a given MerchantOS customer ID
     * @param int/string $customer_id
     * @return int/string $person_id
     * @throws Exception
     */
    public function findPersonFromCustomerID($custID_field_name, $customer_id) {
        $search_url = 'people/search.xml?criteria[' . $custID_field_name . ']=' . $customer_id;
        try {
            $result = $this->_highrise_api->makeAPICall($search_url, 'Read');
            $person_id = false;
            if ($result->count() != 0) {
                $person_id = (string) $result->person[0]->id;
            }
        }
        catch (Exception $e) {
            throw new Exception('APIInterface::findPersonFromCustomerID error: ' . $e->getMessage());
        }
        return $person_id;
    }
    
    
    /**
     * reads all Customers in MerchantOS
     * @return SimpleXMLElement $all_customers
     * @throws Exception
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
            throw new Exception('APIInterface::readAllCustomers error: query_string=' . $query_string . '; ' . $e->getMessage());
        }
    }
    

    /**
     * Reads all People that have been created since the datetime given
     * @param string $datetime in MerchantOS format
     * @return SimpleXMLElement $customers_since
     * @throws Exception
     */
    public function readCustomersCreatedSince($datetime) {
        try {
            $query_string = 'createTime=' . urlencode('>,' . $datetime);
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
            throw new Exception('APIInterface::readCustomersSince error: query_string=' . $query_string . '; ' . $e->getMessage());
        }
    }
    
    
    /**
     * Reads all People that have been modified since the datetime given
     * @param string $datetime in MerchantOS format
     * @return SimpleXMLElement $customers_since
     * @throws Exception
     */
    public function readCustomersModifiedSince($datetime) {
        try {
            $query_string = 'timeStamp=' . urlencode('>,'. $datetime) . '&createTime=' . urlencode('<,' . $datetime);
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
            throw new Exception('APIInterface::readCustomersSince error: query_string=' . $query_string . '; ' . $e->getMessage());
        }
    }
    
    
    /**
     * Reads all People in Highrise
     * @return SimpleXMLElement $all_people
     * @throws Exception
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
    
    
    /**
     * Reads all People that have been created or modified since the datetime given
     * @param string $datetime in Highrise format
     * @return SimpleXMLElement $people_since
     * @throws Exception
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
            throw new Exception('APIInterface::readPeopleSince error: query_string=' . $query_string . ' ; ' . $e->getMessage());
        }
    }
    
    
    /**
     * Creates a customer in MerchantOS using the given XML
     * @param SimpleXMLElement $customer the xml of the customer to be created
     * @return SimpleXMLElement $customer_xml the created customer's xml
     * @throws Exception
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
    

    /**
     * Updates a customer in MerchantOS using the given XML
     * @param int $customer_id
     * @param SimpleXMLElement $update_xml the XML to update the customer with
     * @return SimpleXMLElement $updated the updated XML of the customer
     * @throws Exception
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

    
    /**
     * Creates a person in Highrise using the given XML
     * @param SimpleXMLElement $person the xml of the person to be created
     * @return SimpleXMLElement $person_xml the created person's xml
     * @throws Exception
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
    
    
    /**
     * Updates a person in Highrise using the given XML
     * @param int $person_id
     * @param SimpleXMLElement $update_xml the XML to update the person with
     * @return SimpleXMLElement $updated the updated XML of the person
     * @throws Exception
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
}

?>
