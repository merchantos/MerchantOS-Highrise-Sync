<?php

/**
 * Description of HighriseAPICall
 *
 * @author Erika Ellison
 */
class HighriseAPICall {
    protected $_highrise_api_url_prefix = 'https://';
    protected $_highrise_api_url_suffix = '.highrisehq.com';
	
    protected $_api_key;
    protected $_username;
    
    public function __construct($api_key, $username) {
        $this->_api_key = $api_key;
        $this->_username = $username;
    }
    
    public function makeAPICall($controlname,$action) {
        $custom_request = "GET";
        switch ($action)
        {
                case "Create":
                        $custom_request = "POST";
                        break;
                case "Read":
                        $custom_request = "GET";
                        break;
                case "Update":
                        $custom_request = "PUT";
                        break;
                case "Delete":
                        $custom_request = "DELETE";
                        break;
        }
        
        
        
    }
}

?>
