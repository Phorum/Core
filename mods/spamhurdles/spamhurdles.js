function spamhurdles_eval_javascript(data)
{
    var cursor = 0; var start = 1; var end = 1;
    while (cursor < data.length && start > 0 && end > 0) {
        start = data.indexOf('<script', cursor);
        end   = data.indexOf('</script', cursor);
        if (end > start && end > -1) {
            if (start > -1) {
                var res = data.substring(start, end);
                start = res.indexOf('>') + 1;
                res = res.substring(start);
                if (res.length != 0) {
                    eval(res);
                }
            }
            cursor = end + 1;
        }
    }
}

function spamhurdles_find_form(form_id)
{
    var field = document.getElementById('spamhurdles_'+form_id);
    if (!field || !field.form) return null;
    return field.form;
}
