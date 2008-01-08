<?php
if (!defined("PHORUM")) return;

require_once("./mods/spamhurdles/lib/iscramble.php");
require_once("./mods/spamhurdles/defaults.php");
require_once("./mods/spamhurdles/db.php");

define("KEY_NOTAVAIL", -1);
define("KEY_AVAIL", 0);
define("KEY_EXPIRED", 1);

// This definition defines the chance (in %) that the garbage
// collection is run on the database, to clear out expired items.
define("SPAMHURDLES_GARBAGE_COLLECTION_RATE", 1);

// Register the additional CSS code for this module.
function phorum_mod_spamhurdles_css_register($data)
{
    if ($data['css'] != 'css') return $data;

    $data['register'][] = array(
        "module" => "spamhurdles",
        "where"  => "after",
        "source" => "file(mods/spamhurdles/spamhurdles.css)"
    );
    return $data;
}

function phorum_mod_spamhurdles_common()
{
    global $PHORUM;

    // Check and handle automatic installation and upgrading
    // of the database structure. Do not continue running the
    // Spam Hurldes module in case the installation fails.
    if (! spamhurdles_db_install()) return;

    // Garbage collection for cleaning up expired items from the database.
    if (rand(1,100) <= SPAMHURDLES_GARBAGE_COLLECTION_RATE) {
        spamhurdles_db_remove_expired();
    }

    // See if the old post.php script is called. This one is sometimes
    // left behind on systems after upgrading, but it should no longer
    // be used for posting. Since this script is not protected against
    // spamming, we fully deny access to it here.
    if (strstr($_SERVER["PHP_SELF"], "post.php")) {
        die("Illegal access to post.php. That script is the message posting " .
            "script for Phorum 5.0. Phorum 5.1 and higher use posting.php " .
            "instead. The post.php script might be a left-behind from a " .
            "Phorum 5.0 upgrade. It should be removed from the server.");
    }

    $conf = $PHORUM["mod_spamhurdles"];

    // See if there's a spamhurdles key available in the request.
    $key = NULL;
    if (isset($PHORUM["args"]["spokencaptcha"])) {
        $key = $PHORUM["args"]["spokencaptcha"];
    } elseif (isset($PHORUM["args"]["imagecaptcha"])) {
        $key = $PHORUM["args"]["imagecaptcha"];
    } elseif (isset($_POST["spamhurdles_key"])) {
        $key = $_POST["spamhurdles_key"];
    }

    // Our main data storage for this module.
    $PHORUM["SPAMHURDLES"] = NULL;

    // If there was a key, then retrieve the spamhurdles information for
    // that key from the database. If there was no key or an expired
    // key, keep track of that (used in case we want to post
    // messages with an expired key as unapproved).
    $PHORUM["SPAMHURDLES_KEY_STATUS"] = KEY_NOTAVAIL;
    if ($key !== NULL) {
       $spamhurdles = spamhurdles_db_get($key);
       if (!empty($spamhurdles)) {
           $PHORUM["SPAMHURDLES"] = $spamhurdles;
           $PHORUM["SPAMHURDLES_KEY_STATUS"] = KEY_AVAIL;
       } else {
           // Early bail out option for expired or already used keys,
           if (phorum_page == "post" &&
               do_spamhurdle("blockmultipost") &&
               $conf["blockaction"] == "blockerror") {
               spamhurdle_blockerror();
           }

           $PHORUM["SPAMHURDLES_KEY_STATUS"] = KEY_EXPIRED;
       }
    }

    // If a spoken captcha is requested, then generate a wav file for it
    // and stream it to the user.
    if (isset($PHORUM["args"]["spokencaptcha"])) {
        include("./mods/spamhurdles/captcha/spoken_captcha.php");
        exit(0);
    }

    // If a CAPTCHA image is requested, then let the CAPTCHA class that we
    // used for generating the CAPTCHA generate the image (this class must
    // have a generate_image() method for doing this).
    if (isset($PHORUM["args"]["imagecaptcha"])) {
        if (isset($PHORUM["SPAMHURDLES"]["captcha"]["question"]) &&
            isset($PHORUM["SPAMHURDLES"]["captcha_class"])) {
            $question = $PHORUM["SPAMHURDLES"]["captcha"]["question"];
            $class = $PHORUM["SPAMHURDLES"]["captcha_class"];
            require_once("./mods/spamhurdles/captcha/class.{$class}.php");
            $captcha = new $class();
            $captcha->generate_image($question);
            exit(0);
        } else {
            die("<h1>Internal Spam Hurdles module error</h1>" .
                "Image captcha requested, but not all spamhurdles " .
                "info is available.");
        }
    }

    // See if we switched to another key. If this is the case, we
    // can delete the previously cashed key's data.
    if (isset($_COOKIE["mod_spamhurdles_key"])) {
        $oldkey = $_COOKIE["mod_spamhurdles_key"];
        if (isset($key) && $oldkey !== $key) {
            phorum_mod_spamhurdles_cleanup_key($oldkey);
            setcookie("mod_spamhurdles_key", "", time()-86400);
        }
    }

    // We use (a modified version of) iScramble on several occasions.
    // Load a little javascript function that we need for it.
    $PHORUM['DATA']['HEAD_TAGS'] .= iScramble_javascript();
}

// Generic function for initializing spam hurdles for a form.
// The $type parameter tells which form to handle.
function phorum_mod_spamhurdles_init($type, $extrafields = NULL)
{
    global $PHORUM;

    // Retrieve the module configuration.
    $conf = $PHORUM["mod_spamhurdles"];

    // Keep track if we want to save new spamhurdles info to the database.
    $store = FALSE;

    // Generate a spamhurdles key for the posting if this wasn't done yet.
    if (! isset($PHORUM["SPAMHURDLES"]["key"]))
    {
        // Generate a random spamhurdle key. The chance that we make
        // up an already existing key is ehh... about zero, but since
        // it's not hard to check for duplicates, a check is run for
        // that very special occasion.
        while (TRUE) {
            $key = phorum_mod_spamhurdles_keygen();
            if (! spamhurdles_db_get($key)) break;
        }

        $PHORUM["SPAMHURDLES"] = array(
            "create_time"      => time(),
            "key"              => $key,
            "form_type"        => $type,
            "prev_key_expired" => FALSE,
        );
        $store = TRUE;
    }

    // Generate a CAPTCHA, if required.
    if (!isset($PHORUM["SPAMHURDLES"]["captcha"]) &&
        (($type == "posting" && do_spamhurdle("posting_captcha")) ||
        ($type == "register" && do_spamhurdle("register_captcha")) ||
        ($type == "external_captcha")) ) {

        $class = "captcha_" . $conf["captcha_type"];
        require_once("./mods/spamhurdles/captcha/class.{$class}.php");
        $captcha = new $class();
        $captcha = $captcha->generate_captcha();
        $PHORUM["SPAMHURDLES"]["captcha_class"] = $class;
        $PHORUM["SPAMHURDLES"]["captcha"] = $captcha;
        $store = TRUE;
    }

    // Only for posting messages:
    // Generate a signkey for the MD5 javascript signing check.
    if (! isset($PHORUM["SPAMHURDLES"]["signkey"]) &&
        do_spamhurdle("jsmd5check") &&
        $type == "posting") {

        $signkey = phorum_mod_spamhurdles_keygen();
        $PHORUM["SPAMHURDLES"]["signkey"] = $signkey;
        $store = TRUE;
    }

    // If we encountered an expired key, then keep track of this
    // in the new spamhurdles data.
    if ($PHORUM["SPAMHURDLES_KEY_STATUS"] == KEY_EXPIRED) {
        $PHORUM["SPAMHURDLES"]["prev_key_expired"] = TRUE;
        $store = TRUE;
    }

    // Add fields that were passed in the $extrafields argument.
    if (is_array($extrafields)) {
        foreach ($extrafields as $k => $v) {
            $PHORUM["SPAMHURDLES"][$k] = $v;
        }
        $store = TRUE;
    }

    // Store new spamhurdles information in the database.
    if ($store) {
        spamhurdles_db_put(
            $PHORUM["SPAMHURDLES"]["key"],
            $PHORUM["SPAMHURDLES"],
            $conf["key_max_ttl"]
        );
        spamhurdles_db_put(
            $PHORUM["SPAMHURDLES"]["key"],
            $PHORUM["SPAMHURDLES"],
            $conf["key_max_ttl"]
        );
    }

    return $PHORUM["SPAMHURDLES"];
}

// Generic function for bulding the HTML code that needs to go in
// a form that is protected by the spamhurdles module.
// The $type parameter tells which form to handle. Supported
// types are:
// - posting           Posting a message
// - register          Registering a new account
// - external_capthca  A special option to allow other pages to
//                     use the captcha functionality of this module
function phorum_mod_spamhurdles_build_form($type)
{
    $PHORUM = $GLOBALS["PHORUM"];
    $conf = $GLOBALS["PHORUM"]["mod_spamhurdles"];
    $spamhurdles = $PHORUM["SPAMHURDLES"];
    $key = $spamhurdles["key"];

    // Keep track of the type of form that we are showing. This is used
    // in the before_footer hook to determine what to show.
    $GLOBALS["PHORUM"]["SPAMHURDLES"]["shown_form"] = $type;

    // Add the spamhurdles key to the form.
    ?>
    <input type="hidden"
           name="spamhurdles_key"
           id="spamhurdles_key_field"
           value="<?php print $key ?>" /> <?php

    // Add some javascript to set the current key in a cookie.
    // That way we can detect changes in the key through the cookie
    // (used for cleaning up old data).
    ?>
    <script type="text/javascript">
    //<![CDATA[
    var today = new Date();
    var expire = new Date();
    expire.setTime(today.getTime() + 3600000*24*30);
    document.cookie = "mod_spamhurdles_key=<?php print $key?>;expires="+
                      expire.toGMTString();
    //]]>
    </script> <?php

    // Add data for the HTML commented form field check.
    // This is just a bogus forum field, wrapped in an HTML comment.
    if (do_spamhurdle("commentfieldcheck"))
    {
        print "<!-- \n";
        print '<input type="text" name="commentname" value="">';
        print "\n-->\n";
    }

    // Add data for the Javascript MD5 signing check.
    // This is only done for the posting editor.
    if ($type == "posting" && do_spamhurdle("jsmd5check"))
    {
        ob_start(); ?>

        <img style="display:none"
             src="<?php print $PHORUM["http_path"] ?>/mods/spamhurdles/lib/pixel.gif"
             alt="<?php print $spamhurdles["signkey"] ?>"
             id="spamhurdles_img" />
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        print iScramble($html, FALSE, FALSE, '');
        ?>
        <script type="text/javascript" src="<?php print $PHORUM["http_path"] ?>/mods/spamhurdles/lib/md5.js"> </script>
        <?php
    }

    // Show a CAPTCHA if one was generated earlier on.
    if (isset($PHORUM["SPAMHURDLES"]["captcha"]))
    {
        $captcha = $PHORUM["SPAMHURDLES"]["captcha"]["html_form"];

        // The actual value in the captcha is named {FIELDVALUE} in the
        // generated captcha HTML code. Replace it with the actual value.
        $fn = $PHORUM["SPAMHURDLES"]["captcha"]["input_fieldname"];
        $fieldvalue = isset($_POST[$fn]) ? $_POST[$fn] : "";
        $captcha = str_replace("{FIELDVALUE}", $fieldvalue, $captcha);

        // Replace SPOKENURL with the URL for the spoken captcha code.
        if ($conf["spoken_captcha"] && file_exists($conf["flite_location"])) {
            $url = phorum_get_url(PHORUM_INDEX_URL, "spokencaptcha=" . $key);
            $captcha = str_replace("{SPOKENURL}", $url, $captcha);
        }

        // Replace IMAGE with the URL for the captcha image.
        $url = phorum_get_url(PHORUM_INDEX_URL, "imagecaptcha=" . $key);
        $captcha = str_replace("{IMAGEURL}", $url, $captcha);

        // A extra outer div with a class assignment to make the CAPTCHA
        // look good in the Phorum 5.1 default template.
        if ($type == "posting") {
            print '<div class="PhorumStdBlockHeader PhorumNarrowBlock">';
        }

        print $captcha;

        if ($type == "posting") {
            print "</div>";
        }
    }
}

// Generic function for checking submission of a form that is protected
// by the spamhurdles module. The $type parameter tells which form to handle.
function phorum_mod_spamhurdles_run_submitcheck($type)
{
    $PHORUM = $GLOBALS["PHORUM"];
    $spamhurdles = $PHORUM["SPAMHURDLES"];
    $conf = $PHORUM["mod_spamhurdles"];
    $do_block = FALSE;

    // We should have spamhurdles information at all time. If not, then
    // this probably means, somebody is trying to post data directly to
    // the form or is trying to repost using an already expired/used key.
    if (! isset($PHORUM["SPAMHURDLES"]["key"])) {
        // If we did not enable multipost blocking, then initialize
        // spamhurdles data and let the other checks do their work.
        // They will automatically fail if they are enabled.
        if (($spamhurdles == NULL || $spamhurdles["prev_key_expired"]) &&
            do_spamhurdle("blockmultipost")) {
            $do_block = TRUE;
        // Initialize spamhurdles information for all other cases.
        // If other checks are enabled, they will take over.
        } else {
            $PHORUM["SPAMHURDLES"] = phorum_mod_spamhurdles_init($type);
            $spamhurdles = $PHORUM["SPAMHURDLES"];
        }
    }

    // If the type of form in the spamhurdles data does not match the
    // real form type, then the key that was used for form type 1 is used
    // in form type 2. This is defenitely data tampering. No friendly
    // warning messages here.
    if (!$do_block && $PHORUM["SPAMHURDLES"]["form_type"] !== $type) {
        spamhurdle_blockerror();
    }

    // Check if the minimum TTL is honoured for the message posting form.
    if (!$do_block && $type == "posting" && do_spamhurdle("blockquickpost")) {
        $delay = $conf["key_min_ttl"] - (time() - $spamhurdles["create_time"]);
        if ($delay > 0) $do_block = TRUE;
    }

    // Check if a HTML commented form field was submitted.
    if (!$do_block && do_spamhurdle("commentfieldcheck")) {
        if (array_key_exists("commentname", $_POST)) {
            $do_block = TRUE;
        }
    }

    // Check if javascript signing was done for the message posting form.
    if (!$do_block && $type == "posting" && do_spamhurdle("jsmd5check")) {
        $sig = md5($spamhurdles["key"] . $spamhurdles["signkey"]);
        if (!isset($_POST["spamhurdles_signature"]) ||
            $_POST["spamhurdles_signature"] != $sig) {
            $do_block = TRUE;
        }
    }

    // Check if the captcha is filled in right.
    if (!$do_block && isset($spamhurdles["captcha"])) {
        $class = $spamhurdles["captcha_class"];
        require_once("./mods/spamhurdles/captcha/class.{$class}.php");
        $captcha = new $class();
        $error = $captcha->check_answer($spamhurdles["captcha"]);
        if ($error) return $error;
    }

    // Handle default blocking case. Which method of blocking to use for
    // message posting, can be configured from the module settings page.
    if ($do_block) {
        if ($type == "posting" && $conf["blockaction"] == "unapprove") {
            phorum_mod_spamhurdles_init($type, array("unapprove" => 1));
            return $PHORUM["DATA"]["LANG"]["mod_spamhurdles"]["PostingUnapproveError"];
        } else {
            spamhurdle_blockerror();
        }
    }

    // All is okay!
    return NULL;
}

// Generic function to cleanup the data for a key from the database.
function phorum_mod_spamhurdles_cleanup_key($key = NULL)
{
    if ($key === NULL && isset($GLOBALS["PHORUM"]["SPAMHURDLES"]["key"])) {
        $key = $GLOBALS["PHORUM"]["SPAMHURDLES"]["key"];
    }
    if ($key !== NULL) {
        spamhurdles_db_remove($key);
    }
}

// Add data to the message posting form.
function phorum_mod_spamhurdles_tpl_editor_before_textarea()
{
    if (phorum_page != "post" && phorum_page != "read") return;

    global $PHORUM;

    // Only run the spamhurdle checks when writing a new message.
    // We do not need the checks for editing existing messages.
    if (isset($PHORUM["DATA"]["POSTING"]["message_id"])) {
        if (!empty($PHORUM["DATA"]["POSTING"]["message_id"])) return;
    } else die("phorum_mod_spamhurdles_tpl_editor_before_textarea(): " .
             "Can't determine whether we're editing a new message");

    // Initialize the spamhurdles.
    phorum_mod_spamhurdles_init("posting");

    // Build the form elements for the spamhurdles.
    phorum_mod_spamhurdles_build_form("posting");
}

// Handle checking of message editor data.
function phorum_mod_spamhurdles_check_post($args)
{
    // Return if another module already set an error.
    if (!empty($args[1])) return $args;

    // Our checks are only needed when finishing a post.
    if (! isset($_POST["finish"])) return $args;

    // Only run the checks if we're editing a new message.
    if (! empty($args[0]["message_id"])) return $args;

    // Run the generic checks.
    $error = phorum_mod_spamhurdles_run_submitcheck("posting");

    return array($args[0], $error);
}

// Handle marking message unapproved if requested by this mod.
function phorum_mod_spamhurdles_pre_post($message)
{
    if (isset($GLOBALS["PHORUM"]["SPAMHURDLES"]["unapprove"]) &&
        $GLOBALS["PHORUM"]["SPAMHURDLES"]["unapprove"]) {
        $message["status"] = PHORUM_STATUS_HOLD;
    }
    return $message;
}

// Cleanup data after posting.
function phorum_mod_spamhurdles_post_post($message)
{
    phorum_mod_spamhurdles_cleanup_key();
    return $message;
}

// Add data to the account registation form.
function phorum_mod_spamhurdles_tpl_register_form()
{
    // Only run this when we enabled the CAPTCHA for registering.
    if (! do_spamhurdle("register_captcha")) return;

    // Initialize the spamhurdles.
    phorum_mod_spamhurdles_init("register");

    // Build the form elements for the spamhurdles.
    phorum_mod_spamhurdles_build_form("register");
}

// Handle checking of registration data.
function phorum_mod_spamhurdles_before_register($user)
{
    // Only run this when we enabled the CAPTCHA for registering.
    if (! do_spamhurdle("register_captcha")) return $user;

    // Return if another module already set an error.
    if (isset($user["error"]) && !empty($user["error"])) return $user;

    // See if the spamhurdles return any error.
    $error = phorum_mod_spamhurdles_run_submitcheck("register");
    if ($error !== NULL) $user["error"] = $error;

    return $user;
}

// Cleanup data after registering.
function phorum_mod_spamhurdles_after_register($user)
{
    phorum_mod_spamhurdles_cleanup_key();
    return $user;
}

// Add data that needs to go to the end of the page.
function phorum_mod_spamhurdles_before_footer()
{
    $PHORUM = $GLOBALS["PHORUM"];

    // Only run this hook if we are handling a protected form.
    if (!isset($PHORUM["SPAMHURDLES"]["shown_form"])) return;

    $type = $PHORUM["SPAMHURDLES"]["shown_form"];
    $conf = $PHORUM["mod_spamhurdles"];

    // If we block quick posts, then disable the submit button temporarily
    // to prevent the user from posting his message to early. This only
    // applies to the message posting form.
    if ($type == "posting" && do_spamhurdle("blockquickpost"))
    {
        // See how long we should delay posting for the current user.
        $spamhurdles = $PHORUM["SPAMHURDLES"];
        $delay = $conf["key_min_ttl"]- (time() - $spamhurdles["create_time"]);
        if ($delay < 0) $delay = 0; ?>

        <script type="text/javascript">
        //<![CDATA[
          var poststr;
          var secondsleft = <?php print $delay ?>;;
          var postbutton;

          if (document.getElementById) {
              var keyfld = document.getElementById("spamhurdles_key_field");
              if (keyfld && keyfld.form) {
                  var f = keyfld.form;
                  if (f.finish) {
                      postbutton = f.finish;
                      poststr = postbutton.value;
                      spamhurdles_postdelay_countdown()
                  }
              }
          }

          function spamhurdles_postdelay_countdown()
          {
              secondsleft --;
              if (secondsleft <= 0) {
                  postbutton.value = poststr;
                  postbutton.disabled = false;
              } else {
                  postbutton.value = poststr + "(" + secondsleft + ")";
                  postbutton.disabled = true;
                  setTimeout('spamhurdles_postdelay_countdown()', 1000);
              }
          }
        //]]>
        </script>
        <?php
    }

    // Javascript MD5 signing check. This code will create the signed
    // value, that will be checked when posting the form. This only
    // applies to the message posting form.
    if ($type == "posting" && do_spamhurdle("jsmd5check"))
    {
        ob_start(); ?>
        <script>
        //<![CDATA[
        if (document.getElementById)
        {
            // Extract the signing key from the page.
            var image = document.getElementById("spamhurdles_img");
            var signkey = image.alt;

            // Extract the key from the page.
            var keyfld = document.getElementById("spamhurdles_key_field");
            var key = keyfld.value;

            // Create spamhurdles signature.
            var signature = hex_md5(key + signkey);

            // Create hidden form field on the fly for storing the signature.
            var f = keyfld.form;
            var fld = document.createElement("input");
            fld.name = "spamhurdles_signature";
            fld.type = "hidden";
            fld.value = signature;
            f.appendChild(fld);
        }
        //]]>
        </script>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        print iScramble($html, FALSE, FALSE, '');
    }

    // Show a CAPTCHA if we have generated one before.
    if (isset($PHORUM["SPAMHURDLES"]["captcha"])) {
        print $PHORUM["SPAMHURDLES"]["captcha"]["html_after_form"];
    }
}

function phorum_mod_spamhurdles_keygen()
{
    // A bit of time, remote info and random data to create a nice key.
    $chars = "0123456789" .
             "abcdefghijklmnopqrstuvwxyz" .
             "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $key = microtime() . ":" .  $_SERVER["REMOTE_ADDR"];
    for ($i = 0; $i<40; $i++) {
        $key .= substr($chars, rand(0, strlen($chars)-1), 1);
    }
    // And MD5 will bring it into a nice shape.
    $key = md5($key);

    return $key;
}

function do_spamhurdle($hurdle)
{
    $conf = $GLOBALS["PHORUM"]["mod_spamhurdles"];
    if (!isset($conf[$hurdle])) die(
        "do_spamhurdle(): Illegal hurdle name: " . htmlspecialchars($hurdle));

    // Registration CAPTCHA is a simple checkbox.
    if ($hurdle == "register_captcha") {
        return $conf[$hurdle] ? TRUE : FALSE;
    }

    // Other hurdles use a user type specification.
    switch ($conf[$hurdle]) {
        case "anonymous":
            return $GLOBALS["PHORUM"]["DATA"]["LOGGEDIN"] ? FALSE : TRUE;
        case "all":
            return TRUE;
        case "none":
            return FALSE;
        default:
            die("Unknown hurdle config value for hurdle " .
                htmlspecialchars($hurdle) .
                ": " . htmlspecialchars($conf[$hurdle]));
    }
}

function spamhurdle_blockerror()
{
    global $PHORUM;
    phorum_build_common_urls();
    $PHORUM["DATA"]["ERROR"] =
        $PHORUM["DATA"]["LANG"]["mod_spamhurdles"]["BlockError"];
    include phorum_get_template("header");
    phorum_hook("after_header");
    include phorum_get_template("message");
    phorum_hook("before_footer");
    include phorum_get_template("footer");
    exit(0);
}

?>
