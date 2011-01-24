<?php
if(!defined("PHORUM_ADMIN")) return;

// wow doing it all by hand this time :(

// adding the new field
$PHORUM['DB']->interact(
    DB_RETURN_RES,
    "ALTER TABLE {$PHORUM['user_newflags_table']}
     ADD   message_id INT UNSIGNED NOT NULL DEFAULT '0'",
    NULL, DB_MASTERQUERY
);

// removing old primary-key
$PHORUM['DB']->interact(
    DB_RETURN_RES,
    "ALTER TABLE {$PHORUM['user_newflags_table']}
     DROP PRIMARY KEY",
    NULL, DB_MASTERQUERY
);

// adding new primary-key
$PHORUM['DB']->interact(
    DB_RETURN_RES,
    "ALTER TABLE {$PHORUM['user_newflags_table']}
     ADD PRIMARY KEY (user_id , forum_id , message_id)",
    NULL, DB_MASTERQUERY
);

// converting the newflags
$rows = $PHORUM['DB']->interact(
    DB_RETURN_ASSOCS,
    "SELECT *
     FROM {$PHORUM['user_newflags_table']}
     WHERE message_id=0",
    NULL, DB_MASTERQUERY
);
$olduser=$GLOBALS['PHORUM']['user']['user_id'];
foreach ($rows as $row)
{
    $forum=$row['forum_id'];
    $data=unserialize($row['newflags']);
    $GLOBALS['PHORUM']['user']['user_id']=$row['user_id'];
    $newdata=array();
    foreach($data as $mid1 => $mid2) {
        if(is_int($mid1)) {
            $newdata[]=array("id"=>$mid1,"forum"=>$forum);
        }
    }
    $PHORUM['DB']->newflag_add_read($newdata);
    unset($data);
    unset($newdata);
}
$GLOBALS['PHORUM']['user']['user_id']=$olduser;

$PHORUM['DB']->interact(
    DB_RETURN_RES,
    "DELETE FROM {$PHORUM['user_newflags_table']}
     WHERE message_id=0",
    NULL, DB_MASTERQUERY
);

// remove old column
$PHORUM['DB']->interact(
    DB_RETURN_RES,
    "ALTER TABLE {$PHORUM['user_newflags_table']}
     DROP newflags",
    NULL, DB_MASTERQUERY
);

?>
