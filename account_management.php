<?php


require_once('SyncAccount.class.php');
require_once('SyncAccountDAO.class.php');


define('SubmitNewHighriseCredentials', 'Submit Highrise Credentials');
define('SubmitViewExceptions', 'View Exceptions');


$mos_account_key = $_GET['mos_account_key'];
$mos_api_key = $_GET['mos_api_key'];
$mos_acct_id = $_GET['mos_acct_id'];


if (!$mos_account_key || !$mos_api_key || !$mos_acct_id) {
    echo 'Required account information not provided. 
        Please sign in to your MerchantOS account and try again.';
}
else {
    $display_exceptions = false;
    
    switch ($_GET['submit']) {
        
        case SubmitNewHighriseCredentials:
            $highrise_api_key = $_GET['highrise_api_key'];
            $highrise_username = $_GET['highrise_username']; 
            $new_account = new SyncAccount(null, $mos_account_key, 
                    $mos_api_key, $mos_acct_id,
                    $highrise_api_key, $highrise_username, 
                    null, null);
            if (!$new_account->hasValidCredentialsHighrise()) {
                echo '<p>The Highrise API credentials you entered were not valid,
                    so no changes were made to your subscription status for the MerchantOS-Highrise-Sync service.
                    Please try again below.</p>';
                displayAccountStatus(null);
            }
            else {
                $was_saved = $new_account->save();
                if (!$was_saved) {
                    echo '<p>Sorry, we could not sign you up for the MerchantOS-Highrise-Sync service. 
                        Please try again later.</p>';
                }
                else {
                    /*
                    $command = 'RunSync.php ' . $mos_account_key;
                    exec($command);
                    */
                    echo '<p>The Highrise API credentials you entered were saved successfully. 
                        There is a sync request pending for your account. It will be processed as soon as possible, 
                        and will take approximately one minute for every 100 contacts to be transferred.</p>';
                    displayAccountStatus($new_account);
                }
            }
            break;
        
    
        case SubmitViewExceptions:
            $display_exceptions = true;
            
            
        default:
            $dao = new SyncAccountDAO();
            $existing_account = $dao->getSyncAccountByMOSAccountKey($mos_account_key);
            if ($existing_account && is_object($existing_account)) {
                updateDatabase($existing_account, $mos_api_key, $mos_acct_id);
            }
            else {
                $existing_account = false;
            }
            displayAccountStatus($existing_account, $display_exceptions);
            
    }
}



/** ONLY BRANCH NOT TESTED IS LAST_SYNCED = NOT NULL
 * Displays an account status paragraph, if $existing_account is null displays message prompting sign up
 * @param SyncAccount $existing_account 
 * @param boolean $display_exceptions
 */
function displayAccountStatus($existing_account, $display_exceptions=false) {
    echo '<p>Account Status: ';

    if (!$existing_account) {
        echo '<br />You have not signed up for the MerchantOS-Highrise-Sync service.
            If you would like to sign up, please enter your Highrise API credentials below.';
        echo '</p>';
    }
    else {
        if (!$existing_account->hasValidCredentialsHighrise()) {
           echo '<br /><strong>Warning!</strong> You are signed up for the MerchantOS-Highrise-Sync service, 
                but your Highrise API credentials are not valid, so your accounts cannot be synced. 
                Please update your account with valid Highrise API credentials below.';
        }
        else {
            echo '<br />You are signed up for the MerchantOS-Highrise-Sync service,
                and we have your valid Highrise API credentials on record.';
        }

        $last_synced = $existing_account->getLastSyncedOn();
        if ($last_synced) {
            echo '<br />The account was last synced on ', $last_synced, '.';
        }
        else {
            echo '<br />The account has not yet been synced.';
        }
        
        echo '</p>';
        
        if ($display_exceptions) {
           $dao = new SyncAccountDAO();
           $exceptions_html = $dao->getExceptionsInHTML($existing_account->getID());
           echo $exceptions_html;
        }
    }
    
    
    
    displayForm($existing_account);
}


function displayForm($existing_account) {
    global $mos_account_key, $mos_api_key, $mos_acct_id;
    
    $form = '<form action="account_management.php" method="get">';
    
    $form .=   '<input type="hidden" name="mos_account_key" value="' . $mos_account_key . '" />
                <input type="hidden" name="mos_api_key" value="' . $mos_api_key . '" />
                <input type="hidden" name="mos_acct_id" value="' . $mos_acct_id . '" />';
    
    $form .=   '<input type="text" name="highrise_api_key" placeholder="Your Highrise API Key" />
                <input type="text" name="highrise_username" placeholder="Your Highrise Username" />    
                <input type="submit" name="submit" value="' . SubmitNewHighriseCredentials . '" />
                <a href="highrise_help.php"> (Help with this.)</a>';
                
    if ($existing_account) {
        $form .= '<br /><input type="submit" name="submit" value="' . SubmitViewExceptions . '" /> (';
    }

    $form .= '</form>';

echo $form;    
}


/**
 * If GET parameters for MerchantOS API credentials do not match the database values, updates database
 * @param SyncAccount $existing_account
 * @param string $mos_api_key
 * @param int $mos_acct_id 
 */
function updateDatabase($existing_account, $mos_api_key, $mos_acct_id) {
    $modified = false;
    if (!($existing_account->getMOSAPIKey() === $mos_api_key)) {
        $modified = true;
        $existing_account->setMOSAPIKey($mos_api_key);
    }
    
    if (!($existing_account->getMOSAccountID() === $mos_acct_id)) {
        $existing_account->setMOSAccountID($mos_acct_id);
        $modified = true;
    }
    
    if ($modified) {
        $was_saved = $existing_account->save();
    }
}


function displayExceptions($existing_account) {
    
    
    
}


?>