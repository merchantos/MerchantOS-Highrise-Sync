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
    $acct = new SyncAccount($row['email_address'], $row['password'], $row['name'], 
            $row['mos_api_key'], $row['mos_acct_id'], $row['highrise_api_key'], 
            $row['highrise_username'], $row['last_synced_on'], $row['id']);
    echo $acct->toString() . '<br /><br />';

    $last_synced_on = $acct->sync();
    echo 'back in SyncAllAccounts, $last_synced_on=', $last_synced_on;
    $database->updateLastSyncedOn($row['id'], $last_synced_on);
    
}

?>
