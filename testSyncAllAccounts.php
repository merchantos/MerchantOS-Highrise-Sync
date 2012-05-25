<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('SyncAccount.class.php');
require_once('SyncAccountDAO.class.php');

$dao = new SyncAccountDAO();

$all_accounts = $dao->getAllSyncAccounts();

foreach($all_accounts as $account) {
    echo $account->toString();
    if ($account->getID() != 0) {
        echo 'Attempting to sync account ' , $account->getID(), '<br />';
        
        $started = time();
        $was_synced = $account->sync();
        $finished = time();
        $time_taken = $finished - $started;
        
        $message = 'Account ' . $account->getID() . ' was ';
        if (!$was_synced) {
            $message .= 'NOT ';
        }
        $message .= 'synced in ' . $time_taken . ' seconds.<br />';
        echo $message;
    }
    echo '<br /><br />';
}

?>
