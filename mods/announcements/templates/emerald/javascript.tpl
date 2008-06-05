// Add newflag clearing function for the announcement module.
// We need separate handling for this, because the announcement message list
// uses different message icons than the standard message list.
if (Phorum.UI && Phorum.UI.markreadSuccess) {
    Phorum.UI.markreadSuccess.push(function (mode, item_id)
    {
        // Clear announcement newflag icons.
        Phorum.UI.markreadHandleMatches(
            'img', 'announcement-new-flag-icon-', mode, item_id,
            function(elt) {
                elt.src = '{URL->TEMPLATE}/images/information.png';
            }
        );
    });
}

