<?php

////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//   Copyright (C) 2010  Phorum Development Team                              //
//   http://www.phorum.org                                                    //
//                                                                            //
//   This program is free software. You can redistribute it and/or modify     //
//   it under the terms of either the current Phorum License (viewable at     //
//   phorum.org) or the Phorum License that was distributed with this file    //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   You should have received a copy of the Phorum License                    //
//   along with this program.                                                 //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

if(!defined("PHORUM_ADMIN")) return;

include_once("./include/api/base.php");
include_once("./include/api/custom_profile_fields.php");

// Create or update a custom profile field.
if(count($_POST) && $_POST['name'] != '')
{
    $_POST['curr'] = $_POST['curr'] == 'NEW' ? 'NEW' : (int)$_POST['curr'];
    $_POST['name'] = trim($_POST['name']);
    $_POST['length'] = (int)$_POST['length'];
    $_POST['html_disabled'] = !empty($_POST['html_disabled']) ? 1 : 0;
    $_POST['show_in_admin'] = !empty($_POST['show_in_admin']) ? 1 : 0;

    // Check if there is a deleted field with the same name.
    // If this is the case, then we want to give the admin a chance
    // to restore the deleted field.
    $check = phorum_api_custom_profile_field_byname($_POST['name']);
    if ($check !== FALSE && !empty($check["deleted"]))
    {
      // Handle restoring a deleted field.
      if (isset($_POST["restore"])) {
        if (phorum_api_custom_profile_field_restore($check["id"]) === FALSE) {
            phorum_admin_error(phorum_api_strerror());
        } else {
            phorum_admin_okmsg("The custom profile field " .
                               "\"{$check["name"]}\" has been restored.");
        }

        // Empty the POST array, so the code below won't try to
        // create or update a field.
        $_POST = array();
      }

      // Handle hard deleting a deleted field, so a new field with
      // the same name can be created.
      elseif (isset($_POST["create"])) {
          phorum_api_custom_profile_field_delete($check["id"], TRUE);
      }

      // Ask the admin what to do.
      else
      { ?>
        <div class="PhorumInfoMessage">
          <strong>Restore deleted field?</strong><br/></br>
          A previously deleted custom profile field with the same name
          "<?php print htmlspecialchars($_POST['name']) ?>"
          was found.<br /><br />
          If you accidentally deleted that old field, then
          you can choose to restore the old field's configuration and
          data. You can also create a totally new field and ignore
          the deleted field. What do you want to do?<br/><br/>
          <form action="<?php echo phorum_admin_build_url('base'); ?>" method="post">
            <input type="hidden" name="phorum_admin_token" 
                value="<?php echo $PHORUM['admin_token'];?>" />
            <input type="hidden" name="module"
                value="<?php print $module; ?>" />
            <input type="hidden" name="curr"
                value="<?php print htmlspecialchars($_POST['curr']) ?>" />
            <input type="hidden" name="name"
                value="<?php print htmlspecialchars($_POST['name']) ?>" />
            <input type="hidden" name="length"
                value="<?php print htmlspecialchars($_POST['length']) ?>" />
            <input type="hidden" name="html_disabled"
                value="<?php print htmlspecialchars($_POST['html_disabled']) ?>" />
            <input type="hidden" name="show_in_admin"
                value="<?php print htmlspecialchars($_POST['show_in_admin']) ?>" />
            <input type="submit" name="restore" value="Restore deleted field" />
            <input type="submit" name="create" value="Create new field" />
          </form>
        </div>
        <?php
        return;
      }
    }

    // $_POST could have been emptied in the previous code.
    if (count($_POST))
    {
        // Create or update the custom profile field.
        $field = array(
            'id'            => $_POST['curr'] == 'NEW' ? NULL : $_POST['curr'],
            'name'          => $_POST['name'],
            'length'        => $_POST['length'],
            'html_disabled' => $_POST['html_disabled'],
            'show_in_admin' => $_POST['show_in_admin'],
        );
        $field = phorum_api_custom_profile_field_configure($field);

        if ($field === FALSE) {
            $error = phorum_api_strerror();
            $action = $_POST['curr'] == 'NEW' ? "create" : "update";
            phorum_admin_error("Failed to $action profile field: ".$error);
        } else {
            $action = $_POST['curr'] == 'NEW' ? "created" : "updated";
            phorum_admin_okmsg("Profile field $action");
        }
    }
}

// Confirm deleting a profile field.
if (isset($_GET["curr"]) && isset($_GET["delete"]))
{ ?>
  <div class="PhorumInfoMessage">
    Are you sure you want to delete this custom profile field?
    <br/><br/>
    <form action="<?php echo phorum_admin_build_url('base'); ?>" method="post">
      <input type="hidden" name="phorum_admin_token" value="<?php echo $PHORUM['admin_token'];?>" />
      <input type="hidden" name="module" value="<?php print $module; ?>" />
      <input type="hidden" name="curr" value="<?php print (int) $_GET['curr']; ?>" />
      <input type="hidden" name="delete" value="1" />
      <input type="submit" name="confirm" value="Yes" />
      <input type="submit" name="confirm" value="No" />
    </form>
  </div>
  <?php
  return;
}

// Delete a custom profile field after confirmation.
if (isset($_POST["curr"]) && isset($_POST["delete"]) &&
    $_POST["confirm"] == "Yes") {
    phorum_api_custom_profile_field_delete((int)$_POST["curr"]);
    phorum_admin_okmsg("Profile field deleted");
}

// Check if we are in create or edit mode.
$curr = isset($_GET['curr']) ? (int)$_GET['curr'] : "NEW";
$field = ($curr != 'NEW' && isset($PHORUM['PROFILE_FIELDS'][$curr]))
       ? $PHORUM['PROFILE_FIELDS'][$curr] : NULL;

// Setup data for create mode.
if ($field === NULL) {
    $name          = '';
    $length        = 255;
    $html_disabled = 1;
    $show_in_admin = 0;
    $title         = "Add A Profile Field";
    $submit        = "Add";
// Setup data for edit mode.
} else {
    $name          = $field['name'];
    $length        = $field['length'];
    $html_disabled = $field['html_disabled'];
    $show_in_admin = isset($field['show_in_admin'])
                   ? $field['show_in_admin'] : 0;
    $title         = "Edit Profile Field";
    $submit        = "Update";
}

// Display the custom profile field editor.
include_once "./include/admin/PhorumInputForm.php";

$frm = new PhorumInputForm ("", "post", $submit);
$frm->hidden("module", "customprofile");
$frm->hidden("curr", "$curr");

$frm->addbreak($title);

$row = $frm->addrow("Field Name", $frm->text_box('name', $name, 50));
$frm->addhelp($row, "Field Name", "This is the name to assign to the custom profile field. Because it must be possible to use this name as the name property for an input element in an HTML form, there are a few restrictions to it:<br/><ul><li>it can only contain letters, numbers<br/> and underscores (_);</li><li>it must start with a letter.</li></ul>");

$frm->addrow("Field Length (Max. ".PHORUM_MAX_CPLENGTH.")", $frm->text_box("length", $length, 50));

$row = $frm->addrow("Disable HTML", $frm->checkbox("html_disabled",1,"Yes",$html_disabled));
$frm->addhelp($row, "Disable HTML", "
    If this option is enabled, then HTML code will not be usable
    in this field. When displaying the custom field's data,
    Phorum will automatically replace special HTML characters
    with their safe HTML counter parts.<br/>
    <br/>
    There are two possible reasons for disabling it:<br/>
    <ol>
      <li>You need HTML in this field and run a module which formats
          the field data into safe html (before storing it to the database
          or before displaying it on screen).
      <li>You run a module that needs to store an array in the field.
    </ol>
    So in practice, you only disable this option if module documentation tells
    you to do so or if you are writing a module which needs this. If you don't
    understand what's going on here, then don't disable the option.<br/>
    <br/>
    To learn about the security risks involved, search for \"XSS\" and
    \"cross site scripting\" on the internet.");

$row = $frm->addrow("Show in user admin", $frm->checkbox("show_in_admin",1,"Yes",$show_in_admin));
$frm->addhelp($row, "Show in user admin", "If this option is enabled, then the contents of the field will be displayed on the user details page in the Phorum admin interface (section \"Edit Users\").");

$frm->show();

// If we are not in edit mode, we show the list of available profile fields.
if ($curr == "NEW")
{
    print "Creating a custom profile field here merely allows for the use
           of the field. If you want to use it as an extra info field for
           your users, you will need to edit the register, control center
           and profile templates to actually allow users to enter data in
           the fields and have it stored. You will have to use the name
           you enter here as the name property of the HTML form element.
           <hr class=\"PhorumAdminHR\" />";

    if (isset($PHORUM['PROFILE_FIELDS']["num_fields"]))
        unset($PHORUM['PROFILE_FIELDS']["num_fields"]);

    $active_fields = 0;
    foreach($PHORUM["PROFILE_FIELDS"] as $f) {
        if (empty($f['deleted'])) $active_fields ++;
    }

    if ($active_fields > 0)
    { ?>
        <table border="0" cellspacing="1" cellpadding="0"
               class="PhorumAdminTable" width="100%">
        <tr>
          <td class="PhorumAdminTableHead">Field</td>
          <td class="PhorumAdminTableHead">Length</td>
          <td class="PhorumAdminTableHead">HTML disabled</td>
          <td class="PhorumAdminTableHead">&nbsp;</td>
        </tr> <?php

        foreach($PHORUM["PROFILE_FIELDS"] as $key => $item)
        {
            // Do not show deleted fields.
            if (!empty($item['deleted'])) continue;

            $edit_url = phorum_admin_build_url(array('module=customprofile','edit=1',"curr=$key"));
            $delete_url = phorum_admin_build_url(array('module=customprofile','delete=1',"curr=$key"));
            
            print "<tr>\n";
            print "  <td class=\"PhorumAdminTableRow\">".$item['name']."</td>\n";
            print "    <td class=\"PhorumAdminTableRow\">".$item['length']."</td>\n";
            print "    <td class=\"PhorumAdminTableRow\">".($item['html_disabled']?"Yes":"No")."</td>\n";
            print "    <td class=\"PhorumAdminTableRow\"><a href=\"$edit_url\">Edit</a>&nbsp;&#149;&nbsp;<a href=\"$delete_url\">Delete</a></td>\n";
            print "</tr>\n";
        }
        print "</table>\n";

    } else {
        echo "There are currently no custom profile fields configured.";
    }
}
?>
