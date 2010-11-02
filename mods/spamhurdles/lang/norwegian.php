<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" =>
        "Spam forhindring:",
    "CaptchaExplain" =>
        "Vennligst skriv inn koden nedenfor i input-feltet.
         Dette er for &aring; blokkere roboter som fors&oslash;ker
         &aring; poste til dette forumet automatisk.",
    "CaptchaUnclearExplain" =>
        "Hvis koden er vanskelig &aring; lese, bare fors&oslash;k
         &aring; gjette. Hvis du skriver inn feil kode, vil et nytt
         ord bli vist og du blir bedt om &aring; fors&oslash;ke
         p&aring; nytt. Dette gjentaes inntil du taster inn riktig kode.",
    "CaptchaSpoken" =>
        "Lytt til denne koden i opplest form.",
    "CaptchaFieldLabel" =>
        "Skriv inn kode: ",
    "CaptchaWrongCode" =>
        "Du anga feil kode. Vennligst fors&oslash;k igjen.",

    // Mathematical CAPTCHA
    "MaptchaTitle" =>
        "Spam forhindring:",
    "MaptchaExplain" =>
        "Vennligst l&oslash;s det matemastiske sp&oslash;rsm&aring;let og
         fyll inn svaret i input-feltet nedenfor. Dette er for &aring;
         blokkere roboter som fors&oslash;ker &aring; poste til dette
         forumet automatisk.",
    "MaptchaQuestion" =>
        "Sp&oslash;rsm&aring;l: Hvor mye er {NUMBER1} pluss {NUMBER2}?",
    "MaptchaSpoken" =>
        "Lytt til denne koden i opplest form.",
    "MaptchaFieldLabel" =>
        "Svar: ",
    "MaptchaWrongAnswer" =>
        "Du anga feil svar. Vennligst fors&oslash;k igjen.",

    // Javascript CAPTCHA.
    "JavascriptCaptchaNoscript" =>
        "[V&ae;r s&aring; vennlig &aring; aktivere Java for &aring; se koden.]",

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
