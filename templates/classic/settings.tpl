{! --- defines are used by the engine and vars are used by the template --- }

{! --- How many px to indent for each level --- }
{DEFINE indentmultiplier 15}

{! --- This is used to load the message-bodies in the message-list for that template if set to 1 --- }
{DEFINE bodies_in_list 0}

{! --- This is used the number of page numbers shown on the list page in the paging section (eg. 1 2 3 4 5) --- }
{DEFINE list_pages_shown 5}

{! --- This is used the number of page numbers shown on the search page in the paging section (eg. 1 2 3 4 5) --- }
{DEFINE search_pages_shown 5}

{! --- This is the marker for messages in the thread list --- }
{VAR marker '<img src="templates/classic/images/carat.gif" border="0" width="8" height="8" alt="" />'}

{! -- This is the image to use as a delete button for recipients in PM --- }
{VAR delete_image "templates/classic/images/delete.gif"}

{! -- This is the image for the gauge bar to show how full the PM box is -- }
{VAR gauge_image "templates/classic/images/gauge.gif"}

{! --- these are the colors used in the style sheet --- }
{! --- you can use them or replace them in the style sheet --- }


{! --- common body-colors --- }
{VAR bodybackground "White"}
{VAR defaulttextcolor "Black"}
{VAR backcolor "White"}
{VAR forumwidth "100%"}
{VAR forumalign "center"}
{VAR newflagcolor "#CC0000"}
{VAR errorfontcolor "Red"}
{VAR okmsgfontcolor "DarkGreen"}

{! --- for the forum-list ... alternating colors --- }
{VAR altbackcolor "#EEEEEE"}
{VAR altlisttextcolor "#000000"}

{! --- common link-settings --- }
{VAR linkcolor "#000099"}
{VAR activelinkcolor "#FF6600"}
{VAR visitedlinkcolor "#000099"}
{VAR hoverlinkcolor "#FF6600"}

{! --- for the Navigation --- }
{VAR navbackcolor "#EEEEEE"}
{VAR navtextcolor "#000000"}
{VAR navhoverbackcolor "#FFFFFF"}
{VAR navhoverlinkcolor "#FF6600"}
{VAR navtextweight "normal"}
{VAR navfont "Lucida Sans Unicode, Lucida Grande, Arial"}
{VAR navfontsize "12px"}

{! --- for the PhorumHead ... the list-header --- }
{VAR headerbackcolor "#EEEEEE"}
{VAR headertextcolor "#000000"}
{VAR headertextweight "bold"}
{VAR headerfont "Lucida Sans Unicode, Lucida Grande, Arial"}
{VAR headerfontsize "12px"}


{VAR tablebordercolor "#808080"}

{VAR listlinecolor "#F2F2F2"}

{VAR listpagelinkcolor "#707070"}
{VAR listmodlinkcolor "#707070"}


{! --- You can set the table width globaly here ... ONLY tables, no divs are changed--- }
{VAR tablewidth "100%"}
{VAR narrowtablewidth "600px"}


{! --- Some font stuff --- }
{VAR defaultfont '"Bitstream Vera Sans", "Lucida Sans Unicode", "Lucida Grande", Arial'}
{VAR largefont '"Bitstream Vera Sans", "Trebuchet MS", Verdana, Arial, sans-serif'}
{VAR tinyfont '"Bitstream Vera Sans", Arial, sans-serif'}
{VAR fixedfont "Lucida Console, Andale Mono, Courier New, Courier"}
{VAR defaultfontsize "12px"}
{VAR defaultboldfontsize "13px"}
{VAR largefontsize "16px"}
{VAR smallfontsize "11px"}
{VAR tinyfontsize "10px"}
