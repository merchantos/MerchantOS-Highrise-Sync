<?php
/**
 * TransformXML is a collection of functions used to transform SimpleXMLElements
 * to different XML schemas for MerchantOS-Highrise-Sync.
 * requirements: php5-xsl package
 *
 * @author Erika Ellison
 */

class TransformXML {
    
    /** Transforms a Customers XML document to a People XML document.
     * @param SimpleXMLElement $customers_xml a SimpleXMLElement representing a well-formed XML document in the MerchantOS Customers schema
     * @return SimpleXMLElement $people_xml a SimpleXMLElement representing a well-formed XML document in the Highrise People schema
     */
    public static function customersToPeople($customers_xml) {
        $people_xml = TransformXML::transform($customers_xml, 'customersToPeople.xsl');
        return $people_xml;
    }
    
    /** Transforms a Customers XML document to a People XML document.
     * @param SimpleXMLElement $people_xml a SimpleXMLElement representing a well-formed XML document in the Highrise People schema
     * @return SimpleXMLElement $customers_xml a SimpleXMLElement representing a well-formed XML document in the MerchantOS Customers schema
     */
    public static function peopleToCustomers($people_xml) {
        $customers_xml = TransformXML::transform($people_xml, 'peopleToCustomers.xsl');
        return $customers_xml;
    }
    
    /** appends all children elements of an XML document to another XML document
    * both XML documents should have the same root element
    * @param SimpleXMLElement $xml1
    * @param SimpleXMLElement $xml2
    * @return SimpleXMLElement $new_main
    */        
    function mergeXML($xml1, $xml2) {
        $doc1 = dom_import_simplexml($xml1)->ownerDocument;
        foreach (dom_import_simplexml($xml2)->childNodes as $child) {
            $child = $doc1->importNode($child, TRUE);
            $doc1->documentElement->appendChild($child);
        }
        $merged = simplexml_load_string($doc1->saveXML());
        return $merged;
    }
    
    /** Uses an XSLTProcessor to transform XML according to the given stylesheet
     * @param SimpleXMLElement $original_xml
     * @param string $rules_filename the pathname of an .XSL file
     * @return SimpleXMLElement $new_xml
     */
    protected static function transform($original_xml, $rules_filename) {
        $processor = new XSLTProcessor();        
        $stylesheet = simplexml_load_file($rules_filename);        
        $processor->importStylesheet($stylesheet);
        $new_xml = new SimpleXMLElement($processor->transformToXML($original_xml));
        return $new_xml;
    }
    
    
    /**
     *
     * @param SimpleXMLElement $customers
     * @param DateTime $datetime
     * @return SimpleXMLElement $customers_created
     */
    public function allCustomersCreatedSince($customers, $datetime) {
        
        return $customers_created;
    }
            
            
    /**
     *
     * @param SimpleXMLElement $customers
     * @param DateTime $datetime
     * @return SimpleXMLElement $customers_modified
     */        
    public function onlyCustomersModifiedSince($customers, $datetime) {
        
        return $customers_modified;
    }
            
            
    /**
     *
     * @param SimpleXMLElement $people
     * @param DateTime $datetime
     * @return SimpleXMLElement $people_created
     */        
    public function allPeopleCreatedSince($people, $datetime) {
        
        return $people_created;
    }
            
            
    /**
     *
     * @param SimpleXMLElement $people
     * @param DateTime $datetime
     * @return SimpleXMLElement $people_modified
     */        
    public function onlyPeopleModifiedSince($people, $datetime) {
        
        return $people_modified;
    }
    

    
    
    
}

?>
