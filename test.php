<?php
require_once("HighriseAPICall.class.php");
require_once("MOSAPICall.class.php");


/* test Highrise API calls via wrapper */
function testHighrise() {
    $highrise_api_key = '0f5b609203e0f9b3af5d4325215876d2';
    $highrise_username = 'merchantosintern';
    $highrise_api = new HighriseAPICall($highrise_api_key, $highrise_username);
    $result = $highrise_api->makeAPICall("GET", "/people.xml");
    echo htmlentities($result->asXML());
}


/* test MerchantOS API calls via wrapper */
function testMerchantOS() {
    $mos_api_key = 'd95cf22bca845c8444715cc8e1840145e148bfaac16bd8eeaf7de66131e13eb4';
    $mos_acct_id = 39184;
    $mosapi = new MOSAPICall($mos_api_key, $mos_acct_id);
    $customers = $mosapi->makeAPICall('Account.Customer','Read');
    echo htmlentities($customers->asXML());
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
        testHighrise();
        echo '<br /><br />';
        testMerchantOS();
        ?>
    </body>
</html>
