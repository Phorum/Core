<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" => "Spam prevention:",
    "CaptchaExplain" => "Please, enter the code that you see below in the input field. This is for blocking bots that try to post this form automatically.",
    "CaptchaUnclearExplain" => "If the code is hard to read, then just try to guess it right. If you enter the wrong code, a new image is created and you get another chance to enter it right.",
    "CaptchaSpoken" => "Listen to this code in spoken form.",
    "CaptchaFieldLabel" => "Enter code: ",
    "CaptchaWrongCode" => "You did not provide the correct code for the spam prevention check. Please try again.",

    // Mathematical CAPTCHA
    "MaptchaTitle" => "Spam prevention:",
    "MaptchaExplain" => "Please, solve the mathematical question and enter the answer in the input field below. This is for blocking bots that try to post this form automatically.",
    "MaptchaQuestion" => "Question: how much is {NUMBER1} plus {NUMBER2}?",
    "MaptchaSpoken" => "Listen to this question in spoken form.",
    "MaptchaFieldLabel" => "Answer: ",
    "MaptchaWrongAnswer" => "You did not provide the correct answer for the spam prevention question. Please try again.",

    // Javascript CAPTCHA.
    "JavascriptCaptchaNoscript" => "[Please, enable JavaScript to see the code]",

    // Generic message when a block was hit, but the user is still allowed
    // to post an automatically unapproved message.
    "PostingUnapproveError" => "Anti-spam software on this server has detected that your message might be spam. You are still allowed to post this message, but it will not be visible in the forums until a moderator approves it. You can now try to re-submit your posting.",

    // Generic message when blocking a message. We do not want to
    // feed specific blocking reasons to those who are blocked, because
    // that info might be used to bypass the blocking reasons.
    "BlockError" => "Anti-spam software on this server has detected that your message might be spam. Therefore, your message has been blocked. If your message was not a spam message, then we apologize for the inconvenience that blocking it might have caused. If you keep having problems with your messages being blocked, please contact the site administrator for help.<br/><br/><b>Note</b>: If you have disabled JavaScript within your browser or if your browser does not support JavaScript at all, then this might be the reason why your message was blocked. Some of the anti-spam measures are dependent on JavaScript.",
);
?>
