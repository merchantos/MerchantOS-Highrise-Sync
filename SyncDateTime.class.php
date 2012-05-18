<?php

/**
 * SyncDateTime, a wrapper class for datetimes used in MerchantOS-Highrise-Sync
 * @author Erika Ellison
 */
class SyncDateTime {
    protected $_datetime;
    
    /** constructs a new SyncDateTime
     * @param string $datetime a parsable datetime string
     * if $datetime is omitted, the SyncDateTime constructed will have a datetime value of "now"
     */
    public function __construct($datetime=null) {
        try {
            $this->_datetime = new DateTime($datetime, new DateTimeZone('UTC'));
        }
        catch (Exception $e) {
            throw new Exception('SyncDateTime::construct error: ' . $e->getMessage());
        }
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
        // MerchantOS seems to treat any UTC timezone notation (Z, +00:00) as Pacific Time
        // hence this kind of kludgy fix to get an ISO-8601 string that will return the desired results
        $this->_datetime->modify('-7 hours');
        $formatted = $this->_datetime->format('c');
        $this->_datetime->modify('+7 hours');
        return $formatted;
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
        // returns an int representing the Unix timestamp, for easier comparisons of SyncDateTime instances
        return $this->_datetime->format('U');
    }
}

?>
