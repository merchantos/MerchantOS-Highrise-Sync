<?php 
/**
 * @author Erika Ellison
 * 
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('SyncAccount.class.php');

/*
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
 
 */

?>

<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/plain; charset=UTF-8">
        <title>Index</title>
    </head>
    <body>
        <?php        
        $_db_hostname = '127.0.0.1';
        $_db_username = 'root';
        $_db_password = 'mos123';
        $_db_name = 'sync';
        
        
        $mysqli = new mysqli($_db_hostname, $_db_username, $_db_password, $_db_name);
        if ($mysqli->connect_errno) {
            echo 'Failed to connect to mySQL.<br />';
        }
        else {
            echo 'Connection to mySQL successful.<br />';
        }
        
        if ($result = $mysqli->query('SELECT * FROM sync_accounts')) {
            while ($row = $result->fetch_assoc()) {
                print_r($row);
                echo '<br /><br />';
                
                $acct = new SyncAccount($row['email_address'], $row['password'], $row['name'], 
            $row['mos_api_key'], $row['mos_api_key'], $row['highrise_api_key'], 
            $row['highrise_username'], $row['last_synced_on'], $row['id']);
                
                echo $acct->toString() . '<br /><br />';
            }
        }
        else {
            echo 'Query failed.<br />';
        }
        
        
        
        
        ?>
        
    </body>
</html>
