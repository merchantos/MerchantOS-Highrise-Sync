<?php

/**
 * Database provides a wrapper for the mySQL database used by MerchantOS-Highrise-Sync
 *
 * @author mos
 */
class Database {
    const DB_HOSTNAME = '127.0.0.1';
    const DB_USERNAME = 'root';
    const DB_PASSWORD = 'mos123';
    const DB_NAME = 'sync';

    private $_mysqli;
    private $_all_sync_accounts;
    
    
    /** Constructs a new Database object
     */
    public function __construct() {
        $this->_mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_NAME);
    }
 
    
    /** gets the next sync account in the table
     * will return NULL if there are no more sync accounts
     * @return array $next_row an associative array representing the next record
     */
    public function getNextSyncAccount() {
        if (!(isset($this->_all_sync_accounts))) {
            $this->_all_sync_accounts = $this->_mysqli->query('SELECT * FROM sync_accounts');
        }
        $next_row = $this->_all_sync_accounts->fetch_assoc();
        return $next_row;
    }
    
    public function updateLastSyncedOn($account_id, $last_synced_on) {
        $this->_mysqli->query('UPDATE sync_accounts SET last_synced_on=' . $last_synced_on . ' WHERE id=' . $account_id);
    }
    
}

?>
