<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" =>
        "Spam prevention:",
    "CaptchaExplain" =>
        "Please, enter the code that you see below in the input field.
         This is for blocking bots that try to post this form automatically.",
    "CaptchaUnclearExplain" =>
        "If the code is hard to read, then just try to guess it right.
         If you enter the wrong code, a new image is created and you get
         another chance to enter it right.",
    "CaptchaSpoken" =>
        "Listen to this code in spoken form.",
    "CaptchaFieldLabel" => 
        "Enter code: ",
    "CaptchaWrongCode" =>
        "You did not provide the correct code for the spam prevention check. 
         Please try again.",

    // Mathematical CAPTCHA
    "MaptchaTitle" =>
        "Spam prevention:",
    "MaptchaExplain" =>
        "Please, solve the mathematical question and enter the answer in the
         input field below. This is for blocking bots that try to post this
         form automatically.",
    "MaptchaQuestion" =>
        "Question: how much is {NUMBER1} plus {NUMBER2}?",
    "MaptchaSpoken" =>
        "Listen to this question in spoken form.",
    "MaptchaFieldLabel" =>
        "Answer: ",
    "MaptchaWrongAnswer" =>
        "You did not provide the correct answer for the spam prevention
         question. Please try again.",

    // Javascript CAPTCHA.
    "JavascriptCaptchaNoscript" =>
        "[Please, enable JavaScript to see the code]",

    // A message that is shown when a bot post is suspected.
    "PostingRejected" =>
        "The data that you have submitted to the server have been rejected,
         because it looks like they were posted by an automated bot.",

    // A message for failed spam hurdle checks, for which a repost
    // of the form could make a difference.
    "TryResubmit" =>
        "You can try to resubmit your form data.",

    // A message for failed spam hurdle checks, for which the problem
    // might be lack of javascript support in the browser (either
    // absent or disabled).
    "NeedJavascript" =>
        "If your browser has no JavaScript support or if JavaScript is disabled,
         then this might be the cause of the problem.
         JavaScript must be enabled for submitting this form.",

    // A message that tells the user to contact the site owner
    // if the problems persist.
    "ContactSiteOwner" =>
        "If you keep having problems with your data being blocked,
         then please contact the site owner for help."
);

?>
