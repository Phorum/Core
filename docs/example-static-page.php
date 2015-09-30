<?php

// Static Page Example
// If you want some static pages inside your Phorum which uses the template
// style, you can use this file as a master. Copy -and rename- the file to
// your Phorum root. Customize it for your needs.

define('phorum_page', 'example-static-page');
require_once './common.php';

// Include necessary API classes
// require_once PHORUM_PATH.'/include/api/xxx/xxx.php';

// Set heading, description and breadcrumps
$PHORUM['DATA']['HEADING'] = 'Static Page Example'.(isset($PHORUM['name']) ? ' - '.$PHORUM['name'] : '');
$PHORUM['DATA']['HTML_DESCRIPTION']
  = 'Some description for this page.';
$PHORUM['DATA']['BREADCRUMBS'][] = array(
    'URL'  => '/example-static-page.php?'.$PHORUM['forum_id'],
    'TEXT' => 'Static Page Example'
);

// Set all our URL's
phorum_build_common_urls();

include phorum_get_template('header');
phorum_hook('after_header');

?>
  <h1>Static Page Example</h1>
  <p>
    Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy
    eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam
    voluptua.
  </p>
<?php

// You can also include own template files.
// The file must exist under /templates/yourtemplate/
// include phorum_get_template('static_page');

// Find more useful information inside $PHORUM['DATA']
// var_dump($PHORUM['DATA']);

phorum_hook('before_footer');
include phorum_get_template('footer');

?>
