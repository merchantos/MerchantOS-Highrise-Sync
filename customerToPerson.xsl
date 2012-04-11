<?xml version="1.0" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method='xml' />

<xsl:template match="Customer">
<person>
  <first-name><xsl:value-of select="firstName"></first-name>
  <last-name><xsl:value-of select="lastName"></last-name>
  <title><xsl:value-of select="title"></title>
  <company-name><xsl:value-of select="title"></company-name>

  

  <contact-data>
    <email-addresses>
      <xsl:for-each select="Contact/Emails/ContactEmail">
      <email-address>
        <address><xsl:value-of select="address"></address>
      </email-address>
      </xsl:for-each>
    </email-addresses>
    <phone-numbers>
      <xsl:for-each select="Contact/Phones/ContactPhone">
      <phone-number>
        <number><xsl:value-of select="number"></number>
        <location><xsl:value-of select="useType"></location>
      </phone-number>
      </xsl:for-each>
    </phone-numbers>
    <addresses>
      <xsl:for-each select="Contact/Addresses/ContactAddress">
      <address>
        <city><xsl:value-of select="city"></city>
        <country><xsl:value-of select="country"></country>
        <state><xsl:value-of select="state"></state>
        <street><xsl:value-of select="address1"><xsl:value-of select="address2"></street>
        <zip><xsl:value-of select="zip"></zip>
      </address>
      </xsl:for-each>
    </addresses>
  </contact-data>

  <xsl:if test="Note/isPublic = true">
  <background><xsl:value-of select="Note/note"></background>
  </xsl:if>

  <!-- custom fields -->
  <subject_datas type="array">
    <subject_data>
      <value>Chicago</value>
      <subject_field_id type="integer">2</subject_field_id>
    </subject_data>	
  </subject_datas>

</person>
</xsl:template>

</xsl:stylesheet>
