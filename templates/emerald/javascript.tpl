// ---------------------------------------------------------------------
// Javascript code for Emerald template (autoloaded by javascript.php)
// ---------------------------------------------------------------------

// Handle mark read functionality for the index and list page icons.
// These icons are not CSS driven, therefore we cannot make use of the
// built-in actions of Phorum.UI.NewFlags.
Phorum.UI.NewFlags.registerActionCallback('icon', function (elt, $elt) {

  // Update the icon src. For sticky messages, we assign a different icon.
  if ($elt.hasClass('sticky')) {
    elt.src = '{URL->TEMPLATE}/images/bell.png';
  } else {
    elt.src = '{URL->TEMPLATE}/images/comment.png';
  }

});

