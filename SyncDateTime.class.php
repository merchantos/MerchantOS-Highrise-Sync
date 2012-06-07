<?php

/**
 * SyncDateTime, a wrapper class to abstract away formatting needs for datetimes used in MerchantOS-Highrise-Sync
 * @author Erika Ellison
 */

/**
 * SyncDateTime class
 * @author Erika Ellison 
 */
class SyncDateTime {
    
    /**
     * @var DateTime 
     */
    protected $_datetime;
    
    
    /**
     * Constructs a new SyncDateTime using the same rules as PHP DateTime
     * @param string $datetime a parsable datetime string
     */
    public function __construct($datetime=null) {
        try {
            $this->_datetime = new DateTime($datetime, new DateTimeZone('UTC'));
        }
        catch (Exception $e) {
            throw new Exception('SyncDateTime::construct error: ' . $e->getMessage());
        }
    }
    
    
    /**
     * Gets a string representing the SyncDateTime in Database format
     * @return string 
     */
    public function getDatabaseFormat() {
        // SQL type DATETIME
        return $this->_datetime->format('Y-m-d H:i:s');
    }
    
    
    /** Gets a string representing the SyncDateTime in MerchantOS API format
     * @return string
     */
    public function getMerchantOSFormat() {
        return $this->_datetime->format('c');
    }
    
    
    /**
     * Gets a string representing the SyncDateTime in Highrise API format
     * @return string 
     */
    public function getHighriseFormat() {
        // Highrise expects the format YYYYMMDDHHMMSS in GMT/UTC
        return $this->_datetime->format('YmdHis');
    }
    
    
    /** Get an int representing the SyncDateTime (seconds since Unix epoch) for easy datetime arithmetic
     * @return int 
     */
    public function getInt() {
        // returns an int representing the Unix timestamp, for easier comparisons of SyncDateTime instances
        return $this->_datetime->format('U');
    }
}

?>
