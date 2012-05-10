<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('APIInterface.class.php');
require_once('SyncDateTime.class.php');

define('BR', '<br />');

// account info to use for testing
$mos_api_key = 'd95cf22bca845c8444715cc8e1840145e148bfaac16bd8eeaf7de66131e13eb4';
$mos_acct_id = 39184;
$highrise_api_key = '0f5b609203e0f9b3af5d4325215876d2';
$highrise_username = 'merchantosintern';
$api_interface = new APIInterface($mos_api_key, $mos_acct_id, $highrise_api_key, $highrise_username);



$since = new SyncDateTime('2012-05-08T21:10:00+00:00');
echo 'query date = ' . $since->getDatabaseFormat(), BR, BR;
testReadCustomersSince($since);



// run successfully 2012-05-07
function testDefineCustomHighriseField() {
    global $api_interface;
    echo 'APIInterface::defineCustomHighriseField: ', BR;
    $custom_field = $api_interface->defineCustomHighriseField('test_custom_field');
    $html = htmlentities($custom_field);
    echo $html, BR, BR;
}

// run successfully 2012-05-08
function testFindPersonFromCustomerID() {
    global $api_interface, $customer_id;
    echo 'APIInterface::findPersonFromCustomerID: ', BR;
    $person_id = $api_interface->findPersonFromCustomerID('merchantos_customerid', $customer_id);
    echo 'PersonID = ', $person_id, ' where Customer ID = ', $customer_id, BR, BR;
}

// run successfully 2012-05-07 with and without pagination
function testReadAllCustomers() {
    global $api_interface;
    echo 'APIInterface::readAllCustomers: ', BR;
    $all_customers = $api_interface->readAllCustomers();
    echo htmlentities($all_customers->asXML()), BR, BR;
}

// run successfully on 2012-05-09 (only without pagination)
/**
 * @param SyncDateTime $since 
 */
function testReadCustomersSince($since) {
    global $api_interface;
    
    echo 'APIInterface::readCustomersCreatedSince: ', BR;
    $customers_created_since = $api_interface->readCustomersCreatedSince($since->getMerchantOSFormat());
    echo BR, htmlentities($customers_created_since->asXML()), BR, BR;

    echo 'APIInterface::readCustomersModifiedSince: ', BR;
    $customers_modified_since = $api_interface->readCustomersModifiedSince($since->getMerchantOSFormat());
    echo BR, htmlentities($customers_modified_since->asXML()), BR, BR;
}

// run successfully 2012-05-07 with and without pagination
function testReadAllPeople() {
    global $api_interface;
    echo 'APIInterface::readAllPeople: ', BR;
    $all_people = $api_interface->readAllPeople();
    echo '# of people = ', $all_people->count();
    echo htmlentities($all_people->asXML()), BR, BR;
}

// run successfully 2012-05-07 with and without pagination
function testReadPeopleSince() {
    global $api_interface;
    echo 'APIInterface::readPeopleSince: ', BR;
    $people_since = $api_interface->readPeopleSince($since->getHighriseFormat());
    echo '# of people = ', $people_since->count();
    echo htmlentities($people_since->asXML()), BR, BR;
}

// run successfully on 2012-05-07
function testCreateCustomer() {
    global $api_interface;
    echo 'APIInterface::createCustomer: ', BR;
    $customer = simplexml_load_string('<Customer><firstName>TestFirstName</firstName><lastName>TestLastName</lastName><title>TestTitle></title><company>TestCompany</company></Customer>');
    $customer_xml = $api_interface->createCustomer($customer);
    echo htmlentities($customer_xml->asXML());
}

// run successfully on 2012-05-08
function testUpdateCustomer($customer_id) {
    global $api_interface;
    echo 'APIInterface::updateCustomer: ', BR;
    $customer = simplexml_load_string('<Customer><firstName>UpdatedFirstName</firstName><lastName>UpdatedLastName</lastName><title>UpdatedTitle></title><company>UpdatedCompany</company></Customer>');
    $customer_xml = $api_interface->updateCustomer($customer_id, $customer);
    echo htmlentities($customer_xml->asXML());
}

// run successfully on 2012-05-07
function testCreatePerson() {
    global $api_interface;
    echo 'APIInterface::createPerson: ', BR;
    $person = simplexml_load_string('<person><first-name>TestFirstName</first-name><last-name>TestLastName</last-name><title>TestTitle</title><background>TestBackground</background><company-name>TestCompanyName</company-name></person>');
    $person_xml = $api_interface->createPerson($person);
    echo htmlentities($person_xml->asXML());
}

// run successfully on 2012-05-08
function testUpdatePerson($person_id) {
    global $api_interface;
    echo 'APIInterface::updatePerson: ', BR;
    $person = simplexml_load_string('<person><first-name>UpdatedFirstName</first-name><last-name>UpdatedLastName</last-name><title>UpdatedTitle</title><background>UpdatedBackground</background><company-name>UpdatedCompanyName</company-name></person>');
    $person_xml = $api_interface->updatePerson($person_id, $person);
    echo htmlentities($person_xml->asXML());
}



    
?>