<?php
//error_reporting(E_ALL);
//ini_set('display_errors', '1');


require_once('SyncAccount.class.php');
require_once('SyncAccountDAO.class.php');

// prevents typos from affecting switch/case statements
define('SubmitNewHighriseCredentials', 'Submit Highrise Credentials');
define('SubmitViewExceptions', 'View Errors');
define('ReasonBadCredentials', 'bad credentials');
define('ReasonNotSaved', 'not saved');

// $_GET array keys
define('GET_MOS_ACCOUNT_KEY', 'mos_account_key');
define('GET_MOS_API_KEY', 'mos_api_key');
define('GET_MOS_ACCOUNT_ID', 'mos_account_id');
define('GET_HIGHRISE_API_KEY', 'highrise_api_key');
define('GET_HIGHRISE_USERNAME', 'highrise_username');


main();


function main() {
    $mos_account_key = $_GET[GET_MOS_ACCOUNT_KEY];
    $mos_api_key = $_GET[GET_MOS_API_KEY];
    $mos_acct_id = $_GET[GET_MOS_ACCOUNT_ID];
    
    if (!$mos_account_key || !$mos_api_key || !$mos_acct_id) {
        displayBadGetInformation();
    }
    else {
        $existing_account = getExistingAccount($mos_account_key);
        updateDatabase($existing_account, $mos_api_key, $mos_acct_id);
        
        if (isset($_GET['submit'])) {
            switch ($_GET['submit']) {
                case SubmitNewHighriseCredentials:
                    if ($existing_account) {
                        $existing_account->setHighriseAPIKey($_GET[GET_HIGHRISE_API_KEY]);
                        $existing_account->setHighriseUsername($_GET[GET_HIGHRISE_USERNAME]);
                        attemptNewHighriseCredentials($existing_account);
                    }
                    else {
                        $new_account = new SyncAccount(null, $mos_account_key,
                            $mos_api_key, $mos_acct_id,
                            $_GET[GET_HIGHRISE_API_KEY], $_GET[GET_HIGHRISE_USERNAME]);
                        if ($new_account->hasValidCredentialsMerchantOS()) {
                            if (attemptNewHighriseCredentials($new_account)) {
                                $existing_account = $new_account;
                            }
                        }
                        else {
                            displayBadGetInformation();
                            return;
                        }
                    }
                    break;


                case SubmitViewExceptions:
                    if ($existing_account) {
                        displayExceptions($existing_account);
                    }
                    break;
            }
        }
        
        displaySubscriptionStatus($existing_account);
        displayForm($existing_account); 
    }
}


/**
 * Attempts to assign new Highrise credentials to an account
 * @param SyncAccount $account 
 * @return boolean $was_saved
 */
function attemptNewHighriseCredentials($account) {
    $was_saved = false;
    if ($account->hasValidCredentialsHighrise()) {
        $was_saved = $account->save();
        $command = '/usr/bin/php5 ./RunSync.php ' . $account->getMOSAccountKey();
        exec("$command >> /dev/null 2>&1 &");
        if ($was_saved) {
            displaySuccessfulNewCredentials();
        }
        else {
            displayUnsuccessfulNewCredentials(ReasonNotSaved);
        }
    }
    else {
        displayUnsuccessfulNewCredentials(ReasonBadCredentials);
    }
    return $was_saved;
}

/**
 * Displays an error message if the required GET parameters were not received.
 */
function displayBadGetInformation() {
    echo '<p>The information passed in through GET parameters was bad.</p>';
}


/**
 * Displays a subscription status paragraph.
 * @param SyncAccount $existing_account
 */
function displaySubscriptionStatus($existing_account=null) {
    echo '<p>MerchantOS-Highrise-Sync subscription status: ';

    if (!$existing_account) {
        displayNotSignedUp();
    }
    else {
        if (!$existing_account->hasValidCredentialsHighrise()) {
            displaySignedUpInvalidCredentials();
        }
        else {
            displaySignedUpOK();
        }
        $last_synced = $existing_account->getLastSyncedOn();
        if ($last_synced) {
            echo '<br />The account was last synced on ', $last_synced, '.';
        }
        else {
            echo '<br />The account has not yet been synced.';
        } 
    }
    echo '</p>';
}


/**
 * Displays a warning that the account is subscribed but Highrise credentials are invalid.
 */
function displaySignedUpInvalidCredentials() {
    echo '<br /><strong>Warning!</strong> You are signed up for the MerchantOS-Highrise-Sync service, 
        but your Highrise API credentials are not valid, so your accounts cannot be synced. 
        Please update your account with valid Highrise API credentials below.';
}


/**
 * Displays a message that the account is subscribed and Highrise credentials are valid.
 */
function displaySignedUpOK() {
    echo '<br />You are subscribed to the MerchantOS-Highrise-Sync service,
                and we have your valid Highrise API credentials on record.';
}


/**
 * Displays a message that the account is not subscribed. 
 */
function displayNotSignedUp() {
    echo '<br />You have not signed up for the MerchantOS-Highrise-Sync service.
            If you would like to sign up, please enter your Highrise API credentials below.';
}


/**
 * Displays a message explaining that attempted signup was unsuccessful
 * @param string $reason
 */
function displayUnsuccessfulNewCredentials($reason) {
    $no_changes_made = 'No changes were made to your subscription status for the MerchantOS-Highrise-Sync service.';
    switch ($reason) {
        case ReasonBadCredentials:
            echo '<p>The Highrise API credentials you entered were not valid. ' .
                $no_changes_made . ' Please try again below.</p>';
            break;
        
        case ReasonNotSaved:
            echo '<p>Sorry, your credentials could not be saved. ' . 
                $no_changes_made . ' Please try again later.</p>';
            break;
    }
}


/**
 * Displays a message explaining that attempted signup was successful. 
 */
function displaySuccessfulNewCredentials() {
    echo '<p>The Highrise API credentials you entered were saved successfully. 
        There is a sync request pending for your account. It will be processed as soon as possible, 
        and will take approximately one minute for every 100 contacts to be transferred.</p>';
}


/**
 * Displays any exceptions that have occured for this account
 * @param SyncAccount $existing_account 
 */
function displayExceptions($existing_account) {
    $id = $existing_account->getID();
    $dao = new SyncAccountDAO();
    $exceptions_html = $dao->getExceptionsInHTML($id);
    echo '<p>Errors:</p>', $exceptions_html;
}


/**
 * Displays a form appropriate to the subscription status of the account
 * @param SyncAccount $existing_account 
 */
function displayForm($existing_account) {   
    $form = '<form action="account_management.php" method="get">';
    
    $form .=   '<input type="hidden" name="' . GET_MOS_ACCOUNT_KEY . '" value="' . $_GET[GET_MOS_ACCOUNT_KEY] . '" />
                <input type="hidden" name="' . GET_MOS_API_KEY . '" value="' . $_GET[GET_MOS_API_KEY] . '" />
                <input type="hidden" name="' . GET_MOS_ACCOUNT_ID . '" value="' . $_GET[GET_MOS_ACCOUNT_ID] . '" />';
    
    $form .=   '<input type="text" name="highrise_api_key" placeholder="Your Highrise API Key" />
                <input type="text" name="highrise_username" placeholder="Your Highrise Username" />    
                <input type="submit" name="submit" value="' . SubmitNewHighriseCredentials . '" />
                <a href="highrise_help.php"> (Help with this.)</a>';
                
    if ($existing_account) {
        $form .= '<br /><input type="submit" name="submit" value="' . SubmitViewExceptions . '" />';
    }

    $form .= '</form>';

    echo $form;    
}


/**
 * Returns the account associated with the MerchantOS account key, or null if none exists
 * @param string $mos_account_key
 * @return SyncAccount $existing_account 
 */
function getExistingAccount($mos_account_key) {
    $dao = new SyncAccountDAO();
    $existing_account = $dao->getSyncAccountByMOSAccountKey($mos_account_key);
    if (!$existing_account || !is_object($existing_account)) {
         $existing_account = null;
    }
    return $existing_account;
}


/**
 * If GET parameters for MerchantOS API credentials do not match the database values, updates database
 * @param SyncAccount $existing_account
 * @param string $mos_api_key
 * @param int $mos_acct_id
 */
function updateDatabase($existing_account, $mos_api_key, $mos_acct_id) {
    $modified = false;
    if ($existing_account) {
        if (!($existing_account->getMOSAPIKey() === $mos_api_key)) {
            $modified = true;
            $existing_account->setMOSAPIKey($mos_api_key);
        }
        if (!($existing_account->getMOSAccountID() === $mos_acct_id)) {
            $existing_account->setMOSAccountID($mos_acct_id);
            $modified = true;
        }
        if ($modified && $existing_account->hasValidCredentialsMerchantOS()) {
            $was_saved = $existing_account->save();
        }
    }
}

?>