<?php
/**
 * Sync
 * 
 * @author Erika Ellison
 *  
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('Database.class.php');
require_once('SyncAccount.class.php');

$database = new Database();
while ($row = $database->getNextSyncAccount()) {
    print_r($row);
    echo '<br /><br />';
    
    $acct = new SyncAccount($row['email_address'], $row['password'], $row['name'], 
            $row['mos_api_key'], $row['mos_api_key'], $row['highrise_api_key'], 
            $row['highrise_username'], $row['last_synced_on'], $row['id']);

    // $acct->sync();
    echo $acct->toString() . '<br /><br />';
}

?>
