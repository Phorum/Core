<?php
/**
 * Start a new output buffer.
 */
function phorum_api_outputbuffer_start()
{
    ob_start();
}

/**
 * Stop the output buffer and return the buffered contents.
 *
 * @return string
 */
function phorum_api_outputbuffer_stop()
{
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

/**
 * Clear out all output that PHP buffered up to now.
 */
function phorum_api_outputbuffer_clean()
{
    // Clear out all output that PHP buffered up to now.
    for(;;) {
        $status = ob_get_status();
        if (!$status ||
            $status['name'] == 'ob_gzhandler' ||
            !$status['del']) break;
        ob_end_clean();
    }
}
?>
