Phorum.UI.notification =
{
    'box': null,   // storage for our box <div>
    'boxtop': 5,   // top notify box offset
    'boxleft': 5,  // left notify box offset
    'count': 0,    // the number of pending actions
    'timer': null, // used for timing the fading effect

    /**
     * Increments the number of pending actions.
     */
    'increment': function() { this.count ++; this.set(); },

    /**
     * Decrements the number of pending actions.
     */
    'decrement': function() { this.count --; this.set(); },

    /**
     * Fades out the notification box.
     */
    'fade': function()
    {
        if (!this.box) return;
        if (this.box.style.opacity > 0) {
            var o = this.box.style.opacity;
            o = o - 0.05; if (o < 0) o = 0;
            p = o * 100;
            this.box.style.opacity = o;                     // Standard opacity
            this.box.style.filter = 'alpha(opacity='+p+')'; // MSIE opacity
            this.timer = setTimeout(function() {
                Phorum.UI.notification.fade();
            }, 20);
        }
        else {
            this.box.style.display = 'none';
        }
    },

    /**
     * Set the notification message.
     */
    'set': function()
    {
        if (this.timer) clearTimeout(this.timer);

        // If no actions are pending, clear up the notification
        // after a little 250ms pause (to keep the notification
        // from being irritatingly flashy on screen).
        if (this.count == 0) {
            if (!this.box) return;
            this.timer = setTimeout(function() {
                Phorum.UI.notification.fade();
            }, 250);
            return;
        }

        // Create the notify box if it is not available yet.
        if (!this.box)
        {
            // Create the box.
            this.box = document.createElement('div');
            this.box.id = 'phorum_ajax_notification';

            // Add it to the page.
            document.body.insertBefore(this.box, document.body.childNodes[0]);

            // Move the notification in view if the page scrolls.
            if (window.onscroll) {
                var orig = window.onscroll;
                window.onscroll = function() {
                    orig();
                    Phorum.UI.notification.place();
                };
            } else {
                window.onscroll = function() {
                    Phorum.UI.notification.place();
                }
            }
        }

        <?php $lang = $PHORUM['DATA']['LANG']; ?>
        var message = this.count == 1
            ? '<?php print addslashes($lang['ActionPending']) ?>'
            : '<?php print addslashes($lang['ActionsPending']) ?>';

        // Replace %count% in the message with the current action count.
        var pos = message.indexOf('%count%');
        if (pos > -1) {
            message = message.substr(0,pos)+this.count+message.substr(pos+7);
        }

        // Show the notification.
        this.box.innerHTML = message;
        this.place();
        this.box.style.display = 'block';
        this.box.style.opacity = 1;                   // Standard opacity
        this.box.style.filter = 'alpha(opacity=100)'; // MSIE opacity
    },

    'place': function()
    {
        if (!this.box) return;

        var s = document.documentElement.scrollTop
              ? document.documentElement.scrollTop
              : document.body.scrollTop;

        var t = (s+this.boxtop) + 'px';

        this.box.style.top = t;
        this.box.style.left = this.boxleft;
    }
};

