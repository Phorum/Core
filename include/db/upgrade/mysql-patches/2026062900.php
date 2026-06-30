<?php
if (!defined('PHORUM_ADMIN')) return;

// Widen password columns to hold bcrypt/argon2 hashes (was varchar(50)).
// Drop the userpass index — password hashes should not be indexed.
$upgrade_queries[] = "ALTER TABLE {$PHORUM['user_table']} MODIFY COLUMN password varchar(255) NOT NULL default ''";
$upgrade_queries[] = "ALTER TABLE {$PHORUM['user_table']} MODIFY COLUMN password_temp varchar(255) NOT NULL default ''";
$upgrade_queries[] = "ALTER TABLE {$PHORUM['user_table']} DROP INDEX userpass";
// Widen session ID columns to hold 64-char hex strings (bin2hex(random_bytes(32))).
$upgrade_queries[] = "ALTER TABLE {$PHORUM['user_table']} MODIFY COLUMN sessid_lt varchar(64) NOT NULL default ''";
$upgrade_queries[] = "ALTER TABLE {$PHORUM['user_table']} MODIFY COLUMN sessid_st varchar(64) NOT NULL default ''";
