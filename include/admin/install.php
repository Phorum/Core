<?php

    if(!defined("PHORUM_ADMIN")) return;

    if(!phorum_db_check_connection()){
        echo "A database connection could not be established.  Please edit include/db/config.php.";
        return;
    }

    include_once "./include/admin/PhorumInputForm.php";

    if(empty($_POST["step"])){
        $step = 0;
    } else {
        $step = $_POST["step"];
    }

    if(count($_POST)){

        // THIS IS THE WORK STEP

        switch ($step){

            case 5:

                if(!empty($_POST["admin_user"]) && !empty($_POST["admin_pass"]) && !empty($_POST["admin_pass2"]) && !empty($_POST["admin_email"])){
                    if($_POST["admin_pass"]!=$_POST["admin_pass2"]){
                        echo "The password fields do not match<br />";
                        $step=4;
                    } elseif(phorum_user_check_login($_POST["admin_user"], $_POST["admin_pass"])){
                        if($PHORUM["user"]["admin"]){
                            echo "Admin user already exists and has permissions<br />";
                        } else {
                            echo "That user already exists but does not have admin permissions<br />";
                            $step=4;
                        }
                    } else {

                        // add the user
                        $user = array( "username"=>$_POST["admin_user"], "password"=>$_POST["admin_pass"], "email"=>$_POST["admin_email"], "active"=>1, "admin"=>1 );

                        if(!phorum_user_add($user)){

                            echo "There was an error adding the user.<br />";
                            $step=4;
                        }

                        // set the default http_path so we can continue.
                        if(!empty($_SERVER["HTTP_REFERER"])) {
                            $http_path=$_SERVER["HTTP_REFERER"];
                        } elseif(!empty($_SERVER['HTTP_HOST'])) {
                            $http_path="http://".$_SERVER['HTTP_HOST'];                        
                            $http_path.=$_SERVER['PHP_SELF'];
                        } else {
                            $http_path="http://".$_SERVER['SERVER_NAME'];
                            $http_path.=$_SERVER['PHP_SELF'];                            
                        }
                        phorum_db_update_settings(array("http_path"=>dirname($http_path)));
                        phorum_db_update_settings(array("system_email_from_address"=>$_POST["admin_email"]));


                    }
                } else {
                    echo "Please fill in all fields.<br />";
                    $step=4;
                }

                break;
        }

    }

    // THIS IS THE OUTPUT STEP

    switch ($step){

        case 0:

            $frm =& new PhorumInputForm ("", "post", "Continue ->");
            $frm->addbreak("Welcome to Phorum");
            $frm->addmessage("This wizard will setup Phorum on your server.  The first step is to prepare the database.  Phorum has already confirmed that it can connect to your database.  Press continue when you are ready.");
            $frm->hidden("step", "1");
            $frm->show();

            break;
            
        case 1:
            if(isset($PHORUM['internal_version']) && $PHORUM['internal_version'] < PHORUMINTERNAL) {
                $message="Phorum detected that you are running an old version of itself.<br />It will now try to upgrade the tables to the latest version.";
                $setstep=3;
            } else {
                $message="Phorum detected you don't have any tables or another problem on searching the tables.<br />It will now try to create the tables.";
                $setstep=2;
            }
            
            $frm =& new PhorumInputForm ("", "post", "Continue ->");
            $frm->addbreak("Checking for upgrade ....");
            $frm->addmessage($message);
            $frm->hidden("step", $setstep);
            $frm->show();     
            
            break;

        case 2:
            // ok, fresh install

            $err=phorum_db_create_tables();

            if($err){
                $message="Could not create tables, database said:<blockquote>$err</blockquote>";
                $message.="Your database user will need to have create table permissions.  If you know what the error is (tables already exist, etc.) and want to continue, click the button below.";
            } else {
                $message="Tables created.  Next we will check your cache settings. Press continue when ready.";
            }
            
            $frm =& new PhorumInputForm ("", "post", "Continue ->");
            $frm->addbreak("Creating tables....");
            $frm->addmessage($message);
            $frm->hidden("step", "6");
            $frm->show();

            break;
            
        case 3:
            // ok upgrading tables
            $message = phorum_upgrade_tables($PHORUM['internal_version'],PHORUMINTERNAL);
            $frm =& new PhorumInputForm ("", "post", "Continue ->");
            $frm->addbreak("Upgrading tables....");
            $frm->addmessage($message);
            $frm->hidden("step", "5");
            $frm->show();           
            
            break;

        case 4:

            $frm =& new PhorumInputForm ("", "post");
            $frm->addbreak("Creating An Administrator");
            $frm->addmessage("Please enter the following information.  This can be your user information or you can create an administrator that is separate from yourself.<br /><br />Note: If you are using a pre-existing authentication database, please enter the username and password of the admin user that already exists.");
            $frm->hidden("step", "5");
            $frm->addrow("Admin User Name", $frm->text_box("admin_user", "", 30));
            $frm->addrow("Admin Email Address", $frm->text_box("admin_email", "", 30));
            $frm->addrow("Admin Password", $frm->text_box("admin_pass", "", 30, 0, true));
            $frm->addrow("(again)", $frm->text_box("admin_pass2", "", 30, 0, true));
            $frm->show();

            break;

        case 5:

            echo "The setup is complete.  You can now go to <a href=\"$_SERVER[PHP_SELF]\">the admin</a> and start making Phorum all your own.<br /><br /><strong>Here are some things you will want to look at:</strong><br /><br /><a href=\"$_SERVER[PHP_SELF]?module=settings\">The General Settings page</a><br /><br /><a href=\"docs/faq.txt\">The FAQ</a><br /><br /><a href=\"docs/performance.txt\">How to get peak performance from Phorum</a><br /><br /><strong>For developers:</strong><br /><br /><a href=\"docs/creating_mods.txt\">Module Creation</a><br /><br /><a href=\"docs/permissions.txt\">How Phorum permisssions work</a><br /><br /><a href=\"docs/CODING-STANDARDS\">The Phorum Team's codings standards</a>";

            break;

        case 6:
            // try to figure out if we can write to the cache directory
            $message = "";
            error_reporting(0);
            $err = false;
            if ($fp = fopen($PHORUM["cache"] . "/phorum-install-test", "w+")) {
                unlink($PHORUM["cache"] . "/phorum-install-test");
            }
            else {
                // in this case the normal setting is wrong, so try ./cache
                $PHORUM["cache"] = "./cache";
                $settings = array("cache" => $PHORUM["cache"]);
                if (!phorum_db_update_settings($settings)) {
                    $message .= "Database error updating settings.<br />";
                    $err = true;
                }
                elseif ($fp = fopen($PHORUM["cache"] . "/phorum-install-test", "w+")) {
                    unlink($PHORUM["cache"] . "/phorum-install-test");
                }
                else {
                    $err = true;
                }

            }
            error_reporting(E_WARN);
            if($err){
                $message.="Your cache directory is not writable. Please change the permissions on '/cache' inside the Phorum directory to allow writing. In Unix, you may have to use this command: chmod 777 cache<br /><br />If you want to continue anyway and set a cache directory manually, press continue. Note that you must do this, Phorum will not work without a valid cache.";
            } else {
                $message.="Cache directory set.  Next we will create a user with administrator privileges.  Press continue when ready.";
            }
            
            $frm =& new PhorumInputForm ("", "post", "Continue ->");
            $frm->addbreak("Checking cache....");
            $frm->addmessage($message);
            $frm->hidden("step", "4");
            $frm->show();

            break;
    }

?>
