<?xml version="1.0" encoding="UTF-8"?>

<!--
    Document   : personToCustomer.xsl
    Created on : April 16, 2012, 12:45 PM
    Author     : Erika Ellison
    Description:
        Transforms an XML document from Highrise Person schema to MerchantOS Customer schema.
-->

<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php">
<xsl:output method="xml" encoding="UTF-8" />

    <xsl:template match="person">
    <Customer>
        <firstName><xsl:value-of select="first-name" /></firstName>
        <lastName><xsl:value-of select="last-name" /></lastName>
        <title><xsl:value-of select="title" /></title>
        <company><xsl:value-of select="company-name" /></company>
        
        <Note>
            <note><xsl:value-of select="background" /></note>
            <isPublic>true</isPublic>
        </Note>
        
        <Contact>
            <Addresses>
                <xsl:for-each select="contact-data/addresses/address">
                    <xsl:if test="location = 'Work'">
                        <ContactAddress>
                            <address1><xsl:value-of select="street" /></address1>
                            <address2></address2>
                            <city><xsl:value-of select="city" /></city>
                            <state><xsl:value-of select="state" /></state>
                            <zip><xsl:value-of select="zip" /></zip>
                            <country><xsl:value-of select="country" /></country>
                        </ContactAddress>
                    </xsl:if>
                </xsl:for-each>
            </Addresses>
            <Phones>
                <xsl:for-each select="contact-data/phone-numbers/phone-number">
                    <xsl:if test="(location != 'Skype') and (location != 'Other')">
                        <ContactPhone>
                            <number><xsl:value-of select="number" /></number>
                            <useType><xsl:value-of select="location" /></useType>
                        </ContactPhone>
                    </xsl:if>
                </xsl:for-each>
            </Phones>
            <Emails>
                <xsl:for-each select="contact-data/email-addresses/email-address">
                    <xsl:choose>
                        <xsl:when test="location = 'Work'">
                            <ContactEmail>
                                <address><xsl:value-of select="address" /></address>
                                <useType>Primary</useType>
                            </ContactEmail>
                        </xsl:when>
                        <xsl:when test="location = 'Home'">
                            <ContactEmail>
                                <address><xsl:value-of select="address" /></address>
                                <useType>Secondary</useType>
                            </ContactEmail>
                        </xsl:when>
                    </xsl:choose>
                </xsl:for-each>
            </Emails>
            <Websites>
                <xsl:for-each select="contact-data/web-addresses/web-address">
                    <xsl:if test="location = 'Work'">
                        <ContactWebsite>
                            <url><xsl:value-of select="url" /></url>
                        </ContactWebsite>
                    </xsl:if>
                </xsl:for-each>
            </Websites>
        </Contact>
    </Customer>
    </xsl:template>

</xsl:stylesheet>