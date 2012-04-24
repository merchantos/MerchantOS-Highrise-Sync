<?php 
/**
 * @author Erika Ellison
 * 
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('SyncAccount.class.php');

?>

<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/plain; charset=UTF-8">
        <title>Index</title>
    </head>
    <body>
        <?php        
        $mos_api_key = 'd95cf22bca845c8444715cc8e1840145e148bfaac16bd8eeaf7de66131e13eb4';
        $mos_acct_id = 39184;
        $highrise_api_key = '0f5b609203e0f9b3af5d4325215876d2';
        $highrise_username = 'merchantosintern';

        $acct = new SyncAccount($mos_api_key, $mos_acct_id, $highrise_api_key, $highrise_username);

        $array = $acct->initialSync();
        $customers = $array['Customers'];
        $people = $array['people'];
        echo htmlentities($customers->asXML());
        echo htmlentities($people->asXML());
        ?>
        
    </body>
</html>
