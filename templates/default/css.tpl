
    /* Element level classes */

    body
    {
        color: {defaulttextcolor};
        font-size: {defaultfontsize};
        font-family: {defaultfont};
        background-color: {bodybackground};
        margin: 8px;
    }

    td, th
    {
        color: {defaulttextcolor};
        font-size: {defaultfontsize};
        font-family: {defaultfont};
    }

    img
    {
        border-width: 0px;
        vertical-align: middle;
    }

    a
    {
        color: {linkcolor};
        text-decoration: none;
    }
    a:active
    {
        color: {activelinkcolor};
        text-decoration: none;
    }
    a:visited
    {
        color: {visitedlinkcolor};
        text-decoration: none;
    }        

    a:hover
    {
        color: {hoverlinkcolor};
    }

    input[type=text], input[type=password], input[type=file], select
    {
/*        border: 1px solid {tablebordercolor}; */

        background-color: {backcolor};
        color: {defaulttextcolor};
        font-size: {defaultfontsize};
        font-family: {defaultfont};

        vertical-align: middle;

    }

    textarea
    {
        background-color: {backcolor};
        color: {defaulttextcolor};
        font-size: {defaultfontsize};
        font-family: {fixedfont};
    }
    
	input[type=submit]
	{
        border: 1px dotted {tablebordercolor};
        background-color: {navbackcolor};
        font-size: {defaultfontsize};
        font-family: {defaultfont};
        vertical-align: middle;
	}

	input
	{
        vertical-align: middle;
	}

    /* Standard classes for use in any page */
    /* PhorumDesignDiv - a div for keeping the forum-size size */
    .PDDiv
    {
        width: {forumwidth};
        text-align: left;
    }        
    /* new class for layouting the submit-buttons in IE too */
    .PhorumSubmit { 
        border: 1px dotted {tablebordercolor}; 
        color: {defaulttextcolor}; 
        background-color: {navbackcolor}; 
        font-size: {defaultfontsize}; 
        font-family: {defaultfont}; 
        vertical-align: middle; 
    }    
    
    .PhorumTitleText
    {
        float: right;
    }

    .PhorumStdBlock
    {
        font-size: {defaultfontsize};
        font-family: {defaultfont};
        background-color: {backcolor};
        border: 1px solid {tablebordercolor};
/*        width: {tablewidth}; */
        padding: 3px;		
    }

    .PhorumStdBlockHeader
    {
        font-size: {defaultfontsize};
        font-family: {defaultfont};
        background-color: {navbackcolor};
/*        width: {tablewidth}; */
        border-left: 1px solid {tablebordercolor};
        border-right: 1px solid {tablebordercolor};
        border-top: 1px solid {tablebordercolor};
        padding: 3px;
    }

    .PhorumHeaderText
    {
        font-weight: bold;
    }

    .PhorumNavBlock
    {
        font-size: {defaultfontsize};
        font-family: {defaultfont};
        border: 1px solid {tablebordercolor};
        margin-top: 1px;
        margin-bottom: 1px;
/*        width: {tablewidth}; */
        background-color: {navbackcolor};
        padding: 2px 3px 2px 3px;
    }

    .PhorumNavHeading
    {
        font-weight: bold;
    }

    A.PhorumNavLink
    {
        color: {navtextcolor};
        text-decoration: none;
        font-weight: {navtextweight};
        font-family: {navfont};
        font-size: {navfontsize};
        border-style: solid;
        border-color: {navbackcolor};
        border-width: 1px;
        padding: 0px 4px 0px 4px;
    }

    A.PhorumNavLink:hover
    {
        background-color: {navhoverbackcolor};
        font-weight: {navtextweight};
        font-family: {navfont};
        font-size: {navfontsize};        
        border-style: solid;
        border-color: {tablebordercolor};
        border-width: 1px;
        color: {navhoverlinkcolor};
    }

    .PhorumFloatingText
    {
        padding: 10px;
    }

    .PhorumHeadingLeft
    {
        padding-left: 3px;
        font-weight: bold;
    }

    .PhorumUserError
    {
        padding: 10px;
        text-align: center;
        color: {errorfontcolor};
        font-size: {largefontsize};
        font-family: {largefont};
        font-weight: bold;
    }

   .PhorumNewFlag
    {
        font-family: {defaultfont};
        font-size: {tinyfontsize};
        font-weight: bold;
        color: {newflagcolor};
    }

    .PhorumNotificationArea
    {
        float: right;
        border-style: dotted;
        border-color: {tablebordercolor};
        border-width: 1px;
    }

    /* PSUEDO Table classes                                       */
    /* In addition to these, each file that uses them will have a */
    /* column with a style property to set its right margin       */    

    .PhorumColumnFloatXSmall
    {
        float: right; 
        width: 75px;
    }

    .PhorumColumnFloatSmall
    {
        float: right; 
        width: 100px;
    }

    .PhorumColumnFloatMedium
    {
        float: right; 
        width: 150px;
    }

    .PhorumColumnFloatLarge
    {
        float: right; 
        width: 200px;
    }

    .PhorumColumnFloatXLarge
    {
        float: right; 
        width: 400px;
    }

    .PhorumRowBlock
    {
        background-color: {backcolor};
        border-bottom: 1px solid {listlinecolor};
        padding: 5px 0px 0px 0px;
    }

    .PhorumRowBlockAlt
    {
        background-color: {altbackcolor};
        border-bottom: 1px solid {listlinecolor};
        padding: 5px 0px 0px 0px;
    }

    /************/
    

    /* All that is left of the tables */

    .PhorumStdTable
    {
        border-style: solid;
        border-color: {tablebordercolor};
        border-width: 1px;
        width: {tablewidth};
    }

    .PhorumTableHeader
    {
        background-color: {headerbackcolor};
        border-bottom-style: solid;
        border-bottom-color: {tablebordercolor};
        border-bottom-width: 1px;
        color: {headertextcolor};
        font-size: {headerfontsize};
        font-family: {headerfont};
        font-weight: {headertextweight};
        padding: 3px;
    }

    .PhorumTableRow
    {
        background-color: {backcolor};
        border-bottom-style: solid;
        border-bottom-color: {listlinecolor};
        border-bottom-width: 1px;
        color: {defaulttextcolor};
        font-size: {defaultfontsize};
        font-family: {defaultfont};
        height: 35px;
        padding: 3px;
    }
    
    .PhorumTableRowAlt
    {
        background-color: {altbackcolor};
        border-bottom-style: solid;
        border-bottom-color: {listlinecolor};
        border-bottom-width: 1px;
        color: {altlisttextcolor};
        font-size: {defaultfontsize};
        font-family: {defaultfont};
        height: 35px;
        padding: 3px;
    }    

    table.PhorumFormTable td
    {
        height: 26px;
    }

    /**********************/


    /* Read Page specifics */
    
    .PhorumReadMessageBlock
    {
        margin-bottom: 5px;
    }
    
   .PhorumReadBodySubject
    {
        color: Black;
        font-size: {largefontsize};
        font-family: {largefont};
        font-weight: bold;
        padding-left: 3px;
    }

    .PhorumReadBodyHead
    {
        padding-left: 5px;
    }

    .PhorumReadBodyText
    {
        font-size: {defaultfontsize};
        font-family: {defaultfont};
        padding: 5px;
    }

    .PhorumReadNavBlock
    {
        font-size: {defaultfontsize};
        font-family: {defaultfont};
        border-left: 1px solid {tablebordercolor};
        border-right: 1px solid {tablebordercolor};
        border-bottom: 1px solid {tablebordercolor};
/*        width: {tablewidth}; */
        background-color: {navbackcolor};
        padding: 2px 3px 2px 3px;
    }

    /********************/
    
    /* List page specifics */

    .PhorumListSubText
    {
        color: {listpagelinkcolor};
        font-size: {tinyfontsize};
        font-family: {tinyfont};
    }

    .PhorumListPageLink
    {
        color: {listpagelinkcolor};
        font-size: {tinyfontsize};
        font-family: {tinyfont};
    }

    .PhorumListSubjPrefix
    {
        font-weight: bold;
    }    

    .PhorumListModLink, .PhorumListModLink a
    {
        color: {listmodlinkcolor};
        font-size: {tinyfontsize};
        font-family: {tinyfont};
    }
    /********************/

    /* Override classes - Must stay at the end */

    .PhorumNarrowBlock
    {
        width: {narrowtablewidth};
    }

    .PhorumSmallFont
    {
        font-size: {smallfontsize};
    }    

    .PhorumLargeFont
    {
        color: {defaulttextcolor};
        font-size: {largefontsize};
        font-family: {largefont};
        font-weight: bold;
    }    


    .PhorumFooterPlug
    {
        margin-top: 10px;
        font-size: {tinyfontsize};
        font-family: {tinyfont};
    }