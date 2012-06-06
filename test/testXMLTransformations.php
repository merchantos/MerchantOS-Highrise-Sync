<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('XMLTransformations.class.php');

// tests both XMLTransformations::customerToPerson and XMLTransformations::personToCustomer
// does not test XMLTransformations::merge
// run successfully 2012-05-10


$customer =  new SimpleXMLElement('<Customer><customerID>545</customerID><firstName>FirstNameCustomer1</firstName><lastName>LastNameCustomer1</lastName><archived>false</archived><title>Title1</title><company>CompanyName1</company><createTime>2012-05-02T16:09:11+00:00</createTime><timeStamp>2012-05-02T16:09:11+00:00</timeStamp><creditAccountID>0</creditAccountID><customerTypeID>0</customerTypeID><discountID>0</discountID><taxCategoryID>0</taxCategoryID><Contact><custom/><noEmail>false</noEmail><noPhone>false</noPhone><noMail>false</noMail><timeStamp>2012-05-02T16:09:11+00:00</timeStamp><Addresses><ContactAddress><address1>FirstAddress1</address1><address2>SecondAddress1</address2><city>City1</city><state>State1</state><zip>Zip1</zip><country>Country1</country></ContactAddress></Addresses><Phones><ContactPhone><number>111-111-1111</number><useType readonly="true">Home</useType></ContactPhone><ContactPhone><number>111-111-1112</number><useType readonly="true">Work</useType></ContactPhone><ContactPhone><number>111-111-1113</number><useType readonly="true">Pager</useType></ContactPhone><ContactPhone><number>111-111-1114</number><useType readonly="true">Mobile</useType></ContactPhone><ContactPhone><number>111-111-1115</number><useType readonly="true">Fax</useType></ContactPhone></Phones><Emails><ContactEmail><address>primary1@domain.tld</address><useType readonly="true">Primary</useType></ContactEmail><ContactEmail><address>secondary1@domain.tld</address><useType readonly="true">Secondary</useType></ContactEmail></Emails><Websites><ContactWebsite><url>www.1.com</url></ContactWebsite></Websites></Contact><Note><note>Note1</note><isPublic>true</isPublic><timeStamp>2012-05-02T16:09:11+00:00</timeStamp></Note></Customer>');

$person = new SimpleXMLElement('<person><author-id type="integer">708166</author-id><background>Background1</background><company-id type="integer">116624952</company-id><created-at type="datetime">2012-05-02T22:50:40Z</created-at><first-name>FirstNamePerson1</first-name><group-id type="integer" nil="true"/><id type="integer">116232574</id><last-name>LastNamePerson1</last-name><owner-id type="integer" nil="true"/><title>Title1</title><updated-at type="datetime">2012-05-10T21:28:49Z</updated-at><visible-to>Everyone</visible-to><company-name>Company1</company-name><avatar-url>http://asset0.37img.com/highrise/missing/avatar.gif?r=3</avatar-url><contact-data><twitter-accounts type="array"/><web-addresses type="array"/><email-addresses type="array"/><addresses type="array"/><instant-messengers type="array"/><phone-numbers type="array"/></contact-data></person>');

$new_person = XMLTransformations::customerToPerson($customer);
$new_customer = XMLTransformations::personToCustomer($person);

echo htmlentities($new_person->asXML()), '<br / ><br />';
echo htmlentities($new_customer->asXML());

?>
