<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('SyncAccount.class.php');
define('BR', '<br />');

// account info to use for testing
$mos_api_key = 'd95cf22bca845c8444715cc8e1840145e148bfaac16bd8eeaf7de66131e13eb4';
$mos_acct_id = 39184;
$highrise_api_key = '0f5b609203e0f9b3af5d4325215876d2';
$highrise_username = 'merchantosintern';

$sync_acct = new SyncAccount($mos_api_key, $mos_acct_id, 
        $highrise_api_key, $highrise_username, 
        null, null, null, 
        597843, null, null);





// run successfully 2012-05-10
function testUpdatePersonWithCustomerID($person_id, $customer_id) {
    global $sync_acct;
    echo 'SyncAccount::updatePersonWithCustomerID: ', BR;
    $updated_person = $sync_acct->updatePersonWithCustomerID($person_id, $customer_id);
    echo htmlentities($updated_person->asXML());
}


// run successfully 2012-05-10
function testCreateCustomerFromPerson() {
    global $sync_acct;
    echo 'SyncAccount::createCustomerFromPerson';
    $person = new SimpleXMLElement('<person><author-id type="integer">708166</author-id><background>Background1</background><company-id type="integer">116624952</company-id><created-at type="datetime">2012-05-02T22:50:40Z</created-at><first-name>FirstNamePerson1</first-name><group-id type="integer" nil="true"/><id type="integer">116232574</id><last-name>LastNamePerson1</last-name><owner-id type="integer" nil="true"/><title>Title1</title><updated-at type="datetime">2012-05-10T20:23:38Z</updated-at><visible-to>Everyone</visible-to><company-name>Company1</company-name><avatar-url>http://asset0.37img.com/highrise/missing/avatar.gif?r=3</avatar-url><contact-data><email-addresses type="array"/><instant-messengers type="array"/><phone-numbers type="array"/><twitter-accounts type="array"/><web-addresses type="array"/><addresses type="array"/></contact-data></person>');
    $customer = $sync_acct->createCustomerFromPerson($person);
    echo htmlentities($customer->asXML());
}

// run successfully 2012-05-10
function testCreatePersonFromCustomer() {
    global $sync_acct;
    echo 'SyncAccount::createPersonFromCustomer: ', BR;
    $customer = new SimpleXMLElement('<Customer><customerID>545</customerID><firstName>FirstNameCustomer1</firstName><lastName>LastNameCustomer1</lastName><archived>false</archived><title>Title1</title><company>CompanyName1</company><createTime>2012-05-02T16:09:11+00:00</createTime><timeStamp>2012-05-02T16:09:11+00:00</timeStamp><creditAccountID>0</creditAccountID><customerTypeID>0</customerTypeID><discountID>0</discountID><taxCategoryID>0</taxCategoryID><Contact><custom/><noEmail>false</noEmail><noPhone>false</noPhone><noMail>false</noMail><timeStamp>2012-05-02T16:09:11+00:00</timeStamp><Addresses><ContactAddress><address1>FirstAddress1</address1><address2>SecondAddress1</address2><city>City1</city><state>State1</state><zip>Zip1</zip><country>Country1</country></ContactAddress></Addresses><Phones><ContactPhone><number>111-111-1111</number><useType readonly="true">Home</useType></ContactPhone><ContactPhone><number>111-111-1112</number><useType readonly="true">Work</useType></ContactPhone><ContactPhone><number>111-111-1113</number><useType readonly="true">Pager</useType></ContactPhone><ContactPhone><number>111-111-1114</number><useType readonly="true">Mobile</useType></ContactPhone><ContactPhone><number>111-111-1115</number><useType readonly="true">Fax</useType></ContactPhone></Phones><Emails><ContactEmail><address>primary1@domain.tld</address><useType readonly="true">Primary</useType></ContactEmail><ContactEmail><address>secondary1@domain.tld</address><useType readonly="true">Secondary</useType></ContactEmail></Emails><Websites><ContactWebsite><url>www.1.com</url></ContactWebsite></Websites></Contact><Note><note>Note1</note><isPublic>true</isPublic><timeStamp>2012-05-02T16:09:11+00:00</timeStamp></Note></Customer>');
    $person = $sync_acct->createPersonFromCustomer($customer);
    echo htmlentities($person->asXML());    
}

// run successfully 2012-05-10
function testInitialSync() {
    global $sync_acct;
    echo 'SyncAccount::initialSync: ', BR;
    $sync_acct->initialSync();
}


function testUpdateCustomerFromPerson() {
    
}



function testUpdatePersonFromCustomer($customer) {
    
}

function testIncrementalSync() {
    
}

function testSync() {
    
}






?>
