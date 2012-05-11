<?php
/**
 * TransformXML is a collection of functions used to transform SimpleXMLElements
 * to different XML schemas for MerchantOS-Highrise-Sync.
 * requirements: php5-xsl package
 *
 * @author Erika Ellison
 */

class XMLTransformations {
    
    /** Transforms a Customer XML document to a Person XML document.
     * @param SimpleXMLElement $customer_xml a SimpleXMLElement representing a well-formed XML document in the MerchantOS Customer schema
     * @return SimpleXMLElement $people_xml a SimpleXMLElement representing a well-formed XML document in the Highrise Person schema
     */
    public static function customerToPerson($customer_xml, $custom_field_id) {
        $person_xml = XMLTransformations::transform($customer_xml, 'customerToPerson.xsl');
        $custom_field_xml = new SimpleXMLElement('<person><subject_datas type="array"><subject_data><subject_field_id>' . 
                $custom_field_id . '</subject_field_id><value>' . 
                $customer_xml->customerID . '</value></subject_data></subject_datas></person>');
        $person_xml = XMLTransformations::mergeXML($person_xml, $custom_field_xml);
        
        return $person_xml;
    }
    
    /** Transforms a Person XML document to a Customer XML document.
     * @param SimpleXMLElement $person_xml a SimpleXMLElement representing a well-formed XML document in the Highrise Person schema
     * @return SimpleXMLElement $customer_xml a SimpleXMLElement representing a well-formed XML document in the MerchantOS Customer schema
     */
    public static function personToCustomer($person_xml) {
        $customer_xml = XMLTransformations::transform($person_xml, 'personToCustomer.xsl');
        return $customer_xml;
    }
    
    /** appends deep copies of all children elements of an XML document to another XML document
    * both XML documents should have root elements that are equivalent
    * @param SimpleXMLElement $xml1
    * @param SimpleXMLElement $xml2
    * @return SimpleXMLElement $merged
    */        
    public static function mergeXML($xml1, $xml2) {
        $updated_count = $xml1->count() + $xml2->count();
        $doc1 = dom_import_simplexml($xml1)->ownerDocument;
        foreach (dom_import_simplexml($xml2)->childNodes as $child) {
            $child = $doc1->importNode($child, TRUE);
            $doc1->documentElement->appendChild($child);
        }
        if ($doc1->documentElement->hasAttribute('count')) {
            $doc1->documentElement->setAttribute('count', $updated_count);
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
        $new_xml_string = $processor->transformToXML($original_xml);
        $new_xml = new SimpleXMLElement($new_xml_string);
        return $new_xml;
    }

}

?>
