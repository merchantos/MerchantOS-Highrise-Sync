<?php
require_once('SyncAccount.class.php');
require_once('SyncAccountDAO.class.php');

define('SubmitNewHighriseCredentials', 'Submit Highrise Credentials');
define('SubmitViewExceptions', 'View Exceptions');


$dao = new SyncAccountDAO();


// check submit type
switch ($_GET['submit']) {
    case SubmitNewHighriseCredentials:
        checkHighriseCredentials();
    case SubmitViewExceptions:
        displayExceptions();
        displayAccountStatus();

    case 0:
        $existing_account = $dao->getSyncAccountByMOSAccountKey($mos_account_key);
        if ($existing_account && is_object($existing_account)) {
            updateDatabaseValues($existing_account);
            if (!$existing_account->hasValidCredentialsHighrise()) {
                displayInvalidCredentialsWarning();
                displayHighriseCredentialsForm();
            }
            displayAccountStatus();
        }
        else {
            displayCreateAccountMessage();
            displayHighriseCredentialsForm();
        }
            
                     
}


function updateDatabaseValues($existing_account) {
    $modified = false;
    if (!($existing_account->getMOSAPIKey() === $mos_api_key) {
        $modified = true;
        $existing_account->setMOSAPIKey($mos_api_key);
    }
    if !($existing_account->getMOSAccountID() === $mos_account_id)) {
        $existing_account->setMOSAccountID($mos_account_id);
        $modified = true;
    }
    if ($modified) {
        $existing_account->save();
    }
}



?>


<form action="account_management.php" method="get">
    <fieldset id="HighriseCredentialsForm">
        <input type="text" name="highrise_api_key" placeholder="Your Highrise API Key" />
        <input type="text" name="highrise_username" placeholder="Your Highrise Username" />    
        <input type="submit" name="submit" value="Submit Highrise Credentials" onclick="validateForm"/>
    </fieldset>
    <fieldset id ="AccountManagementOptions">
        <input type="submit" name="submit" value="View Exceptions" />
        
    </fieldset>
    <fieldset id="HiddenFields">
        <input type="hidden" name="mos_account_key" value="$_GET['mos_account_key']" />
        <input type="hidden" name="mos_api_key" value="$_GET['mos_api_key']" />
        <input type="hidden" name="mos_account_id" value="$_GET['mos_account_id']" />
    </fieldset>
</form>



<script>
    function validateForm() {
        var highrise_api_key = document.forms['form']['highrise_api_key'];
        var highrise_username = document.forms['form']['highrise_username'];
        
        var message = '';
        
        if (highrise_api_key == null) || (highrise_api_key == "") {
            message = message + 'Highrise API Key field must not be blank. ';
        }
        if (highrise_username == null) || (highrise_username == "") {
            message = message + 'Highrise Username field must not be blank. ';
        }
        
        if (!message=='') {
            alert(message);
            return false;
        }
        
    }
</script>