<?php

// Releasing Phorum 5.2.16.
// No database upgrades are available for this release, but we do have
// some module updates that require a module info cache refresh.
// Therefore, we introduce this pseudo-db-upgrade, so we force a refresh.

include_once './include/api/modules.php';
phorum_api_modules_save();

