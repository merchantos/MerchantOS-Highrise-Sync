<?php

/*
 * SyncAllAccounts, a script to sync all subscribed users of the MerchantOS-Highrise-Sync service
 * @author Erika Ellison
 */

require_once('SyncAccount.class.php');
require_once('SyncAccountDAO.class.php');

$dao = new SyncAccountDAO();

$all_accounts = $dao->getAllSyncAccounts();

foreach($all_accounts as $account) {
        $was_synced = $account->sync();
}


?>
