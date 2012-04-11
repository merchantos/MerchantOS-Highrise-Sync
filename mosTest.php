<?php 
/**
 * 
 *
 * @author Erika Ellison
 */
define('NEWLINE', '<br />');

// include API wrapper, which depends on MOScURL
require_once("MOSAPICall.class.php");

// user acct info
$mos_api_key = 'd95cf22bca845c8444715cc8e1840145e148bfaac16bd8eeaf7de66131e13eb4';
$mos_acct_id = 39184;

// setup MOS API credentials
$mosapi = new MOSAPICall($mos_api_key, $mos_acct_id);

// make API call, get simpleXMLElement object as return
$customers = $mosapi->makeAPICall('Account.Customer','Read');

$added_customer = $mosapi-->makeAPICall('Account.')

?>

<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Get Customers Test</title>
    </head>
    <body>
        <?php
        
        
            
        foreach ($customers->Customer as $cust) {
            echo $cust->firstName . ' ' . $cust->lastName . NEWLINE;
        }
        
        
                
        echo htmlentities($customers->asXML());
        
        
        ?>
        
    </body>
</html>
