<?php
// cvs-info: $Id: mysql.php,v 1.256.2.17 2005/02/28 17:49:00 brian Exp $

if (!defined("PHORUM")) return;

/**
 * The other Phorum code does not care how the messages are stored.
 *    The only requirement is that they are returned from these functions
 *    in the right way.  This means each database can use as many or as
 *    few tables as it likes.  It can store the fields anyway it wants.
 *    The only thing to worry about is the table_prefix for the tables.
 *    all tables for a Phorum install should be prefixed with the
 *    table_prefix that will be entered in include/db/config.php.  This
 *    will allow multiple Phorum installations to use the same database.
 */

/**
 * These are the table names used for this database system.
 */

$PHORUM["settings_table"] = "{$PHORUM['DBCONFIG']['table_prefix']}_settings";
$PHORUM["forums_table"] = "{$PHORUM['DBCONFIG']['table_prefix']}_forums";
$PHORUM["message_table"] = "{$PHORUM['DBCONFIG']['table_prefix']}_messages";
$PHORUM["user_table"] = "{$PHORUM['DBCONFIG']['table_prefix']}_users";
$PHORUM["user_permissions_table"] = "{$PHORUM['DBCONFIG']['table_prefix']}_user_permissions";
$PHORUM["user_newflags_table"] = "{$PHORUM['DBCONFIG']['table_prefix']}_user_newflags";
$PHORUM["subscribers_table"] = "{$PHORUM['DBCONFIG']['table_prefix']}_subscribers";
$PHORUM["groups_table"] = "{$PHORUM['DBCONFIG']['table_prefix']}_groups";
$PHORUM["forum_group_xref_table"] = "{$PHORUM['DBCONFIG']['table_prefix']}_forum_group_xref";
$PHORUM["user_group_xref_table"] = "{$PHORUM['DBCONFIG']['table_prefix']}_user_group_xref";
$PHORUM["files_table"] = "{$PHORUM['DBCONFIG']['table_prefix']}_files";
$PHORUM["banlist_table"] = "{$PHORUM['DBCONFIG']['table_prefix']}_banlists";
$PHORUM["private_message_table"] = "{$PHORUM['DBCONFIG']['table_prefix']}_private_messages";

/*
* fields which are always strings, even if they contain only numbers
* used in post-message and update-message, otherwise strange things happen
*/
$PHORUM['string_fields']= array('author', 'subject', 'body', 'email');

/**
 * This function executes a query to select messages from the database
 * and returns an array.  The main Phorum code handles actually sorting
 * the threads into a threaded list if needed.
 *
 * NOTE: ALL dates should be returned as Unix timestamps
 * NOTE: $show_special should always be true for non-threaded forums
 */

function phorum_db_get_thread_list($offset)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($offset, "int");

    $conn = phorum_db_mysql_connect();

    $table = $PHORUM["message_table"];

    $arr = array();
    $keyids = array();

    if($PHORUM["float_to_top"]){
            $sortfield = "modifystamp";
            $index = "list_page_float";
    } else{
            $sortfield = "thread";
            $index = "list_page_flat";
    }    
    
    if(isset($PHORUM['TMP']['bodies_in_list']) && $PHORUM['TMP']['bodies_in_list'] == 1) {
        $bodystr=",$table.body";
    } else {
        $bodystr="";
    }
    
    if($PHORUM["threaded_list"]){
        $limit = $PHORUM['list_length_threaded'];
        $start = $offset * $PHORUM["list_length_threaded"];
        
        $sortorder = "sort, $sortfield desc, message_id";
        
        
        $offset_option="$sortfield > 0 and";
        
        // get the announcements and stickies
        $sql="select thread as keyid from $table where sort=0 or (parent_id=0 and sort=1 and forum_id={$PHORUM['forum_id']}) order by sort, thread desc";
        $res = mysql_query($sql, $conn);
            
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

        $rows=mysql_num_rows($res);

        if(empty($offset) && $rows > 0){
            while($rec = mysql_fetch_assoc($res)){
                if(!empty($rec["keyid"])) $keyids[$rec["keyid"]] = $rec["keyid"];
            }

            $limit-=count($keyids);
        } else {
            $start-=$rows;
        }

        
        if($limit>0){

             $sql = "select
                        thread as keyid
                    from
                        $table use index ($index)
                    where
                        modifystamp>0 and
                        forum_id={$PHORUM['forum_id']} and
                        parent_id=0 and
                        status=".PHORUM_STATUS_APPROVED." and
                        sort>1
                    order by
                        $sortfield desc
                    limit $start, $limit";
    
            $res = mysql_query($sql, $conn);

            if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

            if (mysql_num_rows($res) > 0){
            	while($rec = mysql_fetch_assoc($res)){
            		$keyids[$rec["keyid"]] = $rec["keyid"];
            	}

            }
        }
        
        if(count($keyids)>0){

            $sql = "select
                $table.author,
                $table.datestamp,
                $table.email,
                $table.message_id,
                $table.meta,
                $table.moderator_post,
                $table.modifystamp,
                $table.parent_id,
                $table.sort,
                $table.status,
                $table.subject,
                $table.thread,
                $table.thread_count,
                $table.user_id,
                $table.viewcount,
                $table.closed
                $bodystr
              from
                $table
              where
                thread in (" . implode(",", $keyids) . ")
              order by
                $sortorder";

            $res = mysql_query($sql, $conn);

            if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

            while ($rec = mysql_fetch_assoc($res)){
                if($rec["status"]==PHORUM_STATUS_APPROVED){
                    $arr[$rec["message_id"]] = $rec;
                    $arr[$rec["message_id"]]["meta"] = array();
                    if(!empty($rec["meta"])){
                        $arr[$rec["message_id"]]["meta"] = unserialize($rec["meta"]);
                    }
                }
            }

        }

    } else {
        $limit = $PHORUM['list_length_flat'];
        $start = $offset * $PHORUM["list_length_flat"];

        // get the announcements and stickies
        $sql="select 
                $table.author,
                $table.datestamp,
                $table.email,
                $table.message_id,
                $table.meta,
                $table.moderator_post,
                $table.modifystamp,
                $table.parent_id,
                $table.sort,
                $table.status,
                $table.subject,
                $table.thread,
                $table.thread_count,
                $table.user_id,
                $table.viewcount,
                $table.closed
                $bodystr        
              from 
                $table 
              where 
                sort=0 or (parent_id=0 and sort=1 and forum_id={$PHORUM['forum_id']}) 
              order by sort, $sortfield desc";

        $res = mysql_query($sql, $conn);

        $rows = mysql_num_rows($res);

        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

        if(empty($offset) && $rows > 0){
            while ($rec = mysql_fetch_assoc($res)){
                if($rec["status"]==PHORUM_STATUS_APPROVED){
                    $arr[$rec["message_id"]] = $rec;
                    $arr[$rec["message_id"]]["meta"] = array();
                    if(!empty($rec["meta"])){
                        $arr[$rec["message_id"]]["meta"] = unserialize($rec["meta"]);
                    }
                }
            }

            $limit-=count($arr);
        } else {
            $start-=$rows;
        }

        if($limit>0){
            $sql = "select
                        $table.author,
                        $table.datestamp,
                        $table.email,
                        $table.message_id,
                        $table.meta,
                        $table.moderator_post,
                        $table.modifystamp,
                        $table.parent_id,
                        $table.sort,
                        $table.status,
                        $table.subject,
                        $table.thread,
                        $table.thread_count,
                        $table.user_id,
                        $table.viewcount,
                        $table.closed
                        $bodystr 
                    from
                        $table use index ($index)
                    where
                        $sortfield>0 and
                        forum_id={$PHORUM['forum_id']} and
                        parent_id=0 and
                        status=".PHORUM_STATUS_APPROVED." and
                        sort>1
                    order by
                        $sortfield desc
                    limit $start, $limit";

            $res = mysql_query($sql, $conn);
    
            if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
            while ($rec = mysql_fetch_assoc($res)){
                if($rec["status"]==PHORUM_STATUS_APPROVED){
                    $arr[$rec["message_id"]] = $rec;
                    $arr[$rec["message_id"]]["meta"] = array();
                    if(!empty($rec["meta"])){
                        $arr[$rec["message_id"]]["meta"] = unserialize($rec["meta"]);
                    }
                }
            }

        }
    }

    return $arr;
}


/**
 * This function executes a query to get the recent messages for
 * all forums the user can read, a particular forum, or a particular
 * thread, and and returns an array of the messages order by message_id.
 *
 * In reality, this function is not used in the Phorum core as of the time
 * of its creationg.  However, several modules have been written that created
 * a function like this.  Therefore, it has been added to aid in module development
 *
 * The bulk of this function came from Jim Winstead of mysql.com
 */
function phorum_db_get_recent_messages($count, $forum_id = 0, $thread = 0)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($count, "int");
    settype($forum_id, "int");
    settype($thread, "int");
    
    $conn = phorum_db_mysql_connect();

    // are we really allowed to show this thread/message?
    $approvedval = "";
    if(!phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES)) {
        $approvedval="AND {$PHORUM['message_table']}.status = ".PHORUM_STATUS_APPROVED;
    }

    $sql = "SELECT {$PHORUM['message_table']}.* FROM {$PHORUM['message_table']} WHERE status=".PHORUM_STATUS_APPROVED;

    // have to check what forums they can read first.
    // even if $thread is passed, we have to make sure
    // the user can read the forum
    $allowed_forums=phorum_user_access_list(PHORUM_USER_ALLOW_READ);

    // if they are not allowed to see any forums or the one requested, return the emtpy $arr;
    if(empty($allowed_forums) || ($forum_id>0 && !in_array($forum_id, $allowed_forums)) ) return $arr;

    if($forum_id!=0){
        $sql.=" and forum_id=$forum_id";
    } else {
        $sql.=" and forum_id in (".implode(",", $allowed_forums).")";
    }

    if($thread){
        $sql.=" and thread=$thread";
    }
    
    $sql.= " ORDER BY message_id DESC LIMIT $count";

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $arr = array();

    while ($rec = mysql_fetch_assoc($res)){
        $arr[$rec["message_id"]] = $rec;

        // convert meta field
        if(empty($rec["meta"])){
            $arr[$rec["message_id"]]["meta"]=array();
        } else {
            $arr[$rec["message_id"]]["meta"]=unserialize($rec["meta"]);
        }
        if(empty($arr['users'])) $arr['users']=array();
        if($rec["user_id"]){
            $arr['users'][]=$rec["user_id"];
        }

    }

    return $arr;
}


/**
 * This function executes a query to select messages from the database
 * and returns an array.  The main Phorum code handles actually sorting
 * the threads into a threaded list if needed.
 *
 * NOTE: ALL dates should be returned as Unix timestamps
 * @param forum - the forum id to work with. 0 for all forums.
                  You can also pass an array of forum_id's.
 * (which is the current forum the user is looking at)
 */

function phorum_db_get_unapproved_list($forum, $waiting_only=false)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    $table = $PHORUM["message_table"];

    $arr = array();

    $sql = "select
            $table.*
          from
            $table
          where";


     if (is_array($forum)){
        $sql .= " forum_id in (" . implode(",", $forum) . ")";
     }
     elseif ($forum > 0){
        settype($forum, "int");
        $sql .= " forum_id = $forum";
     }

    if($waiting_only){
        $sql.=" and status=".PHORUM_STATUS_HOLD;
    } else {
        $sql="($sql and status=".PHORUM_STATUS_HOLD.") union ($sql and status=".PHORUM_STATUS_HIDDEN.")";
    }


     $sql .=" order by thread, message_id";

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    while ($rec = mysql_fetch_assoc($res)){
        $arr[$rec["message_id"]] = $rec;
        $arr[$rec["message_id"]]["meta"] = array();
        if(!empty($rec["meta"])){
            $arr[$rec["message_id"]]["meta"] = unserialize($rec["meta"]);
        }
    }

    return $arr;
}


/**
 * This function posts a message to the tables.
 * The message is passed by reference and message_id and thread are filled
 */

function phorum_db_post_message(&$message,$convert=false){
    $PHORUM = $GLOBALS["PHORUM"];
    $table = $PHORUM["message_table"];

    $conn = phorum_db_mysql_connect();

    $success = false;

    foreach($message as $key => $value){
        if (is_numeric($value) && !in_array($key,$PHORUM['string_fields'])){
            $message[$key] = (int)$value;
        } elseif(is_array($value)) {
            $message[$key] = mysql_escape_string(serialize($value));
        } else{
            $message[$key] = mysql_escape_string($value);
        }
    }

    if(!$convert)
        $NOW = time();
    else
        $NOW = $message['datestamp'];

    // duplicate-check
    if(isset($PHORUM['check_duplicate']) && $PHORUM['check_duplicate'] && !$convert) {
        // we check for dupes in that number of minutes
        $check_minutes=60;
        $check_timestamp =$NOW - ($check_minutes*60);
        // check_query
        $chk_query="SELECT message_id FROM $table WHERE forum_id = {$message['forum_id']} AND author='{$message['author']}' AND subject='{$message['subject']}' AND body='{$message['body']}' AND datestamp > $check_timestamp";
        $res = mysql_query($chk_query, $conn);
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
        if(mysql_num_rows($res))
            return 0;
    }

    if(isset($message['meta'])){
        $metaval=",meta='{$message['meta']}'";
    } else {
        $metaval="";
    }
    
    $sql = "Insert into $table set
            forum_id = {$message['forum_id']},
            datestamp=$NOW,
            thread={$message['thread']},
            parent_id={$message['parent_id']},
            author='{$message['author']}',
            subject='{$message['subject']}',
            email='{$message['email']}',
            ip='{$message['ip']}',
            user_id={$message['user_id']},
            moderator_post={$message['moderator_post']},
            status={$message['status']},
            sort={$message['sort']},
            msgid='{$message['msgid']}',
            body='{$message['body']}',
            closed={$message['closed']}
            $metaval";

    // if in conversion we need the message-id too    
    if($convert) {
        $sql.=",message_id=".$message['message_id'];
    }

    if(isset($message['viewcount'])) {
        $sql.=",viewcount=".$message['viewcount'];
    }


    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    if ($res){
        $message["message_id"] = mysql_insert_id($conn);

        if(!empty($message["message_id"])){

            $message["datestamp"]=$NOW;

            if ($message["thread"] == 0){
                $message["thread"] = $message["message_id"];
                $sql = "update $table set thread={$message['message_id']} where message_id={$message['message_id']}";
                $res = mysql_query($sql, $conn);
                if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
            }

            if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

            $success = true;
            // some data for later use, i.e. email-notification
            $GLOBALS['PHORUM']['post_returns']['message_id']=$message["message_id"];
            $GLOBALS['PHORUM']['post_returns']['thread_id']=$message["thread"];            
        }
    }

    return $success;
}

/**
 * This function deletes messages from the messages table.
 *
 * @param message $ _id the id of the message which should be deleted
 * mode the mode of deletion, 0 for reconnecting the children, 1 for deleting the children
 */

function phorum_db_delete_message($message_id, $mode = 0)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    settype($message_id, "int");

    // lock the table so we don't leave orphans.
    mysql_query("LOCK TABLES {$PHORUM['message_table']} WRITE", $conn);

    $threadset = 0;
    // get the parents of the message to delete.
    $sql = "select forum_id, message_id, thread, parent_id from {$PHORUM['message_table']} where message_id = $message_id ";
    $res = mysql_query($sql, $conn);
    $rec = mysql_fetch_assoc($res);

    if($mode == PHORUM_DELETE_TREE){
        $mids = phorum_db_get_messagetree($message_id, $rec['forum_id']);
    }else{
        $mids = $message_id;
    }

    $thread = $rec['thread'];
    if($thread == $message_id && $mode == PHORUM_DELETE_TREE){
        $threadset = 1;
    }else{
        $threadset = 0;
    }

    if($mode == PHORUM_DELETE_MESSAGE){
        $count = 1;
        // change the children to point to their parent's parent
        // forum_id is in here for speed by using a key only
        $sql = "update {$PHORUM['message_table']} set parent_id=$rec[parent_id] where forum_id=$rec[forum_id] and parent_id=$rec[message_id]";
        mysql_query($sql, $conn);
    }else{
        $count = count(explode(",", $mids));
    }

    // delete the messages
    $sql = "delete from {$PHORUM['message_table']} where message_id in ($mids)";
    mysql_query($sql, $conn);

    // clear the lock
    mysql_query("UNLOCK TABLES", $conn);

    // it kind of sucks to have this here, but it is the best way
    // to ensure that it gets done if stuff is deleted.
    // leave this include here, it needs to be conditional
    include_once("./include/thread_info.php");
    phorum_update_thread_info($thread);
    
    // we need to delete the subscriptions for that thread too
    $sql = "DELETE FROM {$PHORUM['subscribers_table']} WHERE forum_id > 0 AND thread=$thread";
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");    

    // this function will be slow with a lot of messages.
    phorum_db_update_forum_stats(true);

    return explode(",", $mids);
}

/**
 * gets all attached messages to a message
 *
 * @param id $ id of the message
 */
function phorum_db_get_messagetree($parent_id, $forum_id){
    $PHORUM = $GLOBALS["PHORUM"];

    settype($parent_id, "int");
    settype($forum_id, "int");

    $conn = phorum_db_mysql_connect();

    $sql = "Select message_id from {$PHORUM['message_table']} where forum_id=$forum_id and parent_id=$parent_id";

    $res = mysql_query($sql, $conn);

    $tree = "$parent_id";

    while($rec = mysql_fetch_row($res)){
        $tree .= "," . phorum_db_get_messagetree($rec[0],$forum_id);
    }

    return $tree;
}

/**
 * This function updates the message given in the $message array for
 * the row with the given message id.  It returns non 0 on success.
 */

function phorum_db_update_message($message_id, $message)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($message_id, "int");

    if (count($message) > 0){
        $conn = phorum_db_mysql_connect();

        foreach($message as $field => $value){
            if (is_numeric($value) && !in_array($field,$PHORUM['string_fields'])){
                $fields[] = "$field=$value";
            }elseif (is_array($value)){
                $fields[] = "$field='".mysql_escape_string(serialize($value))."'";
            }else{
                $value = mysql_escape_string($value);
                $fields[] = "$field='$value'";
            }
        }

        $sql = "update {$PHORUM['message_table']} set " . implode(", ", $fields) . " where message_id=$message_id";
        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

        return ($res > 0) ? true : false;
    }else{
        trigger_error("\$message cannot be empty in phorum_update_message()", E_USER_ERROR);
    }
}


/**
 * This function executes a query to get the row with the given value
 * in the given field and returns the message in an array.
 */

function phorum_db_get_message($value, $field="message_id")
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($message_id, "int");

    $conn = phorum_db_mysql_connect();

    $forum_id_check = "";
    if (!empty($PHORUM["forum_id"])){
        $forum_id_check = "(forum_id = {$PHORUM['forum_id']} OR forum_id=0) and";
    }

    $value=mysql_escape_string($value);
    $field=mysql_escape_string($field);

    $sql = "select {$PHORUM['message_table']}.* from {$PHORUM['message_table']} where $forum_id_check $field='$value'";

    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $rec=array();

    if(mysql_num_rows($res)){

        $rec = mysql_fetch_assoc($res);
    
        // convert meta field
        if(empty($rec["meta"])){
            $rec["meta"]=array();
        } else {
            $rec["meta"]=unserialize($rec["meta"]);
        }
    }

    return $rec;
}

/**
 * This function executes a query to get the rows with the given thread
 * id and returns an array of the message.
 */

function phorum_db_get_messages($thread,$page=0)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($thread, "int");

    $conn = phorum_db_mysql_connect();

    $forum_id_check = "";
    if (!empty($PHORUM["forum_id"])){
        $forum_id_check = "(forum_id = {$PHORUM['forum_id']} OR forum_id=0) and";
    }

    // are we really allowed to show this thread/message?
    $approvedval = "";
    if(!phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES)) {
        $approvedval="AND {$PHORUM['message_table']}.status =".PHORUM_STATUS_APPROVED;
    }

    if($page > 0) {
           $start=$PHORUM["read_length"]*($page-1);
           $sql = "select {$PHORUM['message_table']}.* from {$PHORUM['message_table']} where $forum_id_check thread=$thread $approvedval order by message_id LIMIT $start,".$PHORUM["read_length"];
    } else {
           $sql = "select {$PHORUM['message_table']}.* from {$PHORUM['message_table']} where $forum_id_check thread=$thread $approvedval order by message_id";
    }

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $arr = array();

    while ($rec = mysql_fetch_assoc($res)){
        $arr[$rec["message_id"]] = $rec;

        // convert meta field
        if(empty($rec["meta"])){
            $arr[$rec["message_id"]]["meta"]=array();
        } else {
            $arr[$rec["message_id"]]["meta"]=unserialize($rec["meta"]);
        }
        if(empty($arr['users'])) $arr['users']=array();
        if($rec["user_id"]){
            $arr['users'][]=$rec["user_id"];
        }

    }

    if(count($arr) && $page != 0) {
        // selecting the thread-starter
        $sql = "select {$PHORUM['message_table']}.* from {$PHORUM['message_table']} where $forum_id_check message_id=$thread $approvedval";
        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
		if(mysql_num_rows($res) > 0) {
	        $rec = mysql_fetch_assoc($res);
	        $arr[$rec["message_id"]] = $rec;
	        $arr[$rec["message_id"]]["meta"]=unserialize($rec["meta"]);
		}
    }
    return $arr;
}

/**
 * this function returns the index of a message in a thread
 */
function phorum_db_get_message_index($thread=0,$message_id=0) {
    $PHORUM = $GLOBALS["PHORUM"];

    // check for valid values
    if(empty($message_id) || empty($message_id)) {
        return 0;
    }

    settype($thread, "int");
    settype($message_id, "int");

    $approvedval="";
    $forum_id_check="";

    $conn = phorum_db_mysql_connect();

    if (!empty($PHORUM["forum_id"])){
        $forum_id_check = "(forum_id = {$PHORUM['forum_id']} OR forum_id=0) AND";
    }

    if(!phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES)) {
        $approvedval="AND {$PHORUM['message_table']}.status =".PHORUM_STATUS_APPROVED;
    }

    $sql = "select count(*) as msg_index from {$PHORUM['message_table']} where $forum_id_check thread=$thread $approvedval AND message_id <= $message_id order by message_id";

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $rec = mysql_fetch_assoc($res);

    return $rec['msg_index'];
}

/**
 * This function searches the database for the supplied search
 * criteria and returns an array with two elements.  One is the count
 * of total messages that matched, the second is an array of the
 * messages from the results based on the $start (0 base) given and
 * the $length given.
 */

function phorum_db_search($search, $offset, $length, $match_type, $match_date, $match_forum, $body, $author, $subject){
    $PHORUM = $GLOBALS["PHORUM"];

    $start = $offset * $PHORUM["list_length"];

    $arr = array("count" => 0, "rows" => array());

    $conn = phorum_db_mysql_connect();

    $sql = "select count(*) as count from {$PHORUM['message_table']}";

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $total_messages = mysql_result($res, 0, "count");
    
    $search = mysql_escape_string($search);
    

    if($match_type=="PHRASE"){
        $terms = array($search);
    } else {
        $terms = preg_split("/\s+/", $search);
    }
        
    foreach($terms as $term){
        $fields=array();
        if($body) $fields[]="body like '%$term%'";
        if($author) $fields[]="author like '%$term%'";
        if($subject) $fields[]="subject like '%$term%'";
        if($fields){
            $clause[] = "( ".implode(" or ", $fields)." )";
        } else {
            return $arr;
        }
    }

    $conj = ($match_type=="ALL") ? "and" : "or";

    $sql = "select message_id from {$PHORUM['message_table']} where " . implode(" $conj ", $clause);

    if($match_date>0){
        $ts=time()-86400*$match_date;
        $sql.=" and datestamp>=$ts";
    }

    // have to check what forums they can read first.
    $allowed_forums=phorum_user_access_list(PHORUM_USER_ALLOW_READ);
    if(empty($allowed_forums) || ($PHORUM['forum_id']>0 && !in_array($PHORUM['forum_id'], $allowed_forums)) ) return $arr;

    if($PHORUM['forum_id']!=0 && $match_forum!="ALL"){
        $sql.=" and forum_id={$PHORUM['forum_id']}";
    } else {
        // if they are not allowed to search any forums, return the emtpy $arr;
        $sql.=" and forum_id in (".implode(",", $allowed_forums).")";
    }

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    if (mysql_num_rows($res)){
        while ($rec = mysql_fetch_row($res)){
            $total_ids[] = $rec[0];
        }
        // don't worry about how many ids we have here.
        $sql = "select message_id from {$PHORUM['message_table']} where message_id in (" . implode(",", $total_ids) . ") order by datestamp desc";

        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

        if (mysql_data_seek($res, $start)){
            $ids = array();

            while (($rec = mysql_fetch_row($res)) && count($ids) < $length){
                $ids[] = $rec[0];
            }
            // don't worry about how many ids we have here.
            $sql = "select {$PHORUM['message_table']}.* from {$PHORUM['message_table']} where message_id in (" . implode(",", $ids) . ") order by datestamp desc";

            $res = mysql_query($sql, $conn);
            if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

            $rows = array();

            while ($rec = mysql_fetch_assoc($res)){
                $rows[$rec["message_id"]] = $rec;
            }

            $arr = array("count" => count($total_ids), "rows" => $rows, "total" => $total_messages);
        }
    }

    return $arr;
}

/**
 * This function returns the closest thread that is greater than $thread
 */

function phorum_db_get_newer_thread($key){
    $PHORUM = $GLOBALS["PHORUM"];

    settype($key, "int");

    $conn = phorum_db_mysql_connect();

    $keyfield = ($PHORUM["float_to_top"]) ? "modifystamp" : "thread";
	
    // are we really allowed to show this thread/message?
    $approvedval = "";
    if(!phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES) && $PHORUM["moderation"] == PHORUM_MODERATE_ON) {
        $approvedval="AND {$PHORUM['message_table']}.status =".PHORUM_STATUS_APPROVED;
    }	

    $sql = "select thread from {$PHORUM['message_table']} where forum_id={$PHORUM['forum_id']} and $keyfield>$key $approvedval order by $keyfield limit 1";

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    return (mysql_num_rows($res)) ? mysql_result($res, 0, "thread") : 0;
}

/**
 * This function returns the closest thread that is less than $thread
 */

function phorum_db_get_older_thread($key){
    $PHORUM = $GLOBALS["PHORUM"];

    settype($key, "int");

    $conn = phorum_db_mysql_connect();

    $keyfield = ($PHORUM["float_to_top"]) ? "modifystamp" : "thread";
    // are we really allowed to show this thread/message?
    $approvedval = "";
    if(!phorum_user_access_allowed(PHORUM_USER_ALLOW_MODERATE_MESSAGES) && $PHORUM["moderation"] == PHORUM_MODERATE_ON) {
        $approvedval="AND {$PHORUM['message_table']}.status=".PHORUM_STATUS_APPROVED;
    }	

    $sql = "select thread from {$PHORUM['message_table']} where forum_id={$PHORUM['forum_id']} and $keyfield<$key $approvedval order by $keyfield desc limit 1";

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    return (mysql_num_rows($res)) ? mysql_result($res, 0, "thread") : 0;
}

/**
 * This function executes a query to get bad items of type $type and
 * returns an array of the results.
 */

function phorum_db_load_settings(){
    global $PHORUM;
    

    $conn = phorum_db_mysql_connect();

    $sql = "select * from {$PHORUM['settings_table']}";

    $res = mysql_query($sql, $conn);
    if(!$res && !defined("PHORUM_ADMIN")){
        if (mysql_errno($conn)==1146){
            // settings table does not exist
            return;   
        } elseif(($err = mysql_error())){
            phorum_db_mysql_error("$err: $sql");
        }
    }
    
    if (empty($err) && $res){
        while ($rec = mysql_fetch_assoc($res)){
            if ($rec["type"] == "V"){
                if ($rec["data"] == 'true'){
                    $val = true;
                }elseif ($rec["data"] == 'false'){
                    $val = false;
                }elseif (is_numeric($rec["data"])){
                    $val = $rec["data"];
                }else{
                    $val = "$rec[data]";
                }
            }else{
                $val = unserialize($rec["data"]);
            }

            $PHORUM[$rec['name']]=$val;
            $PHORUM['SETTINGS'][$rec['name']]=$val;
        }
    }
}

/**
 * This function executes a query to get bad items of type $type and
 * returns an array of the results.
 */

function phorum_db_update_settings($settings){
    global $PHORUM;

    if (count($settings) > 0){
        $conn = phorum_db_mysql_connect();

        foreach($settings as $field => $value){
            if (is_numeric($value)){
                $type = 'V';
            }elseif (is_string($value)){
                $value = mysql_escape_string($value);
                $type = 'V';
            }else{
                $value = mysql_escape_string(serialize($value));
                $type = 'S';
            }

            $sql = "replace into {$PHORUM['settings_table']} set data='$value', type='$type', name='$field'";
            $res = mysql_query($sql, $conn);
            if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
        }

        return ($res > 0) ? true : false;
    }else{
        trigger_error("\$settings cannot be empty in phorum_db_update_settings()", E_USER_ERROR);
    }
}

/**
 * This function executes a query to select all forum data from
 * the database for a flat/collapsed display and returns the data in
 * an array.
 */

function phorum_db_get_forums($forum_ids = 0, $parent_id = null){
    $PHORUM = $GLOBALS["PHORUM"];

    settype($forums_id, "int");
    settype($parent_id, "int");

    $conn = phorum_db_mysql_connect();

    if (is_array($forum_ids)) $forum_ids = implode(",", $forum_ids);

    $sql = "select * from {$PHORUM['forums_table']} ";
    if ($forum_ids){
        $sql .= " where forum_id in ($forum_ids)";
    } elseif (func_num_args() > 1) {
        $sql .= " where parent_id = $parent_id";
        if(!defined("PHORUM_ADMIN")) $sql.=" and active=1";
    }

    $sql .= " order by display_order ASC, name";

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $forums = array();

    while ($row = mysql_fetch_assoc($res)){
        $forums[$row["forum_id"]] = $row;
    }

    return $forums;
}

/**
 * This function updates the forums stats.  If refresh is true, it pulls the
 * numbers from the table.
 */

function phorum_db_update_forum_stats($refresh=false, $msg_count_change=0, $timestamp=0, $thread_count_change=0)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    // always refresh on small forums
    if($PHORUM["message_count"]<1000) $refresh=true;

    if($refresh || empty($msg_count_change)){
        $sql = "select count(*) as message_count from {$PHORUM['message_table']} where forum_id={$PHORUM['forum_id']} and status=".PHORUM_STATUS_APPROVED;

        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

        $message_count = (int)mysql_result($res, 0, "message_count");
    } else {
        $message_count="message_count+$msg_count_change";
    }
    
    if($refresh || empty($timestamp)){

        $sql = "select max(modifystamp) as last_post_time from {$PHORUM['message_table']} where status=".PHORUM_STATUS_APPROVED." and forum_id={$PHORUM['forum_id']}";

        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

        $last_post_time = (int)mysql_result($res, 0, "last_post_time");
    } else {

        $last_post_time = $timestamp; 
    }

    if($refresh || empty($thread_count_change)){

        $sql = "select count(*) as thread_count from {$PHORUM['message_table']} where forum_id={$PHORUM['forum_id']} and parent_id=0 and status=".PHORUM_STATUS_APPROVED;
        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
        $thread_count = (int)mysql_result($res, 0, "thread_count");

    } else {
        
        $thread_count="thread_count+$thread_count_change";
    }

    $sql = "update {$PHORUM['forums_table']} set thread_count=$thread_count, message_count=$message_count, last_post_time=$last_post_time where forum_id={$PHORUM['forum_id']}";
    mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

}

/**
 * actually moves a thread to the given forum
 */
function phorum_db_move_thread($thread_id, $toforum)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($thread_id, "int");
    settype($toforum, "int");

    if($toforum > 0 && $thread_id > 0){
        $conn = phorum_db_mysql_connect();
        // retrieving the messages for the newflags-update below
        $thread_messages=phorum_db_get_messages($thread_id);

        // just changing the forum-id, simple isn't it?
        $sql = "UPDATE {$PHORUM['message_table']} SET forum_id=$toforum where thread=$thread_id";

        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
        
        // we need to update the number of posts in the current forum
        phorum_db_update_forum_stats(true);
        
        // and of the new forum
        $old_id=$GLOBALS["PHORUM"]["forum_id"];
        $GLOBALS["PHORUM"]["forum_id"]=$toforum;
        phorum_db_update_forum_stats(true);
        $GLOBALS["PHORUM"]["forum_id"]=$old_id;
        
        // we need to move the new-flags for this thread to the new forum too
        // first retrieving which messages belong to this thread
        unset($thread_messages['users']);
        
        $new_newflags=phorum_db_newflag_get_flags($toforum);
        $message_ids=array();
        $delete_ids =array();
        foreach($thread_messages as $mid => $data) {
            if($mid > $new_newflags['min_id']) { // only using it if its higher than min_id
                $message_ids[]=$mid;   
            } else { // newflags to delete
                $delete_ids[]=$mid;   
            }
        }
        
        if(count($message_ids)) { // we only go in if there are messages ... otherwise an error occured

            $ids_str=implode(",",$message_ids);

            // then doing the update to newflags
            $sql="UPDATE {$PHORUM['user_newflags_table']} SET forum_id = $toforum where message_id IN($ids_str)";
            $res = mysql_query($sql, $conn);
            if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

            // then doing the update to subscriptions
            $sql="UPDATE {$PHORUM['subscribers_table']} SET forum_id = $toforum where message_id IN($ids_str)";
            $res = mysql_query($sql, $conn);
            if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
            
        }
        
        if(count($delete_ids)) {
            $ids_str=implode(",",$delete_ids);
            // then doing the delete
            $sql="DELETE FROM {$PHORUM['user_newflags_table']} where message_id IN($ids_str)";
            $res = mysql_query($sql, $conn);
            if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");                
        }

    }
}

/**
 * closes the given thread
 */
function phorum_db_close_thread($thread_id){
    $PHORUM = $GLOBALS["PHORUM"];

    settype($thread_id, "int");

    if($thread_id > 0){
        $conn = phorum_db_mysql_connect();

        $sql = "UPDATE {$PHORUM['message_table']} SET closed=1 where thread=$thread_id";

        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    }
}

/**
 * closes the given thread
 */
function phorum_db_reopen_thread($thread_id){
    $PHORUM = $GLOBALS["PHORUM"];

    settype($thread_id, "int");

    if($thread_id > 0){
        $conn = phorum_db_mysql_connect();

        $sql = "UPDATE {$PHORUM['message_table']} SET closed=0 where thread=$thread_id";

        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    }
}

/**
 * This function executes a query to insert a forum into the forums
 * table and returns the forums id on success or 0 on failure.
 */

function phorum_db_add_forum($forum)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    foreach($forum as $key => $value){
        if (is_numeric($value)){
            $value = (int)$value;
            $fields[] = "$key=$value";
        }else{
            $value = mysql_escape_string($value);
            $fields[] = "$key='$value'";
        }
    }

    $sql = "insert into {$PHORUM['forums_table']} set " . implode(", ", $fields);

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $forum_id = 0;

    if ($res){
        $forum_id = mysql_insert_id($conn);
    }

    return $forum_id;
}

/**
 * This function executes a query to remove a forum from the forums
 * table and its messages.
 */

function phorum_db_drop_forum($forum_id)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($forum_id, "int");

    $conn = phorum_db_mysql_connect();

    $tables = array (
        $PHORUM['message_table'],
        $PHORUM['user_permissions_table'],
        $PHORUM['user_newflags_table'],
        $PHORUM['subscribers_table'],
        $PHORUM['forum_group_xref_table'],
        $PHORUM['forums_table']
    );

    foreach($tables as $table){
        $sql = "delete from $table where forum_id=$forum_id";
        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    }

$sql = "select file_id from {$PHORUM['files_table']} left join {$PHORUM['message_table']} using (message_id) where {$PHORUM['files_table']}.message_id > 0 AND {$PHORUM['message_table']}.message_id is NULL";
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    while($rec=mysql_fetch_assoc($res)){
        $files[]=$rec["file_id"];
    }    
    if(isset($files)){
        $sql = "delete from {$PHORUM['files_table']} where file_id in (".implode(",", $files).")";
        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    }    

}

/**
 * This function executes a query to remove a folder from the forums
 * table and change the parent of its children.
 */

function phorum_db_drop_folder($forum_id)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($forum_id, "int");

    $conn = phorum_db_mysql_connect();

    $sql = "select parent_id from {$PHORUM['forums_table']} where forum_id=$forum_id";

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $new_parent_id = mysql_result($res, 0, "parent_id");

    $sql = "update {$PHORUM['forums_table']} set parent_id=$new_parent_id where parent_id=$forum_id";

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $sql = "delete from {$PHORUM['forums_table']} where forum_id=$forum_id";

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
}

/**
 * This function executes a query to update a forum in the forums
 * table and returns non zero on success or 0 on failure.
 */

function phorum_db_update_forum($forum){
    $PHORUM = $GLOBALS["PHORUM"];

    $res = 0;

    if (!empty($forum["forum_id"])){
    
        // this way we can also update multiple forums at once
        if(is_array($forum["forum_id"])) {
            $forumwhere="forum_id IN (".implode(",",$forum["forum_id"]).")";        
        } else {
            $forumwhere="forum_id=".$forum["forum_id"];
        }
    
        unset($forum["forum_id"]);

        $conn = phorum_db_mysql_connect();

        foreach($forum as $key => $value){
            if (is_numeric($value)){
                $value = (int)$value;
                $fields[] = "$key=$value";
            }else{
                $value = mysql_escape_string($value);
                $fields[] = "$key='$value'";
            }
        }

        $sql = "update {$PHORUM['forums_table']} set " . implode(", ", $fields) . " where $forumwhere";

        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    }else{
        trigger_error("\$forum[forum_id] cannot be empty in phorum_update_forum()", E_USER_ERROR);
    }

    return $res;
}

/**
*
*/

function phorum_db_get_groups($group_id=0)
{
    $PHORUM = $GLOBALS["PHORUM"];
    $conn = phorum_db_mysql_connect();

    settype($group_id, "integer");

    $sql="select * from {$PHORUM['groups_table']}";
    if($group_id!=0) $sql.=" where group_id=$group_id";

    $res = mysql_query($sql, $conn);
    
    $groups=array();
    while($rec=mysql_fetch_assoc($res)){

        $groups[$rec["group_id"]]=$rec;
        $groups[$rec["group_id"]]["permissions"]=array();
    }

    $sql="select * from {$PHORUM['forum_group_xref_table']}";
    if($group_id!=0) $sql.=" where group_id=$group_id";

    $res = mysql_query($sql, $conn);

    while($rec=mysql_fetch_assoc($res)){

        $groups[$rec["group_id"]]["permissions"][$rec["forum_id"]]=$rec["permission"];

    }

    return $groups;

}

/**
* Get the members of a group.
* @param group_id - can be an integer (single group), or an array of groups
* @param status - a specific status to look for, defaults to all
* @return array - users (key is userid, value is group membership status)
*/

function phorum_db_get_group_members($group_id, $status = PHORUM_USER_GROUP_REMOVE)
{
    $PHORUM = $GLOBALS["PHORUM"];
    $conn = phorum_db_mysql_connect();
    
    if(is_array($group_id)){
        $group_id=implode(",", $group_id);
    } else {
        settype($group_id, "int");
    }

    // this join is only here so that the list of users comes out sorted
    // if phorum_db_user_get() sorts results itself, this join can go away
    $sql="select {$PHORUM['user_group_xref_table']}.user_id, {$PHORUM['user_group_xref_table']}.status from {$PHORUM['user_table']}, {$PHORUM['user_group_xref_table']} where {$PHORUM['user_table']}.user_id = {$PHORUM['user_group_xref_table']}.user_id and group_id in ($group_id)";
    if ($status != PHORUM_USER_GROUP_REMOVE) $sql.=" and {$PHORUM['user_group_xref_table']}.status = $status";
    $sql .=" order by username asc";

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");    
    $users=array();
    while($rec=mysql_fetch_assoc($res)){
        $users[$rec["user_id"]]=$rec["status"];
    }

    return $users;

}

/**
*
*/

function phorum_db_save_group($group)
{
    $PHORUM = $GLOBALS["PHORUM"];
    $conn = phorum_db_mysql_connect();

    $ret=false;

    if(isset($group["name"])){
        $sql="update {$PHORUM['groups_table']} set name='{$group['name']}', open={$group['open']} where group_id={$group['group_id']}";
    
        $res=mysql_query($sql, $conn);

        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    }
        
    if(!$err){

        if(isset($group["permissions"])){
            $sql="delete from {$PHORUM['forum_group_xref_table']} where group_id={$group['group_id']}";
    
            $res=mysql_query($sql, $conn);
    
            if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
            foreach($group["permissions"] as $forum_id=>$permission){
                $sql="insert into {$PHORUM['forum_group_xref_table']} set group_id={$group['group_id']}, permission=$permission, forum_id=$forum_id";
                $res=mysql_query($sql, $conn);
                if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
                if(!$res) break;
            }
        }
    }

    if($res>0) $ret=true;

    return $ret;

}

function phorum_db_delete_group($group_id)
{
    $PHORUM = $GLOBALS["PHORUM"];
    $conn = phorum_db_mysql_connect();

    settype($group_id, "int");

    $sql = "delete from {$PHORUM['groups_table']} where group_id = $group_id";
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    // delete things associated with groups
    $sql = "delete from {$PHORUM['user_group_xref_table']} where group_id = $group_id";
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $sql = "delete from {$PHORUM['forum_group_xref_table']} where group_id = $group_id";
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
}

/**
 * phorum_db_add_group()
 *
 * @param $group_name $group_id
 * @return
 **/
function phorum_db_add_group($group_name,$group_id=0)
{
    $PHORUM = $GLOBALS["PHORUM"];
    $conn = phorum_db_mysql_connect();

    settype($group_id, "int");

    if($group_id > 0) { // only used in conversion
        $sql="insert into {$PHORUM['groups_table']} (group_id,name) values ($group_id,'$group_name')";
    } else {
        $sql="insert into {$PHORUM['groups_table']} (name) values ('$group_name')";
    }

    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $group_id = 0;

    if ($res) {
        $group_id = mysql_insert_id($conn);
    }

    return $group_id;
}

/**
* This function returns all moderators for a particular forum
*/
function phorum_db_user_get_moderators($forum_id) {

   $PHORUM = $GLOBALS["PHORUM"];
   $conn = phorum_db_mysql_connect();
   
    settype($forum_id, "int");

   $sql="SELECT DISTINCT user.user_id, user.email FROM {$PHORUM['user_table']} as user LEFT JOIN {$PHORUM['user_permissions_table']} as perm ON perm.user_id=user.user_id WHERE (perm.permission >= ".PHORUM_USER_ALLOW_MODERATE_MESSAGES." AND (perm.permission & ".PHORUM_USER_ALLOW_MODERATE_MESSAGES." > 0) AND perm.forum_id=$forum_id) OR user.admin=1";
   
   
   $res = mysql_query($sql, $conn);
   
   if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");   
   
   $userinfo=array();

   while ($row = mysql_fetch_row($res)){
       $userinfo[$row[0]]=$row[1];         
   }
   // get users who belong to groups that have moderator access
   $sql = "SELECT DISTINCT user.user_id, user.email FROM {$PHORUM['user_table']} AS user, {$PHORUM['groups_table']} AS groups, {$PHORUM['user_group_xref_table']} AS usergroup, {$PHORUM['forum_group_xref_table']} AS forumgroup WHERE user.user_id = usergroup.user_id AND usergroup.group_id = groups.group_id AND groups.group_id = forumgroup.group_id AND forum_id = $forum_id AND permission & ".PHORUM_USER_ALLOW_MODERATE_MESSAGES." > 0 AND usergroup.status >= ".PHORUM_USER_GROUP_APPROVED;

   $res = mysql_query($sql, $conn);
   
   if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");   
   
   while ($row = mysql_fetch_row($res)){
           $userinfo[$row[0]]=$row[1];
   }   
   return $userinfo;
}

/**
 * This function executes a query to select data about a user including
 * his permission data and returns that in an array.
 */

function phorum_db_user_get($user_id, $detailed)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    if(is_array($user_id)){
        $user_ids=implode(",", $user_id);
    } else {
        $user_ids=(int)$user_id;
    }

    $users = array();

    $sql = "select * from {$PHORUM['user_table']} where user_id in ($user_ids)";

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    if (mysql_num_rows($res)){
        while($rec=mysql_fetch_assoc($res)){
            $users[$rec["user_id"]] = $rec;
        }
        
        if ($detailed){
            // get the users' permissions
            $sql = "select * from {$PHORUM['user_permissions_table']} where user_id in ($user_ids)";
        
            $res = mysql_query($sql, $conn);
            if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
        
            while ($row = mysql_fetch_assoc($res)){
                $users[$row["user_id"]]["forum_permissions"][$row["forum_id"]] = $row["permission"];
            }
        
            // get the users' groups and forum permissions through those groups
            $sql = "select user_id, {$PHORUM['user_group_xref_table']}.group_id, forum_id, permission from {$PHORUM['user_group_xref_table']} left join {$PHORUM['forum_group_xref_table']} using (group_id) where user_id in ($user_ids) AND {$PHORUM['user_group_xref_table']}.status >= ".PHORUM_USER_GROUP_APPROVED;

            $res = mysql_query($sql, $conn);
            if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
            
            while ($row = mysql_fetch_assoc($res)){
                $users[$row["user_id"]]["groups"][$row["group_id"]] = $row["group_id"];
                if(!empty($row["forum_id"])){
                    if(!isset($users[$row["user_id"]]["group_permissions"][$row["forum_id"]])) {
		    	         $users[$row["user_id"]]["group_permissions"][$row["forum_id"]] = 0;
		            }
                    $users[$row["user_id"]]["group_permissions"][$row["forum_id"]] = $users[$row["user_id"]]["group_permissions"][$row["forum_id"]] | $row["permission"];
                }
            }
        }

    }
            
    if(is_array($user_id)){
        return $users;
    } else {
        return $users[$user_id];
    }    

}

/**
 * This function gets a list of all the active users.
 * @return array - (key: userid, value: array (username, displayname)
 */
function phorum_db_user_get_list(){
   $PHORUM = $GLOBALS["PHORUM"];

   $conn = phorum_db_mysql_connect();
   
   $users = array();
   $sql = "select user_id, username from {$PHORUM['user_table']} order by username asc";
   $res = mysql_query($sql, $conn);
   if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

   while ($row = mysql_fetch_assoc($res)){
       $users[$row["user_id"]] = array("username" => $row["username"], "displayname" => $row["username"]);
   }

   return $users;
}

/**
 * This function executes a query to select data about a user including
 * his permission data and returns that in an array.
 */

function phorum_db_user_check_pass($username, $password, $temp_password=false){
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    $username = mysql_escape_string($username);

    $password = mysql_escape_string($password);

    $pass_field = ($temp_password) ? "password_temp" : "password";

    $sql = "select user_id from {$PHORUM['user_table']} where username='$username' and $pass_field='$password'";

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    return ($res && mysql_num_rows($res)) ? mysql_result($res, 0, "user_id") : 0;
}

/**
 * This function executes a query to check for the given field in the
 * user tableusername and return the user_id of the user it matches or 0
 * if no match is found.
 * 
 * The parameters can be arrays.  If they are, all must be passed and all
 * must have the same number of values.
 * 
 * If $return_array is true, an array of all matching rows will be returned.
 * Otherwise, only the first user_id from the results will be returned.
 */

function phorum_db_user_check_field($field, $value, $operator="=", $return_array=false){
    $PHORUM = $GLOBALS["PHORUM"];

    $ret = 0;

    $conn = phorum_db_mysql_connect();

    if(!is_array($field)){
        $field=array($field);
    }

    if(!is_array($value)){
        $value=array($value);
    }

    if(!is_array($operator)){
        $operator=array($operator);
    }

    foreach($field as $key=>$name){
        $value[$key] = mysql_escape_string($value[$key]);
        $clauses[]="$name $operator[$key] '$value[$key]'";
    }

    $sql = "select user_id from {$PHORUM['user_table']} where ".implode(" and ", $clauses);

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    if ($res && mysql_num_rows($res)){
        if($return_array){
            $ret=array();
            while($row=mysql_fetch_assoc($res)){
                $ret[$row["user_id"]]=$row["user_id"];
            }
        } else {
            $ret = mysql_result($res, 0, "user_id");
        }
    }

    return $ret;
}


/**
 * This function executes a query to add the given user data to the
 * database and returns the userid or 0
 */

function phorum_db_user_add($userdata){
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    if (isset($userdata["forum_permissions"]) && !empty($userdata["forum_permissions"])){
        $forum_perms = $userdata["forum_permissions"];
        unset($userdata["forum_permissions"]);
    }

    $sql = "insert into {$PHORUM['user_table']} set ";

    $values = array();

    foreach($userdata as $key => $value){
        if (!is_numeric($value)){
            $value = mysql_escape_string($value);
            $values[] = "$key='$value'";
        }else{
            $values[] = "$key=$value";
        }
    }

    $sql .= implode(", ", $values);

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $user_id = 0;
    if ($res){
        $user_id = mysql_insert_id($conn);
    }

    if ($res && isset($forum_perms)){
        foreach($forum_perms as $fid => $p){
            $sql = "insert into {$PHORUM['user_permissions_table']} set user_id=$user_id, forum_id=$fid, permission=$p";
            $res = mysql_query($sql, $conn);
            if ($err = mysql_error()){
                phorum_db_mysql_error("$err: $sql");
                break;
            }
        }
    }

    return $user_id;
}


/**
 * This function executes a query to update the given user data in the
 * database and returns the true or false
 */
function phorum_db_user_save($userdata){
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    if(isset($userdata["permissions"])){
        unset($userdata["permissions"]);
    }

    if (isset($userdata["forum_permissions"])){
        $forum_perms = $userdata["forum_permissions"];
        unset($userdata["forum_permissions"]);
    }

    if (isset($userdata["groups"])){
        $groups = $userdata["groups"];
        unset($userdata["groups"]);
        unset($userdata["group_permissions"]);
    }

    $user_id = $userdata["user_id"];
    unset($userdata["user_id"]);

    if(count($userdata)){

        $sql = "update {$PHORUM['user_table']} set ";

        $values = array();

        foreach($userdata as $key => $value){
            if (!is_numeric($value)){
                $value = mysql_escape_string($value);
                $values[] = "$key='$value'";
            }else{
                $values[] = "$key=$value";
            }
        }

        $sql .= implode(", ", $values);

        $sql .= " where user_id=$user_id";

        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    }

    if (isset($forum_perms)){

        $sql = "delete from {$PHORUM['user_permissions_table']} where user_id = $user_id";
        $res=mysql_query($sql, $conn);

        foreach($forum_perms as $fid=>$perms){
            $sql = "insert into {$PHORUM['user_permissions_table']} set user_id=$user_id, forum_id=$fid, permission=$perms";
            $res = mysql_query($sql, $conn);
            if ($err = mysql_error()){
                phorum_db_mysql_error("$err: $sql");
            }
        }
    }

    return (bool)$res;
}

/**
 * This function saves a users group permissions.
 */
function phorum_db_user_save_groups($user_id, $groups)
{
    $PHORUM = $GLOBALS["PHORUM"];
    if (!$user_id > 0){
        return false;
    }

    settype($user_id, "int");

    // erase the group memberships they have now
    $conn = phorum_db_mysql_connect();
    $sql = "delete from {$PHORUM['user_group_xref_table']} where user_id = $user_id";
    $res=mysql_query($sql, $conn);

    foreach($groups as $group_id => $group_perm){
        $sql = "insert into {$PHORUM['user_group_xref_table']} set user_id=$user_id, group_id=$group_id, status=$group_perm";
        mysql_query($sql, $conn);
        if ($err = mysql_error()){
            phorum_db_mysql_error("$err: $sql");
            break;
        }
    }
    return (bool)$res;
}

/**
 * This function executes a query to subscribe a user to a forum/thread.
 */

function phorum_db_user_subscribe($user_id, $forum_id, $thread, $type)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($user_id, "int");
    settype($forum_id, "int");
    settype($thread, "int");
    settype($type, "int");

    $conn = phorum_db_mysql_connect();

    $sql = "replace into {$PHORUM['subscribers_table']} set user_id=$user_id, forum_id=$forum_id, sub_type=$type, thread=$thread";

    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    return (bool)$res;
}

/**
  * This function increases the post-counter for a user by one
  */
function phorum_db_user_addpost() {
        
        $conn = phorum_db_mysql_connect();
        
        $sql="UPDATE ".$GLOBALS['PHORUM']['user_table']." SET posts=posts+1 WHERE user_id = ".$GLOBALS['PHORUM']['user']['user_id'];
        $res=mysql_query($sql,$conn);
        
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
        
        return (bool)$res;
}

/**
 * This function executes a query to unsubscribe a user to a forum/thread.
 */

function phorum_db_user_unsubscribe($user_id, $thread, $forum_id=0)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($user_id, "int");
    settype($forum_id, "int");
    settype($thread, "int");

    $conn = phorum_db_mysql_connect();

    $sql = "DELETE FROM {$PHORUM['subscribers_table']} WHERE user_id=$user_id AND thread=$thread";
    if($forum_id) $sql.=" and forum_id=$forum_id";
    
    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    return (bool)$res;
}

/**
 * This function will return a list of groups the user 
 * is a member of, as well as the users permissions.
 */
function phorum_db_user_get_groups($user_id)
{
    $PHORUM = $GLOBALS["PHORUM"];
    $groups = array();

    if (!$user_id > 0){
           return $groups;
    }

    settype($user_id, "int");

    $conn = phorum_db_mysql_connect();
    $sql = "SELECT group_id, status FROM {$PHORUM['user_group_xref_table']} WHERE user_id = $user_id ORDER BY status DESC";

    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    while($row = mysql_fetch_assoc($res)){
        $groups[$row["group_id"]] = $row["status"];
    }

    return $groups;
}

/**
 * This function executes a query to select data about a user including
 * his permission data and returns that in an array.
 * If $search is empty, all users should be returned.
 */

function phorum_db_search_users($search)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    $users = array();

    $search = trim($search);

    $sql = "select user_id, username, email from {$PHORUM['user_table']} where username like '%$search%' or email like '%$search%'order by username";

    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    if (mysql_num_rows($res)){
        while ($user = mysql_fetch_assoc($res)){
            $users[$user["user_id"]] = $user;
        }
    }

    return $users;
}


/**
 * This function gets the users that await approval
 */

function phorum_db_user_get_unapproved()
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    $sql="select user_id, username, email from {$PHORUM['user_table']} where active in(".PHORUM_USER_PENDING_BOTH.", ".PHORUM_USER_PENDING_MOD.") order by username";
    $res=mysql_query($sql, $conn);
    
    if ($err = mysql_error()){
        phorum_db_mysql_error("$err: $sql");
    }
        
    $users=array();
    if($res){
        while($rec=mysql_fetch_assoc($res)){
            $users[$rec["user_id"]]=$rec;
        }
    }
    
    return $users;

}
/**
 * This function deletes a user completely
 * - entry in the users-table
 * - entries in the permissions-table
 * - entries in the newflags-table
 * - entries in the subscribers-table
 * - entries in the group_xref-table
 * - entries in the private-messages-table
 * - entries in the files-table
 * - sets entries in the messages-table to anonymous 
 *
 */
function phorum_db_user_delete($user_id) {
    $PHORUM = $GLOBALS["PHORUM"];
    
    // how would we check success???
    $ret = true;

    settype($user_id, "int");

    $conn = phorum_db_mysql_connect();
    // user-table
    $sql = "delete from {$PHORUM['user_table']} where user_id=$user_id";
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
    // permissions-table
    $sql = "delete from {$PHORUM['user_permissions_table']} where user_id=$user_id";
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    // newflags-table    
    $sql = "delete from {$PHORUM['user_newflags_table']} where user_id=$user_id";
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    // subscribers-table
    $sql = "delete from {$PHORUM['subscribers_table']} where user_id=$user_id";
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
    // group-xref-table
    $sql = "delete from {$PHORUM['user_group_xref_table']} where user_id=$user_id";    
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
    // incoming pm's
    $sql = "delete from {$PHORUM['private_message_table']} where to_user_id=$user_id";    
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
    // set outgoing pm's to delete
    $sql = "update {$PHORUM['private_message_table']} set from_del_flag = 1 where from_user_id=$user_id and from_del_flag=0";
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
    // files-table
    $sql = "delete from {$PHORUM['files_table']} where user_id=$user_id and message_id=0";
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    // messages-table
    if(PHORUM_DELETE_CHANGE_AUTHOR) {
      $sql = "update {$PHORUM['message_table']} set user_id=0,author='".mysql_escape_string($PHORUM['DATA']['LANG']['AnonymousUser'])."' where user_id=$user_id";
    } else {
      $sql = "update {$PHORUM['message_table']} set user_id=0 where user_id=$user_id";
    }
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");      
    
    return $ret;    
}


/**
 * This function gets the users file list
 */

function phorum_db_get_user_file_list($user_id)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    settype($user_id, "int");

    $files=array();

    $sql="select file_id, filename, filesize, add_datetime from {$PHORUM['files_table']} where user_id=$user_id and message_id=0";
    
    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()){
        phorum_db_mysql_error("$err: $sql");
    }
        
    if($res){
        while($rec=mysql_fetch_assoc($res)){
            $files[$rec["file_id"]]=$rec;
        }
    }
    
    return $files;
}


/**
 * This function gets the message's file list
 */

function phorum_db_get_message_file_list($message_id)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    $files=array();

    $sql="select file_id, filename, filesize, add_datetime from {$PHORUM['files_table']} where message_id=$message_id";
    
    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()){
        phorum_db_mysql_error("$err: $sql");
    }
        
    if($res){
        while($rec=mysql_fetch_assoc($res)){
            $files[$rec["file_id"]]=$rec;
        }
    }
    
    return $files;
}


/**
 * This function saves a file to the db
 */

function phorum_db_file_get($file_id)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    settype($user_id, "int");

    $file=array();
    
    $sql="select * from {$PHORUM['files_table']} where file_id=$file_id";

    $res = mysql_query($sql, $conn);
        
    if ($err = mysql_error()){
        phorum_db_mysql_error("$err: $sql");
    }

    if($res){
        $file=mysql_fetch_assoc($res);
    }

    return $file;
}


/**
 * This function saves a file to the db
 */

function phorum_db_file_save($user_id, $filename, $filesize, $buffer, $message_id=0)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    $file_id=0;

    settype($user_id, "int");
    settype($message_id, "int");
    settype($filesize, "int");

    $filename=addslashes($filename);

    $sql="insert into {$PHORUM['files_table']} set user_id=$user_id, message_id=$message_id, filename='$filename', filesize=$filesize, file_data='$buffer', add_datetime=".time();

    $res = mysql_query($sql, $conn);
        
    if ($err = mysql_error()){
        phorum_db_mysql_error("$err: $sql");
    }

    if($res){
        $file_id=mysql_insert_id($conn);
    }

    return $file_id;
}


/**
 * This function saves a file to the db
 */

function phorum_db_file_delete($file_id)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    settype($file_id, "int");

    $file=array();
    
    $sql="delete from {$PHORUM['files_table']} where file_id=$file_id";

    $res = mysql_query($sql, $conn);
        
    if ($err = mysql_error()){
        phorum_db_mysql_error("$err: $sql");
    }

    return $res;
}


/**
 * This function reads the current total size of all files for a user
 */

function phorum_db_get_user_filesize_total($user_id)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    settype($user_id, "int");

    $total=0;

    $sql="select sum(filesize) as total from {$PHORUM['files_table']} where user_id=$user_id and message_id=0";

    $res = mysql_query($sql, $conn);
        
    if ($err = mysql_error()){
        phorum_db_mysql_error("$err: $sql");
    }

    if($res){
        $total=mysql_result($res, 0,"total");
    }

    return $total;

}

/**
 * This function returns the newinfo-array for markallread
 */

function phorum_db_newflag_allread($forum_id=0)
{
    $PHORUM = $GLOBALS['PHORUM'];
    $conn = phorum_db_mysql_connect();
    
    settype($forum_id, "int");

    if(empty($forum_id)) $forum_id=$PHORUM["forum_id"];
    
    // delete all newflags for this user and forum
    phorum_db_newflag_delete(0,$forum_id);
    
    // get the maximum message-id in this forum
    $sql = "select max(message_id) from {$PHORUM['message_table']} where forum_id=$forum_id";
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()){
        phorum_db_mysql_error("$err: $sql");
    }elseif (mysql_num_rows($res) > 0){
        $row = mysql_fetch_row($res);
        if($row[0] > 0) {
            // set this message as min-id
            phorum_db_newflag_add_read(array(0=>array('id'=>$row[0],'forum'=>$forum_id)));
        }
    }
    
}


/**
* This function returns the read messages for the current user and forum
* optionally for a given forum (for the index)
*/
function phorum_db_newflag_get_flags($forum_id=0) 
{
    $PHORUM = $GLOBALS["PHORUM"];
    
    settype($forum_id, "int");

    $read_msgs=array('min_id'=>0);

    if(empty($forum_id)) $forum_id=$PHORUM["forum_id"];
    
    $sql="SELECT message_id,forum_id FROM ".$PHORUM['user_newflags_table']." WHERE user_id={$PHORUM['user']['user_id']} AND forum_id IN({$forum_id},0)";
    
    $conn = phorum_db_mysql_connect();
    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    while($row=mysql_fetch_row($res)) {
        // set the min-id if given flag is set
        if($row[1] != 0 && ($read_msgs['min_id']==0 || $row[0] < $read_msgs['min_id'])) {
            $read_msgs['min_id']=$row[0];
        } else {
            $read_msgs[$row[0]]=$row[0];
        }
    }
    
    return $read_msgs;
}


/**
* This function returns the count of unread messages the current user and forum
* optionally for a given forum (for the index)
*/
function phorum_db_newflag_get_unread_count($forum_id=0) 
{
    $PHORUM = $GLOBALS["PHORUM"];
    
    settype($forum_id, "int");

    if(empty($forum_id)) $forum_id=$PHORUM["forum_id"];
    
    // get the read message array
    $read_msgs = phorum_db_newflag_get_flags($forum_id);

    if($read_msgs["min_id"]==0) return array(0,0);    

    $sql="SELECT count(*) as count FROM ".$PHORUM['message_table']." WHERE message_id NOT in (".implode(",", $read_msgs).") and message_id > {$read_msgs['min_id']} and forum_id in ({$forum_id},0) and status=".PHORUM_STATUS_APPROVED;

    $conn = phorum_db_mysql_connect();
    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $counts[] = mysql_result($res, 0, "count");

    $sql="SELECT count(*) as count FROM ".$PHORUM['message_table']." WHERE message_id NOT in (".implode(",", $read_msgs).") and message_id > {$read_msgs['min_id']} and forum_id in ({$forum_id},0) and parent_id=0 and status=".PHORUM_STATUS_APPROVED;

    $conn = phorum_db_mysql_connect();
    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $counts[] = mysql_result($res, 0, "count");

    return $counts;
}


/**
 * This function marks a message as read
 */
function phorum_db_newflag_add_read($message_ids) {
    $PHORUM = $GLOBALS["PHORUM"];

    $num_newflags=phorum_db_newflag_get_count();

    // maybe got just one message
    if(!is_array($message_ids)) {
        $message_ids=array(0=>(int)$message_ids);
    }
    // deleting messages which are too much
    $num_end=$num_newflags+count($message_ids);
    if($num_end > PHORUM_MAX_NEW_INFO) {
        phorum_db_newflag_delete($num_end - PHORUM_MAX_NEW_INFO);
    }
    // building the query
    $values=array();
    $cnt=0;

    foreach($message_ids as $id=>$data) {
        if(is_array($data)) {
            $values[]="({$PHORUM['user']['user_id']},{$data['forum']},{$data['id']})";            
        } else {
            $values[]="({$PHORUM['user']['user_id']},{$PHORUM['forum_id']},$data)";
        }
        $cnt++;
    }
    if($cnt) {
        $insert_sql="INSERT IGNORE INTO ".$PHORUM['user_newflags_table']." (user_id,forum_id,message_id) VALUES".join(",",$values);
        
        // fire away
        $conn = phorum_db_mysql_connect();
        $res = mysql_query($insert_sql, $conn);
    
        if ($err = mysql_error()) phorum_db_mysql_error("$err: $insert_sql");
    }
}

/**
* This function returns the number of newflags for this user and forum
*/
function phorum_db_newflag_get_count($forum_id=0) 
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($forum_id, "int");

    if(empty($forum_id)) $forum_id=$PHORUM["forum_id"];
    
    $sql="SELECT count(*) FROM ".$PHORUM['user_newflags_table']." WHERE user_id={$PHORUM['user']['user_id']} AND forum_id={$forum_id}";
    
    // fire away
    $conn = phorum_db_mysql_connect();
    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");    
    
    $row=mysql_fetch_row($res);
    
    return $row[0];
}

/**
* This function removes a number of newflags for this user and forum
*/
function phorum_db_newflag_delete($numdelete=0,$forum_id=0) 
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($forum_id, "int");
    settype($numdelete, "int");

    if(empty($forum_id)) $forum_id=$PHORUM["forum_id"];
    
    if($numdelete>0) {
        $lvar=" ORDER BY message_id ASC LIMIT $numdelete";    
    } else {
        $lvar="";   
    }
    // delete the number of newflags given
    $del_sql="DELETE FROM ".$PHORUM['user_newflags_table']." WHERE user_id={$PHORUM['user']['user_id']} AND forum_id={$forum_id}".$lvar;
    // fire away
    $conn = phorum_db_mysql_connect();
    $res = mysql_query($del_sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $del_sql");            
}

/**
 * This function executes a query to get the user ids of the users
 * subscribed to a forum/thread.
 */

function phorum_db_get_subscribed_users($forum_id, $thread, $type){
    $PHORUM = $GLOBALS["PHORUM"];

    settype($forum_id, "int");
    settype($thread, "int");
    settype($type, "int");

    $conn = phorum_db_mysql_connect();

    $userignore="";
    if ($PHORUM["DATA"]["LOGGEDIN"])
       $userignore="and b.user_id != {$PHORUM['user']['user_id']}";

    $sql = "select DISTINCT(b.email) from {$PHORUM['subscribers_table']} as a,{$PHORUM['user_table']} as b where a.forum_id=$forum_id and (a.thread=$thread or a.thread=0) and a.sub_type=$type and b.user_id=a.user_id $userignore";

    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

        $arr=array();

    while ($rec = mysql_fetch_row($res)){
        $arr[] = $rec[0];
    }

    return $arr;
}

/**
 * This function executes a query to get the subscriptions of a user-id,
 * together with the forum-id and subjects of the threads
 */

function phorum_db_get_message_subscriptions($user_id,$days=2){
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    settype($user_id, "int");

    $userignore="";
    if ($PHORUM["DATA"]["LOGGEDIN"])
       $userignore="and b.user_id != {$PHORUM['user']['user_id']}";

    if($days > 0) {
         $timestr=" AND (".time()." - b.modifystamp) <= ($days * 86400)";
    } else {
        $timestr="";
    }

    $sql = "select a.thread, a.forum_id, a.sub_type, b.subject,b.modifystamp,b.author,b.user_id,b.email from {$PHORUM['subscribers_table']} as a,{$PHORUM['message_table']} as b where a.user_id=$user_id and b.message_id=a.thread and (a.sub_type=".PHORUM_SUBSCRIPTION_MESSAGE." or a.sub_type=".PHORUM_SUBSCRIPTION_BOOKMARK.")"."$timestr ORDER BY b.modifystamp desc";

    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    $arr=array();
    $forum_ids=array();

    while ($rec = mysql_fetch_assoc($res)){
        $unsub_url=phorum_get_url(PHORUM_CONTROLCENTER_URL, "panel=".PHORUM_CC_SUBSCRIPTION_THREADS, "unsub_id=".$rec['thread'], "unsub_forum=".$rec['forum_id'], "unsub_type=".$rec['sub_type']);
        $rec['unsubscribe_url']=$unsub_url;
        $arr[] = $rec;
        $forum_ids[]=$rec['forum_id'];
    }
    $arr['forum_ids']=$forum_ids;

    return $arr;
}

/**
 * This function executes a query to find out if a user is subscribed to a thread
 */

function phorum_db_get_if_subscribed($forum_id, $thread, $user_id, $type=PHORUM_SUBSCRIPTION_MESSAGE)
{
    $PHORUM = $GLOBALS["PHORUM"];

    settype($forum_id, "int");
    settype($thread, "int");
    settype($user_id, "int");
    settype($type, "int");

    $conn = phorum_db_mysql_connect();

    $sql = "select user_id from {$PHORUM['subscribers_table']} where forum_id=$forum_id and thread=$thread and user_id=$user_id and sub_type=$type";

    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");

    if (mysql_num_rows($res) > 0){
        $retval = true;
    }else{
        $retval = false;
    }

    return $retval;
}


/**
 * This function retrieves the banlists for the current forum 
 */
 
function phorum_db_get_banlists() {
    $PHORUM = $GLOBALS["PHORUM"];
    
    $retarr = array();
    $forumstr = "";
    
    $conn = phorum_db_mysql_connect();
    
    if(isset($PHORUM['forum_id']) && !empty($PHORUM['forum_id']))
        $forumstr = "WHERE forum_id = {$PHORUM['forum_id']} OR forum_id = 0";
        
    
    
    $sql = "SELECT * FROM {$PHORUM['banlist_table']} $forumstr";
    
    $res = mysql_query($sql, $conn);
    
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
    if (mysql_num_rows($res) > 0){
        while($row = mysql_fetch_assoc($res)) {
            $retarr[$row['type']][$row['id']]=array('pcre'=>$row['pcre'],'string'=>$row['string'],'forum_id'=>$row['forum_id']);
        }
    }
    return $retarr;
}


/**
 * This function retrieves one item from the banlists
 */
 
function phorum_db_get_banitem($banid) {
    $PHORUM = $GLOBALS["PHORUM"];
    
    $retarr = array();
    
    $conn = phorum_db_mysql_connect();
    
    settype($banid, "int");

    $sql = "SELECT * FROM {$PHORUM['banlist_table']} WHERE id = $banid";
    
    $res = mysql_query($sql, $conn);
    
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
    if (mysql_num_rows($res) > 0){
        while($row = mysql_fetch_assoc($res)) {
            $retarr=array('pcre'=>$row['pcre'],'string'=>$row['string'],'forumid'=>$row['forum_id'],'type'=>$row['type']);
        }
    }
    return $retarr;
}


/**
 * This function deletes one item from the banlists
 */
 
function phorum_db_del_banitem($banid) {
    $PHORUM = $GLOBALS["PHORUM"];
    
    $conn = phorum_db_mysql_connect();
    
    $sql = "DELETE FROM {$PHORUM['banlist_table']} WHERE id = $banid";
    
    $res = mysql_query($sql, $conn);
    
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
    if(mysql_affected_rows($conn) > 0) {
        return true;   
    } else {
        return false;
    }
}


/**
 * This function adds or modifies a banlist-entry
 */
 
function phorum_db_mod_banlists($type,$pcre,$string,$forum_id,$id=0) {
    $PHORUM = $GLOBALS["PHORUM"];
    
    $retarr = array();
    
    $conn = phorum_db_mysql_connect();
    
    settype($type, "int");
    settype($pcre, "int");
    settype($forum_id, "int");
    settype($id, "int");

    if($id > 0) { // modifying an entry
        $sql = "UPDATE {$PHORUM['banlist_table']} SET forum_id = $forum_id, type = $type, pcre = $pcre, string = '".mysql_escape_string($string)."' where id = $id";
    } else { // adding an entry
        $sql = "INSERT INTO {$PHORUM['banlist_table']} (forum_id,type,pcre,string) VALUES($forum_id,$type,$pcre,'".mysql_escape_string($string)."')";
    }

    $res = mysql_query($sql, $conn);
    
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
    if(mysql_affected_rows($conn) > 0) {
        return true;   
    } else {
        return false;
    }
}
 


/**
 * This function retrives private messages
 */

function phorum_db_get_private_messages($user_id, $type)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    settype($user_id, "int");

    if($type!="from" && $type!="to"){
        trigger_error("\$type must be either `to` or `from` in function phorum_db_get_private_messages()", E_USER_WARNING);
        return 0;
    }
    
    $field1=$type."_user_id";
    $field2=$type."_del_flag";
    
    $retarr=array();

    $sql="select * from {$PHORUM['private_message_table']} where $field1=$user_id and $field2=0 order by $field1 desc, $field2 desc, datestamp desc";
    $res = mysql_query($sql, $conn);
    
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
    if (mysql_num_rows($res) > 0){
        while($row = mysql_fetch_assoc($res)) {
            $retarr[]=$row;
        }
    }

    return $retarr;
}


/**
 * This function retrives private messages
 */

function phorum_db_get_private_message($pm_id)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    settype($pm_id, "int");
    
    $retarr=array();

    $sql="select * from {$PHORUM['private_message_table']} where private_message_id=$pm_id";
    if(!$PHORUM["user"]["admin"]) $sql.=" and (to_user_id={$PHORUM['user']['user_id']} or from_user_id={$PHORUM['user']['user_id']})";

    $res = mysql_query($sql, $conn);
    
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
    if (mysql_num_rows($res) > 0){
        $retarr = mysql_fetch_assoc($res);
    }

    return $retarr;
}

/**
 * This function retrieves the number of private messages a user has recieved, and returns both the total and the number unread.
 */

function phorum_db_get_private_message_count($user_id)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();
    
    settype($user_id, "int");

    $retarr=array();

    $sql="select count(*) as total, (count(*) - sum(read_flag)) as new from {$PHORUM['private_message_table']} where to_user_id=$user_id and to_del_flag=0";
    $res = mysql_query($sql, $conn);
    
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
    if (mysql_num_rows($res) > 0){
        $res = mysql_fetch_assoc($res);
        $retarr["total"] = $res["total"];
        $retarr["new"] = ($res["new"] >= 1) ? $res["new"] : 0;
    }

    return $retarr;
}

/**
 * This function inserts a private message
 */

function phorum_db_put_private_messages($to_username, $to_user_id, $subject, $message, $keep)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    settype($to_user_id, "int");

    $from_delete_flag = (empty($keep)) ? 1 : 0;

    $sql="insert into {$PHORUM['private_message_table']} set
            from_username  = '".mysql_escape_string($PHORUM['user']['username'])."',
            to_username    = '".mysql_escape_string($to_username)."',
            from_user_id   = '{$PHORUM['user']['user_id']}',
            to_user_id     = '$to_user_id',
            subject        = '".mysql_escape_string($subject)."',
            message        = '".mysql_escape_string($message)."',
            datestamp      = '".time()."',
            from_del_flag  = '$from_delete_flag'";

    $res = mysql_query($sql, $conn);
        
    if ($err = mysql_error()){
        phorum_db_mysql_error("$err: $sql");
    }

    return $res;
}


/**
 * This function updates the flags in a pm
 */

function phorum_db_update_private_message($pm_id, $flag, $value)
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    if($flag!="read_flag" && $flag!="return_flag" && $flag!="reply_flag" && $flag!="to_del_flag" && $flag!="from_del_flag"){
        trigger_error("Invalid value for \$flag in function phorum_db_update_private_message()", E_USER_WARNING);
        return 0;
    }

    settype($value, "int");
    settype($pm_id, "int");
    
    $sql="update {$PHORUM['private_message_table']} set $flag=$value where private_message_id=$pm_id";
    if(!$PHORUM["user"]["admin"]) $sql.=" and (to_user_id={$PHORUM['user']['user_id']} or from_user_id={$PHORUM['user']['user_id']})";

    $res = mysql_query($sql, $conn);

    if ($err = mysql_error()){
        phorum_db_mysql_error("$err: $sql");
    }

    if($res>0 && ($flag=="to_del_flag" || $flag=="from_del_flag")){
        // clear any messages where both delete flags are set
        $sql="delete from {$PHORUM['private_message_table']} where to_del_flag=1 and from_del_flag=1";
        mysql_query($sql, $conn);
    }

    return $res;
}

/**
* This function returns messages or threads which are newer or older
* than the given timestamp
*
* $time  - holds the timestamp the comparison is done against
* $forum - get Threads from this forum
* $mode  - should we compare against datestamp (1) or modifystamp (2)
*
*/
function phorum_db_prune_oldThreads($time,$forum=0,$mode=1) {

    $PHORUM = $GLOBALS['PHORUM'];
    
    $conn = phorum_db_mysql_connect();
    $numdeleted=0;
    
    $compare_field = "datestamp";
    if($mode == 2) {
      $compare_field = "modifystamp";
    }
            
    $forummode="";
    if($forum > 0) {
      $forummode=" AND forum_id = $forum";
    }
    
    // retrieving which threads to delete
    $sql = "select thread from {$PHORUM['message_table']} where $compare_field < $time AND parent_id=0 $forummode";
    
    $res = mysql_query($sql, $conn);
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");        
    
    $ret=array();
    while($row=mysql_fetch_row($res)) {
        $ret[]=$row[0];
    }
    
    $thread_ids=implode(",",$ret);
    
    if(count($ret)) {
      // deleting the messages/threads
      $sql="delete from {$PHORUM['message_table']} where thread IN ($thread_ids)";
      $res = mysql_query($sql, $conn);
      if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");       
              
      $numdeleted = mysql_affected_rows($conn);
      if($numdeleted < 0) {
        $numdeleted=0;
      }
      
      // deleting the associated notification-entries
      $sql="delete from {$PHORUM['subscribers_table']} where thread IN ($thread_ids)";
      $res = mysql_query($sql, $conn);
      if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");       
                

      // optimizing the message-table        
      $sql="optimize table {$PHORUM['message_table']}";
      $res = mysql_query($sql, $conn);
      if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");               
    }
    
    return $numdeleted;
}

/**
 * This function returns the maximum message-id in the database
 */
function phorum_db_get_max_messageid() {
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();
    $maxid = 0;
        
    $sql="SELECT max(message_id) from ".$PHORUM["message_table"];
    $res = mysql_query($sql, $conn);
    
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    
    if (mysql_num_rows($res) > 0){
        $row = mysql_fetch_row($res);
        $maxid = $row[0];
    }

    return $maxid;    
}

/**
 * This function increments the viewcount for a post
 */
 
function phorum_db_viewcount_inc($message_id) {
    if($message_id < 1 || !is_numeric($message_id)) {
        return false;
    }
    
    $conn = phorum_db_mysql_connect();
    $sql="UPDATE ".$GLOBALS['PHORUM']['message_table']." SET viewcount=viewcount+1 WHERE message_id=$message_id";
    $res = mysql_query($sql, $conn);
    
    if ($err = mysql_error()) phorum_db_mysql_error("$err: $sql");
    

    return true;        
    
}


/**
 * This function creates the tables needed in the database.
 */

function phorum_db_create_tables()
{
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    $retmsg = "";

    $queries = array(

        // create tables
        "CREATE TABLE {$PHORUM['forums_table']} ( forum_id int(10) unsigned NOT NULL auto_increment, name varchar(50) NOT NULL default '', active smallint(6) NOT NULL default '0', description text NOT NULL default '', template varchar(50) NOT NULL default '', folder_flag tinyint(1) NOT NULL default '0', parent_id int(10) unsigned NOT NULL default '0', list_length_flat int(10) unsigned NOT NULL default '0', list_length_threaded int(10) unsigned NOT NULL default '0', moderation int(10) unsigned NOT NULL default '0', email_outgoing_address varchar(50) NOT NULL default '', email_incoming_address varchar(50) NOT NULL default '', email_subject_tag varchar(20) NOT NULL default '', threaded_list tinyint(4) NOT NULL default '0', threaded_read tinyint(4) NOT NULL default '0', float_to_top tinyint(4) NOT NULL default '0', check_duplicate tinyint(4) NOT NULL default '0', allow_attachment_types varchar(100) NOT NULL default '', max_attachment_size int(10) unsigned NOT NULL default '0', max_attachments int(10) unsigned NOT NULL default '0', pub_perms int(10) unsigned NOT NULL default '0', reg_perms int(10) unsigned NOT NULL default '0', display_ip_address smallint(5) unsigned NOT NULL default '1', allow_email_notify smallint(5) unsigned NOT NULL default '1', language varchar(100) NOT NULL default 'english', email_moderators tinyint(1) NOT NULL default '0', message_count int(10) unsigned NOT NULL default '0', thread_count int(10) unsigned NOT NULL default '0', last_post_time int(10) unsigned NOT NULL default '0', display_order int(10) unsigned NOT NULL default '0', read_length int(10) unsigned NOT NULL default '0', edit_post tinyint(1) NOT NULL default '1',template_settings text NOT NULL, count_views tinyint(1) unsigned NOT NULL default '0', display_fixed tinyint(1) unsigned NOT NULL default '0', PRIMARY KEY (forum_id), KEY name (name), KEY active (active,parent_id), KEY group_id (parent_id)) TYPE=MyISAM",
        "CREATE TABLE {$PHORUM['message_table']} ( message_id int(10) unsigned NOT NULL auto_increment, forum_id int(10) unsigned NOT NULL default '0', thread int(10) unsigned NOT NULL default '0', parent_id int(10) unsigned NOT NULL default '0', author varchar(37) NOT NULL default '', subject varchar(255) NOT NULL default '', body text NOT NULL, email varchar(100) NOT NULL default '', ip varchar(50) NOT NULL default '', status tinyint(4) NOT NULL default '2', msgid varchar(100) NOT NULL default '', modifystamp int(10) unsigned NOT NULL default '0', user_id int(10) unsigned NOT NULL default '0', thread_count int(10) unsigned NOT NULL default '0', moderator_post tinyint(3) unsigned NOT NULL default '0', sort tinyint(4) NOT NULL default '2', datestamp int(10) unsigned NOT NULL default '0', meta text NOT NULL, viewcount int(10) unsigned NOT NULL default '0', closed tinyint(4) NOT NULL default '0', PRIMARY KEY (message_id), KEY thread_message (thread,message_id), KEY thread_forum (thread,forum_id), KEY special_threads (sort,forum_id), KEY status_forum (status,forum_id), KEY list_page_float (forum_id,parent_id,modifystamp), KEY list_page_flat (forum_id,parent_id,thread), KEY post_count (forum_id,status,parent_id), KEY dup_check (forum_id,author,subject,datestamp), KEY forum_max_message (forum_id,message_id,status,parent_id), KEY last_post_time (forum_id,status,modifystamp) ) TYPE=MyISAM",
        "CREATE TABLE {$PHORUM['settings_table']} ( name varchar(255) NOT NULL default '', type enum('V','S') NOT NULL default 'V', data text NOT NULL, PRIMARY KEY (name)) TYPE=MyISAM",
        "CREATE TABLE {$PHORUM['subscribers_table']} ( user_id int(10) unsigned NOT NULL default '0', forum_id int(10) unsigned NOT NULL default '0', sub_type int(10) unsigned NOT NULL default '0', thread int(10) unsigned NOT NULL default '0', PRIMARY KEY (user_id,forum_id,thread), KEY forum_id (forum_id,thread,sub_type)) TYPE=MyISAM",
        "CREATE TABLE {$PHORUM['user_permissions_table']} ( user_id int(10) unsigned NOT NULL default '0', forum_id int(10) unsigned NOT NULL default '0', permission int(10) unsigned NOT NULL default '0', PRIMARY KEY  (user_id,forum_id), KEY forum_id (forum_id,permission) ) TYPE=MyISAM",
        "CREATE TABLE {$PHORUM['user_table']} ( user_id int(10) unsigned NOT NULL auto_increment, username varchar(50) NOT NULL default '', password varchar(50) NOT NULL default '', password_temp varchar(50) NOT NULL default '', email varchar(100) NOT NULL default '',  email_temp varchar(110) NOT NULL default '', hide_email tinyint(1) NOT NULL default '0', active tinyint(1) NOT NULL default '0', user_data text NOT NULL, signature text NOT NULL, threaded_list tinyint(4) NOT NULL default '0', posts int(10) NOT NULL default '0', admin tinyint(1) NOT NULL default '0', threaded_read tinyint(4) NOT NULL default '0', date_added int(10) unsigned NOT NULL default '0', date_last_active int(10) unsigned NOT NULL default '0', last_active_forum int(10) unsigned NOT NULL default '0', hide_activity tinyint(1) NOT NULL default '0', PRIMARY KEY (user_id), UNIQUE KEY username (username), KEY active (active), KEY userpass (username,password), KEY activity (date_last_active,hide_activity,last_active_forum), KEY date_added (date_added), KEY email_temp (email_temp) ) TYPE=MyISAM",
        "CREATE TABLE {$PHORUM['user_newflags_table']} ( user_id int(11) NOT NULL default '0', forum_id int(11) NOT NULL default '0', message_id int(11) NOT NULL default '0', PRIMARY KEY  (user_id,forum_id,message_id) ) TYPE=MyISAM",
        "CREATE TABLE {$PHORUM['groups_table']} ( group_id int(11) NOT NULL auto_increment, name varchar(255) NOT NULL default '0', open tinyint(3) NOT NULL default '0', PRIMARY KEY  (group_id) ) TYPE=MyISAM",
        "CREATE TABLE {$PHORUM['forum_group_xref_table']} ( forum_id int(11) NOT NULL default '0', group_id int(11) NOT NULL default '0', permission int(10) unsigned NOT NULL default '0', PRIMARY KEY  (forum_id,group_id) ) TYPE=MyISAM",
        "CREATE TABLE {$PHORUM['user_group_xref_table']} ( user_id int(11) NOT NULL default '0', group_id int(11) NOT NULL default '0', status tinyint(3) NOT NULL default '1', PRIMARY KEY  (user_id,group_id) ) TYPE=MyISAM",
        "CREATE TABLE {$PHORUM['files_table']} ( file_id int(11) NOT NULL auto_increment, user_id int(11) NOT NULL default '0', filename varchar(255) NOT NULL default '', filesize int(11) NOT NULL default '0', file_data mediumtext NOT NULL, add_datetime int(10) unsigned NOT NULL default '0', message_id int(10) unsigned NOT NULL default '0', PRIMARY KEY (file_id), KEY add_datetime (add_datetime), KEY message_id (message_id)) TYPE=MyISAM",
        "CREATE TABLE {$PHORUM['banlist_table']} ( id int(11) NOT NULL auto_increment, forum_id int(11) NOT NULL default '0', type tinyint(4) NOT NULL default '0', pcre tinyint(4) NOT NULL default '0', string varchar(255) NOT NULL default '', PRIMARY KEY  (id), KEY forum_id (forum_id)) TYPE=MyISAM",
        "CREATE TABLE {$PHORUM['private_message_table']} ( private_message_id int(10) unsigned NOT NULL auto_increment, from_username varchar(50) NOT NULL default '', to_username varchar(50) NOT NULL default '', from_user_id int(10) unsigned NOT NULL default '0', to_user_id int(10) unsigned NOT NULL default '0', subject varchar(100) NOT NULL default '', message text NOT NULL, datestamp int(10) unsigned NOT NULL default '0', read_flag tinyint(1) NOT NULL default '0', reply_flag tinyint(1) NOT NULL default '0', to_del_flag tinyint(1) NOT NULL default '0', from_del_flag tinyint(1) NOT NULL default '0', PRIMARY KEY (private_message_id), KEY to_user_id (to_user_id,to_del_flag,datestamp), KEY from_user_id (from_user_id,from_del_flag,datestamp), KEY to_del_flag (to_del_flag,from_del_flag), KEY read_flag (to_user_id,read_flag,to_del_flag) ) TYPE=MyISAM"
        
    );

    foreach($queries as $sql){
        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()){
            $retmsg = "$err<br />";
            phorum_db_mysql_error("$err: $sql");
            break;
        }
    }


    if(empty($err)){

        $tmp_dir = (substr(__FILE__, 0, 1)=="/") ? "/tmp" : "C:\\Windows\\Temp";
                    
        // set initial settings
        $settings=array(
			"title" => "Phorum 5",
			"cache" => "$tmp_dir",
			"session_timeout" => "30",
			"session_path" => "/",
			"session_domain" => "",
			"cache_users" => "0",
			"register_email_confirm" => "0",
			"default_template" => "default",
			"default_language" => "english",
			"use_cookies" => "1",
			"use_bcc" => "1",
			"internal_version" => "" . PHORUMINTERNAL . "",
			"PROFILE_FIELDS" => array("real_name"),
			"enable_pm" => "1",
			"user_edit_timelimit" => "0",
			"enable_new_pm_count" => "1",
			"enable_dropdown_userlist" => "1",
			"enable_moderator_notifications" => "1",
			"show_new_on_index" => "1",
			"dns_lookup" => "1",
			"tz_offset" => "0",
			"user_time_zone" => "1",
			"user_template" => "0",
			"registration_control" => "1",
			"file_uploads" => "0",
			"file_types" => "",
			"max_file_size" => "",
			"file_space_quota" => "",
			"file_offsite" => "0",
			"system_email_from_name" => "",
			"hide_forums" => "1",
			"enable_new_pm_count" => "1",
			"track_user_activity" => "0",
			"html_title" => "Phorum",
			"head_tags" => ""
          );

        phorum_db_update_settings($settings);
    
        // insert the default module settings
        // hooks
        mysql_query("INSERT INTO {$PHORUM['settings_table']} (`name`, `type`, `data`) VALUES ('hooks','S','a:1:{s:6:\"format\";a:2:{s:4:\"mods\";a:2:{i:0;s:7:\"smileys\";i:1;s:6:\"bbcode\";}s:5:\"funcs\";a:2:{i:0;s:18:\"phorum_mod_smileys\";i:1;s:14:\"phorum_bb_code\";}}}')");

        // enabled modules
        mysql_query("INSERT INTO {$PHORUM['settings_table']} (`name`, `type`, `data`) VALUES ('mods','S','a:4:{s:4:\"html\";s:1:\"0\";s:7:\"replace\";s:1:\"0\";s:7:\"smileys\";s:1:\"1\";s:6:\"bbcode\";s:1:\"1\";}')");

        // default settings for smiley module
        mysql_query("INSERT INTO {$PHORUM['settings_table']} (`name`, `type`, `data`) VALUES ('mod_smileys','S','a:21:{i:0;a:4:{s:6:\"search\";s:2:\"B)\";s:6:\"smiley\";s:8:\"cool.gif\";s:3:\"alt\";s:11:\"cool smiley\";s:4:\"uses\";s:1:\"2\";}i:1;a:4:{s:6:\"search\";s:2:\":)\";s:6:\"smiley\";s:11:\"smilie1.gif\";s:3:\"alt\";s:14:\"smiling smiley\";s:4:\"uses\";s:1:\"2\";}i:2;a:4:{s:6:\"search\";s:2:\":(\";s:6:\"smiley\";s:11:\"smilie2.gif\";s:3:\"alt\";s:10:\"sad smiley\";s:4:\"uses\";s:1:\"2\";}i:3;a:4:{s:6:\"search\";s:2:\";)\";s:6:\"smiley\";s:11:\"smilie3.gif\";s:3:\"alt\";s:14:\"winking smiley\";s:4:\"uses\";s:1:\"2\";}i:4;a:4:{s:6:\"search\";s:2:\":o\";s:6:\"smiley\";s:11:\"smilie4.gif\";s:3:\"alt\";s:14:\"yawning smiley\";s:4:\"uses\";s:1:\"2\";}i:5;a:4:{s:6:\"search\";s:2:\":D\";s:6:\"smiley\";s:11:\"smilie5.gif\";s:3:\"alt\";s:15:\"grinning smiley\";s:4:\"uses\";s:1:\"2\";}i:6;a:4:{s:6:\"search\";s:2:\":P\";s:6:\"smiley\";s:11:\"smilie6.gif\";s:3:\"alt\";s:26:\"tongue sticking out smiley\";s:4:\"uses\";s:1:\"2\";}i:7;a:4:{s:6:\"search\";s:3:\"B)-\";s:6:\"smiley\";s:11:\"smilie7.gif\";s:3:\"alt\";s:14:\"smoking smiley\";s:4:\"uses\";s:1:\"2\";}i:8;a:4:{s:6:\"search\";s:3:\"8-)\";s:6:\"smiley\";s:11:\"smilie8.gif\";s:3:\"alt\";s:18:\"eye rolling smiley\";s:4:\"uses\";s:1:\"2\";}i:9;a:4:{s:6:\"search\";s:2:\":X\";s:6:\"smiley\";s:11:\"smilie9.gif\";s:3:\"alt\";s:12:\"angry smiley\";s:4:\"uses\";s:1:\"2\";}i:10;a:4:{s:6:\"search\";s:3:\"(:D\";s:6:\"smiley\";s:12:\"smiley12.gif\";s:3:\"alt\";s:23:\"smiling bouncing smiley\";s:4:\"uses\";s:1:\"2\";}i:11;a:4:{s:6:\"search\";s:4:\">:D<\";s:6:\"smiley\";s:12:\"smiley14.gif\";s:3:\"alt\";s:16:\"thumbs up smiley\";s:4:\"uses\";s:1:\"2\";}i:12;a:4:{s:6:\"search\";s:4:\":)-D\";s:6:\"smiley\";s:12:\"smiley15.gif\";s:3:\"alt\";s:17:\"smileys with beer\";s:4:\"uses\";s:1:\"2\";}i:13;a:4:{s:6:\"search\";s:3:\":)o\";s:6:\"smiley\";s:12:\"smiley16.gif\";s:3:\"alt\";s:15:\"drinking smiley\";s:4:\"uses\";s:1:\"2\";}i:14;a:4:{s:6:\"search\";s:2:\":?\";s:6:\"smiley\";s:12:\"smiley17.gif\";s:3:\"alt\";s:12:\"moody smiley\";s:4:\"uses\";s:1:\"2\";}i:15;a:4:{s:6:\"search\";s:4:\"(td)\";s:6:\"smiley\";s:12:\"smiley23.gif\";s:3:\"alt\";s:11:\"thumbs down\";s:4:\"uses\";s:1:\"2\";}i:16;a:4:{s:6:\"search\";s:4:\"(tu)\";s:6:\"smiley\";s:12:\"smiley24.gif\";s:3:\"alt\";s:9:\"thumbs up\";s:4:\"uses\";s:1:\"2\";}i:17;a:4:{s:6:\"search\";s:4:\"(:P)\";s:6:\"smiley\";s:12:\"smiley25.gif\";s:3:\"alt\";s:39:\"spinning smiley sticking its tongue out\";s:4:\"uses\";s:1:\"2\";}i:18;a:4:{s:6:\"search\";s:2:\"X(\";s:6:\"smiley\";s:7:\"hot.gif\";s:3:\"alt\";s:10:\"hot smiley\";s:4:\"uses\";s:1:\"2\";}i:19;a:4:{s:6:\"search\";s:2:\":S\";s:6:\"smiley\";s:12:\"smilie11.gif\";s:3:\"alt\";s:15:\"confused smiley\";s:4:\"uses\";s:1:\"2\";}i:20;a:4:{s:6:\"search\";s:3:\"::o\";s:6:\"smiley\";s:12:\"smilie10.gif\";s:3:\"alt\";s:18:\"eye popping smiley\";s:4:\"uses\";s:1:\"2\";}}')");

        // create a test forum
        $forum=array(
                  "name"=>'Test Forum',
                  "active"=>1,
                  "description"=>'This is a test forum.  Feel free to delete it or edit after installation.',
                  "template"=>'default',
                  "folder_flag"=>0,
                  "parent_id"=>0,
                  "list_length_flat"=>30,
                  "list_length_threaded"=>15,
                  "read_length"=>20,
                  "moderation"=>0,
                  "threaded_list"=>0,
                  "threaded_read"=>0,				  
                  "float_to_top"=>1,
                  "display_ip_address"=>0,
                  "allow_email_notify"=>1,
                  "language"=>'english',
                  "email_moderators"=>0,
                  "display_order"=>0,
                  "edit_post"=>1,
                  "pub_perms" =>  1,
                  "reg_perms" =>  15
              );

        $GLOBALS["PHORUM"]['forum_id']=phorum_db_add_forum($forum);
        
        // create a test post
        $message=array(
                    "forum_id" => $GLOBALS['PHORUM']["forum_id"],
                    "thread" => 0,
                    "parent_id" => 0,
                    "author" => 'Phorum Installer',
                    "subject" => 'Test Message',
                    "email" => '',
                    "ip" => '127.0.0.1',
                    "user_id" => 0,
                    "moderator_post" => 0,
                    "status" => PHORUM_STATUS_APPROVED,
                    "sort" => PHORUM_SORT_DEFAULT,
                    "msgid" => '',
                    "body" => "This is a test message.  You can delete it after install using the admin.\n\nPhorum 5 Team"
                 );

        phorum_db_post_message($message);

        include_once ("./include/thread_info.php");

        phorum_update_thread_info($message["thread"]);

        phorum_db_update_forum_stats(true);

    }

    return $retmsg;
}

/**
 * This function goes through an array of queries and executes them
 */

function phorum_db_run_queries($queries){
    $PHORUM = $GLOBALS["PHORUM"];

    $conn = phorum_db_mysql_connect();

    $retmsg = "";

    foreach($queries as $sql){
        $res = mysql_query($sql, $conn);
        if ($err = mysql_error()){
            // skip duplicate column name errors
            if(!stristr($err, "duplicate column")){
                $retmsg.= "$err<br />";
                phorum_db_mysql_error("$err: $sql");
            }
        }
    }

    return $retmsg;
}

/**
 * This function checks that a database connection can be made.
 */

function phorum_db_check_connection(){
    $conn = phorum_db_mysql_connect();

    return ($conn > 0) ? true : false;
}

/**
 * handy little connection function.  This allows us to not connect to the
 * server until a query is actually run.
 * NOTE: This is not a required part of abstraction
 */

function phorum_db_mysql_connect(){
    $PHORUM = $GLOBALS["PHORUM"];

    static $conn;
    if (empty($conn)){
        $conn = mysql_connect($PHORUM["DBCONFIG"]["server"], $PHORUM["DBCONFIG"]["user"], $PHORUM["DBCONFIG"]["password"], true);
        mysql_select_db($PHORUM["DBCONFIG"]["name"], $conn);
    }
    return $conn;
}

/**
 * error handling function
 * NOTE: This is not a required part of abstraction
 */

function phorum_db_mysql_error($err){
    if (!defined("PHORUM_ADMIN")){
        echo htmlspecialchars($err);
        exit();
    }else{
        echo "<!-- $err -->";
    }
}

?>
