<?php
    // Check for possible collisions between modules.

    $phorum_check = "Modules (hook collision checks)";

    function phorum_check_modules() {
        $PHORUM = $GLOBALS["PHORUM"];

        // For some hooks, we only want one module enabled to
        // prevent collision problems. This is a list of
        // those specific hooks.
        $only_single_mod_allowed = array(
            'quote',
            'send_mail',
        );

        // Check all single mod hooks.
        foreach ($only_single_mod_allowed as $hook) {
            if (isset($PHORUM["hooks"][$hook]["mods"])) {
                $mods = $PHORUM["hooks"][$hook]["mods"];
                if (count($mods) > 1) return array(
                    PHORUM_SANITY_WARN,
                    "You have activated multiple modules that handle
                    Phorum's \"".htmlspecialchars($hook)."\" hook.
                    However, this hook is normally only handled by
                    one module at a time. Keeping all modules
                    activated might lead to some unexpected results.
                    The colliding modules are: ".
                    implode(" + ", $mods) .
                    "<br/><br/>You can ignore this message in case you
                    are sure that the modules can work together"
                );
            }
        }

        // All checks are OK.
        return array(PHORUM_SANITY_OK, NULL);
    }
?>
