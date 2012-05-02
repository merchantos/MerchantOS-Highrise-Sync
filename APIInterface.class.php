<?php
/**
 * APIInterface provides a wrapper for calls to MerchantOS or Highrise.
 *
 * @author Erika Ellison
 */

require_once('HighriseAPICall.class.php');
require_once('MOSAPICall.class.php');

class APIInterface {
    protected $_mos_api_key;
    protected $_mos_acct_id;
    protected $_highrise_api_key;
    protected $_highrise_username;
    
    protected $_mos_api;
    protected $_highrise_api;
    
    const HIGHRISE_PERSONS_PER_PAGE = 500;
    const MOS_CUSTOMERS_PER_PAGE = 500;
    
    
    public function __construct($mos_api_key, $mos_acct_id, $highrise_api_key, $highrise_username) {
        $this->_mos_api_key = $mos_api_key;
        $this->_mos_acct_id = $mos_acct_id;
        $this->_highrise_api_key = $highrise_api_key;
        $this->_highrise_username = $highrise_username;
        
        $this->_mos_api = new MOSAPICall($this->_mos_api_key, $this->_mos_acct_id);
        $this->_highrise_api = new HighriseAPICall($this->_highrise_api_key, $this->_highrise_username);
    }
    
    
    public function defineCustomHighriseField($label_name) {
        $xml_string = '<subject-field><label>' . $label_name . '</label></subject-field>';
        $this->_highrise_api->makeAPICall('subject_fields.xml', 'Create', $xml_string);
    }
    
    
    /** reads all Customers in MerchantOS
     * @return SimpleXMLElement $all_customers
     */
    public function readAllCustomers() {
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
                $all_customers = TransformXML::mergeXML($all_customers, $page);
            }
            $offset += self::MOS_CUSTOMERS_PER_PAGE;
        } while ($page->count() == self::MOS_CUSTOMERS_PER_PAGE);
        return $all_customers;
    }

    
    /** reads all People in Highrise
     * @return SimpleXMLElement $all_people
     */
    public function readAllPeople() {
        $all_people;
        $offset = 0;
        // deals with Highrise pagination by repeating the read call with increased offset
        // while the number of results in the last response is equal to Highrise page limit
        // also keeps adding the most recent XML to the original XML tree, so that
        // only one SimpleXMLElement needs to be returned to hide pagination from the caller
        do {
            if ($offset == 0) {
               $all_people = $page = $this->_highrise_api->makeAPICall('people.xml', 'Read');
            }
            else {
                $page = $this->_highrise_api->makeAPICall('people.xml?n=' . $offset, 'Read');
                $all_people = TransformXML::mergeXML($all_people, $page);
            }
            $offset += self::HIGHRISE_PERSONS_PER_PAGE;
        } while ($page->count() == self::HIGHRISE_PERSONS_PER_PAGE);
        
        return $all_people;
    }

    /** creates all Customers passed in
     * @param SimpleXMLElement $customers 
     * @return SimpleXMLElement $uncreated_customers
     */
    public function createCustomers($customers) {
        $uncreated_customers = '';
        foreach($customers->Customer as $customer) {
            $customer_xml = $customer->asXML();
            try {
                $response = $this->_mos_api->makeAPICall('Account.Customer', 'Create', null, $customer_xml);
                // confirm creation from XML response and put merchantos customerID back into Highrise
                $merchantos_customerid = $response->customerID->asXML();
                if ($merchantos_customerid) {
                    $updated = $this->_highrise_api->makeAPICall('people/' . $customer->highrise_customerid . '.xml?reload=true', 'Update', $merchantos_customerid);
                    if (!($updated->merchantos_customerid)) {
                        // report error somewhere
                    }
                }
                else {
                    $uncreated_customers .= $customer_xml;
                }
            }
            catch (Exception $e) {
                $customer_xml.addChild('exception', $e->getMessage());
                $uncreated_customers .= $customer_xml;
            }
        }
        $uncreated_customers = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><Customers>' . $notCreated . '</Customers>');        
        return $uncreated_customers;
    }
    
    /** creates all people passed in
     * @param SimpleXMLElement $people
     * @return SimpleXMLElement $uncreated_people 
     */
    public function createPeople($people) {
        $uncreated_people = '';
        foreach($people->person as $person) {
            try {
                $response = $this->_highrise_api->makeAPICall('people.xml', 'Create', $person->asXML());
                // confirm creation from XML response
                if (!($response->id)) {
                    $uncreated_people .= $person_xml;
                }
            }
            catch (Exception $e) {
                $uncreated_people .= $person_xml;
                // send $cusotmer_xml and $e->getMessage() to some error log?
            }
        }
        $uncreated_people = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><people>' . $uncreated_people . '</people>'); 
        return $uncreated_people;
    }
    
    
    /**
     *
     * @param SyncDateTime $datetime
     * @return SimpleXMLElement $customers_since
     */
    public function getCustomersSince($datetime) {
        $query_string = 'createTime=' . urlencode('>,' . $datetime->getMerchantOSFormat());
        echo $query_string . '<br />';
        
        $offset = 0;
        do {
            $query_string .= '&limit=' . self::MOS_CUSTOMERS_PER_PAGE . '&offset=' . $offset;
            if ($offset == 0) {
                $customers_since = $page = $this->_mos_api->makeAPICall('Account.Customer', 'Read', null, null, 'xml', $query_string);
            }
            else {
                $page = $this->_mos_api->makeAPICall('Account.Customer', 'Read', null, null, 'xml', $query_string);
                $customers_since = TransformXML::mergeXML($customers_since, $page);
            }
            $offset += self::MOS_CUSTOMERS_PER_PAGE;
        } WHILE ($page->count() == self::MOS_CUSTOMERS_PER_PAGE);
        
        return $customers_since;
    }

    
    /**
     *
     * @param SyncDateTime $datetime
     * @return SimpleXMLElement $people_since
     */
    public function getPeopleSince($datetime) {        
        $query_string = '?since=' . $datetime->getHighriseFormat();
        
        $offset = 0;
        do {
            if ($offset == 0) {
                $people_since = $page = $this->_highrise_api->makeAPICall ('people.xml' . $query_string, 'Read');
            }
            else {
                $page = $this->_highrise_api->makeAPICall ('people.xml' . $query_string . '&n=' . $offset, $action);
                $people_since = TransformXML::mergeXML($people_since, $page);
            }
            
        } while ($page->count() == self::HIGHRISE_PERSONS_PER_PAGE);

        return $people_since;
    }

    
    /**
     *
     * @param SimpleXMLElement $customers
     * @return SimpleXMLElement $unupdated_customers
     */
    public function updateCustomers($customers) {
        foreach($customers->customer as $customer) {
            
        }

        return $unupdated_customers;
    }

    
    /**
     *
     * @param SimpleXMLElement $customers
     * @return SimpleXMLElement $unupdated_people
     */
    public function updatePeople($customers) {

        return $unupdated_people;
    }
    
    /** Archives all customers in the MerchantOS account, not meant to be used for anything but testing.
     */
    public function archiveAllCustomers() {
        for($i = 500; $i < 600; $i++) {
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
