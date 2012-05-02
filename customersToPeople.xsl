<?xml version="1.0" encoding="UTF-8"?>

<!--
    Document   : customerToPerson.xsl
    Created on : April 9, 2012, 4:30 PM
    Author     : Erika Ellison
    Description:
        Transforms an XML document from Highrise Person schema to MerchantOS Customer schema.
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="xml" encoding="UTF-8" />

    <xsl:template match="Customers">
        <people>
            <xsl:for-each select="Customer">
                <person>
                    <first-name><xsl:value-of select="firstName" /></first-name>
                    <last-name><xsl:value-of select="lastName" /></last-name>
                    <title><xsl:value-of select="title" /></title>
                    <company-name><xsl:value-of select="company" /></company-name>
                    <xsl:if test="Note/isPublic = 'true'">
                        <background><xsl:value-of select="Note/note" /></background>
                    </xsl:if>

                    <contact-data>
                        <email-addresses>
                            <xsl:for-each select="Contact/Emails/ContactEmail">
                                <email-address>
                                    <address><xsl:value-of select="address" /></address>
                                    <location>
                                        <xsl:choose>
                                            <xsl:when test="useType = 'Primary'">Work</xsl:when>
                                            <xsl:when test="useType = 'Secondary'">Home</xsl:when>
                                            <xsl:otherwise>Other</xsl:otherwise>
                                        </xsl:choose>    
                                    </location>
                                </email-address>
                            </xsl:for-each>
                        </email-addresses>
                        <phone-numbers>
                        <xsl:for-each select="Contact/Phones/ContactPhone">
                            <phone-number>
                                <number><xsl:value-of select="number" /></number>
                                <location><xsl:value-of select="useType" /></location>
                            </phone-number>
                        </xsl:for-each>
                        </phone-numbers>
                        <addresses>
                        <xsl:for-each select="Contact/Addresses/ContactAddress">
                            <address>
                                <city><xsl:value-of select="city" /></city>
                                <country><xsl:value-of select="country" /></country>
                                <state><xsl:value-of select="state" /></state>
                                <street><xsl:value-of select="address1" />, <xsl:value-of select="address2" /></street>
                                <zip><xsl:value-of select="zip" /></zip>
                                <location>Work</location>
                            </address>
                        </xsl:for-each>
                        </addresses>
                    </contact-data>

                    <!-- custom fields -->
                    <subject_datas type="array">
                        <subject_data>
                            <subject_field_label>merchantos-customerid</subject_field_label>
                            <value><xsl:value-of select="customerID" /></value>
                        </subject_data>
                    </subject_datas>

                </person>
            </xsl:for-each>
        </people>
    </xsl:template>

</xsl:stylesheet>
