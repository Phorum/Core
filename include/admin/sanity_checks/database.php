<?php
    // Check the database connection and setup. We may want to have finer
    // granulated checks here to give users with problem real good
    // information about what should be fixed, but for that the
    // database layer must be extended. For now it's just a simple
    // connect check that will mostly just sit there and be pretty ;-)
    //
    // Extra checks to think about:
    // - test if all needed permissions are set;
    // - catch the error from the database on connection failure and
    //   try to give the user specific data for fixing the problem.

    $phorum_check = "Database connection";

    function phorum_check_database() {
        $PHORUM = $GLOBALS["PHORUM"];

        // Check if we have a database configuration available.
        if (! isset($PHORUM["DBCONFIG"])) return array(
            PHORUM_SANITY_CRIT,
            "No database configuration was found in your
             environment. Most probably you have not
             copied include/db/config.php.sample to
             include/db/config.php. Read Phorum's
             install.txt for more information."
        );

        // Check if a connection can be made.
        $connected = @phorum_db_check_connection();
        if (! $connected) return array(
            PHORUM_SANITY_CRIT,
            "Connecting to the database failed.
             Please check your database settings in the
             file include/db/conf.php"
        );

        // All checks are OK.
        return array(PHORUM_SANITY_OK, NULL);
    }
?>
