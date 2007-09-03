<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:import href="http://docbook.sourceforge.net/release/xsl/current/html/chunk.xsl"/>
  <xsl:param name="chunk.section.depth" select="3" />
  <xsl:param name="toc.section.depth" select="3" />
  <xsl:param name="section.autolabel" select="1" />
  <xsl:param name="section.label.includes.component.label" select="1" />
  <xsl:param name="html.stylesheet" select="'../phorum_docbook.css'"/>
  <xsl:param name="navig.showtitles" select="0" />
</xsl:stylesheet>
