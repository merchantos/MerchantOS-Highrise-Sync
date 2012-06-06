<?php

/**
 * SyncAccountDAO is a data access object for SyncAccount,
 * used to save SyncAccount fields to a record in a database table, 
 * and report exceptions to another database table.
 * @author Erika Ellison
 */

require_once('SyncAccount.class.php');

/**
 * SyncAccountDAO class
 * @author Erika Ellison  
 */
class SyncAccountDAO {
    const DB_HOSTNAME = '127.0.0.1';
    const DB_USERNAME = 'root';
    const DB_PASSWORD = 'mos123';
    const DB_NAME = 'sync';
    
    const SYNC_ACCT_TABLE = 'sync_accounts';
    const EXCEPTIONS_TABLE = 'exceptions_log';
    
    const NULL_VALUE = 'NULL';

    /**
     * A connection to the MySQL database
     * @var mysqli 
     */
    protected $_mysqli;
    
    
    /** Construct a new SyncAccountDAO
     */
    public function __construct() {
        $this->_mysqli = new mysqli(self::DB_HOSTNAME, self::DB_USERNAME, self::DB_PASSWORD, self::DB_NAME);
    }
    
    
    /**
     * Logs an exception from a SyncAccount
     * @param int $sync_account_id foreign key
     * @param string/datetime $when 
     * @param string $exception_message
     * @param string $data_involved 
     * @return boolean $was_logged
     */
    public function logException($sync_account_id, $when, $exception_message, $data_involved=NULL) {
        $sync_id = $this->sqlize($sync_account_id);
        $datetime_of = $this->sqlize($when);
        $message = $this->sqlize($exception_message);
        $data = $this->sqlize($data_involved);
        
        $value_string = '(' . $sync_id . ', ' . $datetime_of . ', ' . 
                $message . ', ' . $data . ')';
        
        $query_string = 'INSERT INTO ' . self::EXCEPTIONS_TABLE . 
                ' (sync_account_id, datetime_of, message, data_involved) VALUES ' . $value_string;
        
        $was_logged = $this->_mysqli->query($query_string);
        return $was_logged;
    }
    
    
    /**
     * Get an HTML string of descriptions of exceptions; all in the database if no SyncAccount ID given
     * @param int $sync_account_id 
     * @return string $html
     */
    public function getExceptionsInHTML($sync_account_id=NULL) {
        $query_string = 'SELECT * FROM ' . self::EXCEPTIONS_TABLE;
        if ($sync_account_id) {
            $id = $this->sqlize($sync_account_id);
            $query_string .= ' WHERE sync_account_id=' . $id;
        }
        $query_string .= ' ORDER BY datetime_of';
        $result = $this->_mysqli->query($query_string);
        
        $html = '';
        while ($row = $result->fetch_assoc()) {
            $html .= $this->getParagraphFromRow($row);
        }
        
        if ($html === '') {
            $html = '<p>There are no exceptions to display.</p>';
        }
        
        return $html;
    }  
    
    
    /** Reads and returns all stored SyncAccounts
     * @return SyncAccount[] $all_accounts array of all SyncAccounts
     */
    public function getAllSyncAccounts() {
        $query_string = 'SELECT * FROM ' . self::SYNC_ACCT_TABLE;
        $result = $this->_mysqli->query($query_string);
        $all_accounts = array();
        
        while ($row = $result->fetch_assoc()) {
            $new_acct = $this->instantiateSyncAccountFromRow($row);
            $all_accounts[] = $new_acct;
        }
        return $all_accounts;
    }
    
    
    /** Returns the SyncAccount associated with the MerchantOS account key if it exists, otherwise returns false
     * @param string $mos_account_key 
     * @return mixed 
     */
    public function getSyncAccountByMOSAccountKey($mos_account_key) { 
        $query_string = 'SELECT * FROM ' . self::SYNC_ACCT_TABLE . 
                ' WHERE mos_account_key=' . $this->sqlize($mos_account_key);
        $result = $this->_mysqli->query($query_string);
        
        if ($result && ($row = $result->fetch_assoc())) {
            $sync_account = $this->instantiateSyncAccountFromRow($row);
            return $sync_account;
        }
        else {
            return false;
        }
    }
   
    /**
     * Saves the SyncAccount to the database
     * @param SyncAccount $sync_account
     * @return boolean $was_saved 
     */
    public function saveSyncAccount($sync_account) {
        $was_saved = false;
        if ($sync_account->getID() == NULL) {
            $was_saved = $this->createSyncAccount($sync_account);
        }
        else {
            $was_saved = $this->updateSyncAccount($sync_account);
        }
        return $was_saved;
    }
    
    /** Creates the SyncAccount in the database and updates the SyncAccount object with its new ID
     * @param SyncAccount $sync_account 
     * @return boolean $was_created
     */
    protected function createSyncAccount($sync_account) {
        $mos_account_key = $this->sqlize($sync_account->getMOSAccountKey());
        $mos_api_key = $this->sqlize($sync_account->getMOSAPIKey());
        $mos_acct_id = $this->sqlize($sync_account->getMOSAccountID());
        $highrise_api_key = $this->sqlize($sync_account->getHighriseAPIKey());
        $highrise_username = $this->sqlize($sync_account->getHighriseUsername());
        $custom_field_id = $this->sqlize($sync_account->getCustomFieldID());
        $last_synced_on = $this->sqlize($sync_account->getLastSyncedOn());
        
        $value_string = '(' . $mos_account_key . ', ' . 
                $mos_api_key . ', ' . $mos_acct_id . ', ' . 
                $highrise_api_key . ', ' . $highrise_username . ', ' .  
                $custom_field_id  . ', ' . $last_synced_on . ')';
        
        $query_string = 'INSERT INTO ' . self::SYNC_ACCT_TABLE . 
                ' (mos_account_key, 
                    mos_api_key, mos_account_id, 
                    highrise_api_key, highrise_username, 
                    custom_field_id, last_synced_on) VALUES ' . $value_string;

        $was_created = $this->_mysqli->query($query_string);
        
        if ($was_created) {
            $saved_account = $this->getSyncAccountByMOSAccountKey($sync_account->getMOSAccountKey());
            $new_id = $saved_account->getID();
            $sync_account->setID($new_id);
        }
        
        return $was_created;
    }
    
    
    /** Updates all fields in the SyncAccount
     * @param SyncAccount $sync_account 
     * @return boolean $was_updated
     */
    protected function updateSyncAccount($sync_account) {
        $id = $this->sqlize($sync_account->getID());
        $mos_api_key = $this->sqlize($sync_account->getMOSAPIKey());
        $mos_acct_id = $this->sqlize($sync_account->getMOSAccountID());
        $highrise_api_key = $this->sqlize($sync_account->getHighriseAPIKey());
        $highrise_username = $this->sqlize($sync_account->getHighriseUsername());
        $custom_field_id = $this->sqlize($sync_account->getCustomFieldID());
        $last_synced_on = $this->sqlize($sync_account->getLastSyncedOn());
       
        
        $query_string = 'UPDATE ' . self::SYNC_ACCT_TABLE . 
                ' SET' . 
                ' mos_api_key=' . $mos_api_key . 
                ', mos_account_id=' . $mos_acct_id . 
                ', highrise_api_key=' . $highrise_api_key . 
                ', highrise_username=' . $highrise_username . 
                ', custom_field_id=' . $custom_field_id . 
                ', last_synced_on=' . $last_synced_on .                 
                ' WHERE id=' . $id;
        
        $was_updated = $this->_mysqli->query($query_string);
        return $was_updated;
    }
    
    
    /**
     * Updates the last synced on field in the SyncAccount
     * @param SyncAccount $sync_account
     * @return boolean $was_updated
     */
    public function updateLastSyncedOn($sync_account) {
        $last_synced_on = $this->sqlize($sync_account->getLastSyncedOn());
        $id = $this->sqlize($sync_account->getID());
        
        $query_string = 'UPDATE ' . self::SYNC_ACCT_TABLE . 
                ' SET last_synced_on=' . $last_synced_on . 
                ' WHERE id=' . $id;
        
        $was_updated = $this->_mysqli->query($query_string);
        return $was_updated;
    }
    
    
    /**
     * Updates the custom field id field in the SyncAccount
     * @param SyncAccount $sync_account
     * @return boolean $was_updated
     */
    public function updateCustomFieldID($sync_account) {
        $custom_field_id = $this->sqlize($sync_account->getCustomFieldID());
        $id = $this->sqlize($sync_account->getID());
        
        $query_string = 'UPDATE ' . self::SYNC_ACCT_TABLE . 
                ' SET custom_field_id=' . $custom_field_id . 
                ' WHERE id=' . $id;
        
        $was_updated = $this->_mysqli->query($query_string);
        return $was_updated;
    }
    
        
    /**
     * Returns an HTML paragraph describing the exception represented by the database row
     * @param type $row
     * @return string $paragraph
     */
    protected function getParagraphFromRow($row) {
        $paragraph = '';
        $paragraph .= '<p>Exception ID: ' . $row['id'];
        $paragraph .= '<br />SyncAccount ID: ' . $row['sync_account_id'];
        $paragraph .= '<br />Exception occured: ' . $row['datetime_of'];
        $paragraph .= '<br />Exception message: ' . $row['message'];
        $paragraph .= '<br />Data involved: ' . htmlentities($row['data_involved']);
        $paragraph .= '</p>';
        return $paragraph;
    }
    
    
    /**
     * Creates a new instance of SyncAccount using the values in the database row
     * @param associative array $row
     * @return SyncAccount $sync_account
     */
    protected function instantiateSyncAccountFromRow($row) {
        $sync_account = new SyncAccount($row['id'], $row['mos_account_key'], 
                $row['mos_api_key'], $row['mos_account_id'], 
                $row['highrise_api_key'], $row['highrise_username'], 
                $row['custom_field_id'], $row['last_synced_on']);
        
        return $sync_account;
    }
    
    
    /**
     * Sanitizes all values, and single-quotes non-NULL values for safe and easy use in a SQL query.
     * @param string $value
     * @return string $sqlized_value
     */
    protected function sqlize($value) {
        if ($value === NULL) {
            $sqlized_value = self::NULL_VALUE;
        }
        else {
            $sqlized_value = '\'' . $this->_mysqli->real_escape_string($value) . '\'';
        }
        return $sqlized_value;
    }
    
}

?>