<?php

/**
 * HighriseAPICall wraps up calls to the Highrise API.
 *
 * @author Erika Ellison
 */

class HighriseAPICall {
    protected $_url;
    protected $_api_key;
    protected $_password = 'X';
    protected $_cert_filename = 'GeoTrustGlobalCA';

    
    
    public function __construct($api_key, $username) {
        $this->_api_key = $api_key;
        $this->_url = 'https://' . $username . '.highrisehq.com/';
    }
    
    /**
     * Make the specified API call.
     * @param string $action one of the four HTTP verbs
     * @param string $resource_name the Highrise resource to be accessed
     * @param string $xml a well-formed XML string for a Highrise create, update, or delete request
     * @return SimpleXMLElement $result_simplexml
     * $resource_name parameter should include any query parameters
     * as suggested by Highrise API documentation
     * eg, if you want to GET all People, pass in 'people.xml'
     * and if you want to get People by search term where field=value,
     * then pass in '/people/search.xml?criteria[field]=value'
     */
    public function makeAPICall($resource_name, $action, $xml=null) {
        /* initialize curl session and set defaults for new Highrise API call */
        $curl = new MOScURL();
        $curl->setUserAgent('MerchantOS-Highrise-Sync');
        $curl->setReturnTransfer(1);
        $curl->setBasicAuth($this->_api_key, $this->_password);
        
        // may need to enable these later
        $curl->setVerifyPeer(false);
        $curl->setVerifyHost(0);
        //$curl->setCaInfo($this->_cert_filename);
        
        /* if xml was passed in, set header */
        if (isset($xml)) {
            $curl->setHTTPHeader(array('Content-type: application/xml'));
        }
        
        $custom_request = 'GET';
        switch ($action)
        {
                case 'Create':
                        $custom_request = 'POST';
                        break;
                case 'Read':
                        $custom_request = 'GET';
                        break;
                case 'Update':
                        $custom_request = 'PUT';
                        break;
                case 'Delete':
                        $custom_request = 'DELETE';
                        break;
        }
        $curl->setCustomRequest($custom_request);

        /* get the raw response from executing the curl session */
        $result = $curl->call($this->_url . $resource_name, $xml);
        
        // return the response as a simpleXMLElement
        try {
            $result_simplexml = new SimpleXMLElement($result);
        }
        catch (Exception $e) {
            throw new Exception('Highrise API Call Error: ' . $e->getMessage() . ', Response: ' . $result);
        }
        if (!is_object($result_simplexml)) {
            throw new Exception('Highrise API Call Error: Could not parse XML, Response: ' . $result);
        }
        return $result_simplexml;
    }
    
}
?>
