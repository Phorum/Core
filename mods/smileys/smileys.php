<?php

if(!defined("PHORUM")) return;

function phorum_mod_smileys ( $data )
{
	// early out if we're not enabled
	if ( ! isset ( $GLOBALS['PHORUM']['mod_smileys'] ) ) {
		return $data;
	}
  if(isset($GLOBALS['PHORUM']['mod_smileys']['prefix'])) {
	  $prefix = $GLOBALS['PHORUM']['mod_smileys']['prefix']; // quicker than array lookups
  } else {
    $prefix = "smileys/";
  }

	$do_work = $do_subject = $do_body = false;
	$smiley_body_key = $smiley_subject_key = $smiley_body_value = $smiley_subject_value = array();

	foreach ( $GLOBALS['PHORUM']['mod_smileys'] as $key=>$smiley ) {

		if ( ! is_long ( $key ) ) {
			continue;
		}

		$do_work = true;

		$smiley['alt'] = htmlspecialchars ( $smiley['alt'] );
		switch ( $smiley['uses'] ) {
			case 1: // subject only replace
				$do_subject = true;
				$smiley_subject_key[] = $smiley['search'];
				$smiley_subject_value[] = '<img title="'.$smiley['alt'].'" alt="'.$smiley['alt'].'" src="'.$prefix.$smiley['smiley'].'" />';
				break;
			case 2: // both replace
				$do_subject = true;
				$smiley_subject_key[] = $smiley['search'];
				$smiley_subject_value[] = '<img title="'.$smiley['alt'].'" alt="'.$smiley['alt'].'" src="'.$prefix.$smiley['smiley'].'" />';
				// ... goes on to body-replace
			case 0: // body only replace
			default: // in old versions it wasnt set, so body only replace
				$do_body = true;
				$smiley_body_key[] = $smiley['search'];
				$smiley_body_value[] = '<img title="'.$smiley['alt'].'" alt="'.$smiley['alt'].'" src="'.$prefix.$smiley['smiley'].'" />';
		}
		unset ( $smiley );

	}
	unset ( $smiley, $prefix );

	// early out if no smileys actually exist
	if ( $do_work !== true ) {
		return $data;
	}

	foreach ( $data as $key=>$message ) {

		if ( $do_subject && isset ( $message['subject'] ) ) {
			$data[$key]['subject'] = str_replace ( $smiley_subject_key, $smiley_subject_value, $message['subject'] );
		}

		if ( $do_body && isset ( $message['body'] ) ) {
			$data[$key]['body'] = str_replace ( $smiley_body_key, $smiley_body_value, $message['body'] );
		}

		unset ( $message, $key );
	}
	unset ( $message, $key );

	return $data;

}

?>
