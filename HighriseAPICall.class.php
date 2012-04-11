<?php

/**
 * HighriseAPICall wraps up calls to the Highrise API.
 *
 * @author Erika Ellison
 */
class HighriseAPICall {
    protected $_default_timeout = 60;
    protected $_highrise_api_url_prefix = 'https://';
    protected $_highrise_api_url_suffix = '.highrisehq.com';
	
    protected $_api_key;
    protected $_url;
    
    public function __construct($api_key, $username) {
        $this->_api_key = $api_key;
        $this->_url = $_url_prefix . $username . $_url_suffix;
    }
    
    /**
     * Make the specified API call.
     * @param string $action one of the four HTTP verbs supported by Highrise
     * @param string $resource_name the Highrise resource to be accessed
     * @param string $xml a well-formed XML string for a Highrise create, update, or delete request
     * 
     * $xml parameter should include any query parameters as suggested by Highrise API documentation
     * eg, if you want to GET all People, pass in "/people.xml"
     * and if you want to get People by search term where field=value,
     * then pass in "/people/search.xml?criteria[field]=value"
     */
    public function makeAPICall($action,$resource_name,$xml=null) {
        $custom_request = strtoupper($action);
        /* initialize curl session and set defaults for new API call */
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->_url . $resource_name);
        curl_setopt($curl, CURLOPT_USERPWD, $this>_api_key . ':X');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->_default_timeout);
        if (isset($xml)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
        }
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $custom_request);
        
        /* get the string response from executing the curl session */
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
        /*
        // return the response as a simpleXMLElement
        try {
                $result_simplexml = new SimpleXMLElement($result);
        }
        catch (Exception $e) {
                throw new Exception("Highrise API Call Error: " . $e->getMessage() . ", Response: " . $result);
        }
        if (!is_object($result_simplexml)) {
                throw new Exception("Highrise API Call Error: Could not parse XML, Response: " . $result);
        }
        return $result_simplexml;
        */
    }

}
?>
