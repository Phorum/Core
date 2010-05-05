function spamhurdles_block_quick_submit(form_id, seconds_left)
{
    var button = null;

    // Find the form that is protected by this hurdle.
    var f = spamhurdles_find_form(form_id);
    if (!f) return;

    // The button with name="finish" or name="post" is the submit button on
    // the message posting form.
    if (f.finish) {
        button = f.finish;
    }
    else if (f.post) {
        button = f.post;
    }
    // On other forms we check if there is only one submit button available.
    // If yes, then we use that button for implementing the count down.
    else
    {
        var buttons = f.getElementsByTagName('input');
        for (var i = 0; i < buttons.length; i++) {
            if (buttons[i].type === 'submit')
            {
                // If we found more than one button, then we are not sure
                // on what button to show the timeout.
                if (button) {
                    button = null;
                    break;
                }

                button = buttons[i];
            }
        }
    }

    // Return if we found no usable button.
    if (!button) return;

    // Display a count down on the button.
    button.orig_value = button.value;
    spamhurdles_block_quick_submit_countdown(button, seconds_left);
}

function spamhurdles_block_quick_submit_countdown(button, seconds_left)
{
    if (seconds_left <= 0) {
        button.value = button.orig_value;
        button.disabled = false;
    } else {
        button.value = button.orig_value + "(" + seconds_left + ")";
        button.disabled = true;
        setTimeout(function(){
            spamhurdles_block_quick_submit_countdown(button, --seconds_left);
        }, 1000);
    }
}

