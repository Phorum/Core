<?php

if ( !defined( "PHORUM" ) ) return;

function phorum_format_messages( $data )
{
    $PHORUM = $GLOBALS["PHORUM"];
    
    // retrieving bad-words list
    $banlists=phorum_db_get_banlists();
    if(isset($banlists[PHORUM_BAD_WORDS])) {
        $bad_words=$banlists[PHORUM_BAD_WORDS];
    } else {
        $bad_words=array();   
    }

    foreach( $data as $key => $message ) {
        // ////////////////////////////////
        
        // Work on the body
        
        if ( isset( $message["body"] ) ) {
            $body = $message["body"];
            
            // convert legacy <> urls into bare urls
            $body = preg_replace("/<((http|https|ftp):\/\/[a-z0-9;\/\?:@=\&\$\-_\.\+!*'\(\),~%]+?)>/i", "$1", $body);
            
            // htmlspecialchars does too much
            $body = str_replace( array( "&", "<", ">" ), array( "&amp;", "&lt;", "&gt;" ), $body ); 
            
            // replace newlines with <br phorum="true" /> temporarily
            // this way the mods know what Phorum did vs the user
            $body = str_replace( "\n", "<br phorum=\"true\" />\n", $body );

            if ( is_array( $bad_words ) ) {
                foreach( $bad_words as $item ) {
                    $word=$item['string'];
                    $body = preg_replace( "/\b$word(ing|ed|s|er|es)*\b/", "@#$%&", $body );
                } 
            } 

            $data[$key]["body"] = $body;
        } 
        // ////////////////////////////////
        
        // Work on the other fields
        
        // htmlspecialchars does too much
        $safe_author = str_replace( array( "<", ">" ), array( "&lt;", "&gt;" ), $message["author"] );
        if($safe_author!=$data[$key]["author"]){
            // never should have put HTML in the core
            if(isset($data[$key]["linked_author"])){
                $data[$key]["linked_author"] = str_replace($data[$key]["author"], $safe_author, $data[$key]["linked_author"]);
            }
            $data[$key]["author"] = $safe_author;
        }
        $data[$key]["author"] = str_replace( array( "<", ">" ), array( "&lt;", "&gt;" ), $message["author"] );

        $data[$key]["email"] = str_replace( array( "<", ">" ), array( "&lt;", "&gt;" ), $message["email"] );
        $data[$key]["subject"] = str_replace( array( "&", "<", ">" ), array( "&amp;", "&lt;", "&gt;" ), $message["subject"] );
    } 
    // run message formatting mods
    $data = phorum_hook( "format", $data );

    $nobr_tags = array( "pre", "xmp" ); 
    // clean up after the mods are done.
    foreach( $data as $key => $message ) {
        if ( isset( $message["body"] ) ) {
            // clean up around blockquote, pre and xmp tags so they format better in the message.
            foreach( $nobr_tags as $tagname ) {
                if ( preg_match_all( "/(<$tagname.*?>).+?(<\/$tagname>)/si", $message["body"], $matches ) ) {
                    foreach( $matches[0] as $match ) {
                        $stripped = str_replace( "<br phorum=\"true\" />", "", $match );

                        $message["body"] = str_replace( $match, $stripped, $message["body"] );
                    } 
                } 
                // fiddle with white space around quote and code tags.
                $message["body"] = preg_replace( "/\s*(<\/*(xmp|blockquote|pre).*?>)\s*/", "$1", $message["body"] );
            } 
            // normalize the <br /> tags
            $data[$key]["body"] = str_replace( "<br phorum=\"true\" />", "<br />", $message["body"] );
        } 
    } 

    return $data;
} 

function phorum_date( $picture, $ts )
{
    $PHORUM = $GLOBALS["PHORUM"];
    // setting locale
    if(!isset($PHORUM['locale']))
        $PHORUM['locale']="EN";
        
    setlocale(LC_TIME, $PHORUM['locale']); 

    if($PHORUM["user_time_zone"] && isset( $PHORUM["user"]["tz_offset"] ) && $PHORUM["user"]["tz_offset"]!=="" ){

        $ts += $PHORUM["user"]["tz_offset"] * 3600;
        return gmstrftime( $picture, $ts );

    } else {

        $ts += $PHORUM["tz_offset"] * 3600;
        return strftime( $picture, $ts );

    }

} 

function strip_body( $body )
{ 
    // strip HTML
    $body = preg_replace( "|</*[a-z][^>]*>|i", "", $body ); 
    // strip BB Code
    $body = preg_replace( "|\[/*[a-z][^\]]*\]|i", "", $body );

    return $body;
} 

?>