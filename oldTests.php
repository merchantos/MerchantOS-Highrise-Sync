<?php
/**
 * Method signatures have changed and it's very unlikely that any of these old tests
 * will still work until I get around to writing an updated collection of tests.
 */


require_once("HighriseAPICall.class.php");
require_once("MOSAPICall.class.php");

error_reporting(E_ALL);
ini_set('display_errors', '1');

// global variables
$mos_api_key = 'd95cf22bca845c8444715cc8e1840145e148bfaac16bd8eeaf7de66131e13eb4';
$mos_acct_id = 39184;
$highrise_api_key = '0f5b609203e0f9b3af5d4325215876d2';
$highrise_username = 'merchantosintern';


/* test Highrise API calls via wrapper */
function readCallHighrise() {
    global $highrise_api_key, $highrise_username;
    $highrise_api = new HighriseAPICall($highrise_api_key, $highrise_username);
    $result = $highrise_api->makeAPICall('GET', '/people.xml');
    echo htmlentities($result->asXML());
}


/* test MerchantOS API calls via wrapper */
function readCallMOS() {
    global $mos_api_key, $mos_acct_id;
    $mosapi = new MOSAPICall($mos_api_key, $mos_acct_id);
    $customers = $mosapi->makeAPICall('Account.Customer','Read');
    echo htmlentities($customers->asXML());
}


function writeCallHighrise() {
    global $highrise_api_key, $highrise_username;
    $highrise_api = new HighriseAPICall($highrise_api_key, $highrise_username);
    $person_xml ='<?xml version="1.0" encoding="UTF-8"?> <person><first-name>Savos</first-name><last-name>Aren</last-name><title>Archmage</title><company-name>Winterhold College</company-name><contact-data><email-addresses/><phone-numbers><phone-number><number>555-555-5555</number><location>Home</location></phone-number><phone-number><number>555-555-5555</number><location>Work</location></phone-number><phone-number><number>555-555-5555</number><location>Mobile</location></phone-number></phone-numbers><addresses><address><city>Winterhold</city><country>Tamriel</country><state>Skyrim</state><street>Hall of the Elements, College of Winterhold</street><zip>99999</zip><location>Work</location></address></addresses></contact-data></person>';
    $response = $highrise_api->makeAPICall('POST', '/people.xml', $person_xml);
    echo htmlentities($response->asXML());
}

//not yet tested
function writeCallMOS() {
    global $mos_api_key, $mos_acct_id;
    $mos_api = new MOSAPICall($mos_api_key, $mos_acct_id);
    $customer_xml = '';
    $response = $mos_api->makeAPICall('Acccount.Customer', 'Create', null, $customer_xml);
    echo htmlentities($response->asXML());
}


/* nevermind, not needed ... was meant to be used in XSLT stylesheet with registerPHPFunctions */
function getCompanyName($company_id) {
    global $highrise_api_key, $highrise_username;
    $highrise_api = new HighriseAPICall($highrise_api_key, $highrise_username);
    
    $company = $highrise_api->makeAPICall('GET', '/companies/' . $company_id . '.xml');
    $name = (string) $company->name;
    return $name;
}

?>

<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        
        $mos_api = new MOSAPICall($mos_api_key, $mos_acct_id);
        $highrise_api = new HighriseAPICall($highrise_api_key, $highrise_username);
        
        // get simpleXMLElement for all People from Highrise
        $people = $highrise_api->makeAPICall('GET', '/people.xml');
        
        // create XSLT processor
        $person_to_customer = new XSLTProcessor();        
        $stylesheet = simplexml_load_file('peopleToCustomers.xsl');        
        $person_to_customer->importStylesheet($stylesheet);

            
        // turn them into Customers
        foreach ($people->person as $person) {
            $person_xml = new SimpleXMLElement($person->asXML());
            echo 'Highrise XML<br />';
            echo htmlentities($person_xml->asXML());
            echo '<br /><br />';
            
            $customer_xml = new SimpleXMLElement($person_to_customer->transformToXML($person_xml));
            echo 'MerchantOS XML<br />';
            echo htmlentities($customer_xml->asXML());
            echo '<br /><br />';

        }
        
        ?>
    </body>
</html>

    
<?php
/** Not actually needed, it turns out.
 * Was meant to be used in XSLT stylesheet
 * after calling registerPHPFunctions() on processor.
 *
 * @global string $highrise_api_key
 * @global string $highrise_username
 * @param string $company_id
 * @return string $name
 */
function getCompanyName($company_id) {
    global $highrise_api_key, $highrise_username;
    $highrise_api = new HighriseAPICall($highrise_api_key, $highrise_username);
    
    $company = $highrise_api->makeAPICall('GET', '/companies/' . $company_id . '.xml');
    $name = (string) $company->name;
    return $name;
}

?>