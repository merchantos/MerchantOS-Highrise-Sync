<?php

/**
 * SyncDate, a wrapper class for datetimes
 * 
 *
 * @author Erika Ellison
 */
class SyncDateTime {
    protected $_datetime;
    
    /** constructs a new SyncDateTime
     * @param string $datetime a parsable datetime string
     * if $datetime is omitted, the SyncDateTime constructed will have a datetime value of "now"
     */
    public function __construct($datetime=null) {
        $this->_datetime = new DateTime($datetime, new DateTimeZone('UTC'));
    }
    
    /** gets the SyncDateTime in Database format
     * @return string 
     */
    public function getDatabaseFormat() {
        // SQL type DATETIME
        return $this->_datetime->format('Y-m-d H:i:s');
    }
    
    /** gets the SyncDateTime in MerchantOS API format
     * @return string
     */
    public function getMerchantOSFormat() {
        // ISO 8601
        return $this->_datetime->format('c');
    }
    
    /** gets the SyncDateTime in Highrise API format
     * @return string 
     */
    public function getHighriseFormat() {
        // Highrise expects the format YYYYMMDDHHMMSS in GMT/UTC
        return $this->_datetime->format('YmdHis');
    }
    
    /** returns an int representing the SyncDateTime for easy datetime comparisons
     * @return int 
     */
    public function getInt() {
        // returns an int representing the Unix timestamp, for easier comparisons
        return $this->_datetime->format('U');
    }
}

?>
