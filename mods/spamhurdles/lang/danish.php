<?php
//Last updated for version 1.0.9 by Pascal d'Hermilly

$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" =>
        "Spam forebyggelse:",
    "CaptchaExplain" =>
        "Indtast venligst den viste kode i det nedenst&aring;ende inputfelt.
         Dette er for at blokere spam-robotter som pr&oslash;ver automatisk
         at skrive reklamer.",
    "CaptchaUnclearExplain" =>
        "Hvis koden er sv&aelig;r at l&aelig;se, s&aring; bare pr&oslash;v
         alligevel. Hvis du skriver forkert, kommer der bare et nyt billede
         og du f&aring;r en ny chance.",
    "CaptchaSpoken" =>
        "Lyt til koden udtalt p&aring; engelsk.",
    "CaptchaFieldLabel" =>
        "Intast kode: ",
    "CaptchaWrongCode" =>
        "Du skrev en forkert kode. Pr&oslash;v venligst igen.",

    // Mathematical CAPTCHA
    "MaptchaTitle" =>
        "Spam forebyggelse:",
    "MaptchaExplain" =>
        "L&oslash;s venligst det matematiske sp&oslash;rgsm&aring;l og
         indtast l&oslash;sningen i feltet. Dette er for at blokere
         spam-robotter som pr&oslash;ver automatisk at skrive reklamer.",
    "MaptchaQuestion" =>
        "Sp&oslash;rgsm&aring;l: Hvor meget giver {NUMBER1} plus {NUMBER2}?",
    "MaptchaSpoken" =>
        "Lyt til dette sp&oslash;rgsm&aring;l gennem talesyntese.",
    "MaptchaFieldLabel" =>
        "Svar: ",
    "MaptchaWrongAnswer" =>
        "Du svarede ikke korrekt. Pr&oslash;v venligst igen.",

    // Javascript CAPTCHA.
    "JavascriptCaptchaNoscript" =>
        "[Aktiver javascript for at se koden]",

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
