<?php

$upgrade_queries[]=
    "CREATE TABLE {$PHORUM['message_tracking_table']} (
			track_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			message_id INT UNSIGNED NOT NULL DEFAULT '0',
			user_id INT UNSIGNED NOT NULL DEFAULT '0',
			time INT UNSIGNED NOT NULL DEFAULT '0',
			diff_body TEXT NULL ,
			diff_subject TEXT NULL ,

			PRIMARY KEY track_id (track_id),
			KEY message_id ( message_id )
	   )";

?>
