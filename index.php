<?php 
/**
 * @author Erika Ellison
 * requirements: php5-xsl package (not installed by default)
 * 
 */

require_once('MOSAPICall.class.php');
require_once('HighriseAPICall.class.php');


error_reporting(E_ALL);
ini_set('display_errors', '1');

$mos_api_key = 'd95cf22bca845c8444715cc8e1840145e148bfaac16bd8eeaf7de66131e13eb4';
$mos_acct_id = 39184;
$highrise_api_key = '0f5b609203e0f9b3af5d4325215876d2';
$highrise_username = 'merchantosintern';

function initialSync() {
    global $mos_api_key, $mos_acct_id, $highrise_api_key, $highrise_username;
    
    $mos_api = new MOSAPICall($mos_api_key, $mos_acct_id);
    $highrise_api = new HighriseAPICall($highrise_api_key, $highrise_username);

    // get SimpleXMLElement for all customers, people
    $customers = $mos_api->makeAPICall('Account.Customer', 'Read');
    $people = $highrise_api->makeAPICall('GET', '/people.xml');
    
    
    $customer_to_person = new XSLTProcessor();        
    $stylesheet = simplexml_load_file('customerToPerson.xsl');        
    $customer_to_person->importStylesheet($stylesheet); 

    foreach($customers->Customer as $customer) {
        $customer_xml = new SimpleXMLElement($customer->asXML());
        echo htmlentities($customer_xml->asXML());
        echo '<br /><br />';
        
        // if (!personExists($customer_xml))
        
        $person_xml = new SimpleXMLElement($customer_to_person->transformToXML($customer_xml));
        echo htmlentities($person_xml->asXML());
        echo '<br /><br />';

        $response = $highrise_api->makeAPICall('POST', '/people.xml', $person_xml->asXML());
        echo htmlentities($response->asXML());
        echo '<br /><br />';
        echo '<br /><br />';
    }
    
}

function personExists() {
    

}

function customerExists() {
    
    
}

?>

<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Get Customers Test</title>
    </head>
    <body>
        <?php

        

 
        
        
        ?>
        
    </body>
</html>
