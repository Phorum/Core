<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" =>
        "Spamschutz:",
    "CaptchaExplain" =>
        "Geben Sie bitte den Code aus dem unten stehenden Bild in das
         Eingabefeld ein. Damit werden Spamprogramme, die versuchen,
         dieses Formular automatisch auszufüllen, geblockt.",
    "CaptchaUnclearExplain" =>
        "Wenn der Code schwer zu lesen ist, raten Sie einfach.
         Bei einem falschen Code wird ein neues Bild erzeugt und Sie
         erhalten eine zweite Chance.",
    "CaptchaSpoken" =>
        "Diesen Code vorlesen lassen (auf Englisch).",
    "CaptchaFieldLabel" =>
        "Code eingeben: ",
    "CaptchaWrongCode" =>
        "Sie haben einen falschen Code für die Spamschutz-Abfrage eingegeben.
         Bitte versuchen Sie es noch einmal.",

    // Mathematical CAPTCHA
    "MaptchaTitle" =>
        "Spamschutz:",
    "MaptchaExplain" =>
        "Bitte geben Sie die Lösung der Rechenaufgabe in das Eingabefeld ein.
         Damit werden Spamprogramme, die versuchen, dieses Formular
         automatisch auszufüllen, geblockt.",
    "MaptchaQuestion" =>
        "{NUMBER1} plus {NUMBER2} = ?",
    "MaptchaSpoken" =>
        "Die Frage vorlesen lassen (auf Englisch).",
    "MaptchaFieldLabel" =>
        "Antwort: ",
    "MaptchaWrongAnswer" =>
        "Die Antwort ist falsch. Bitte versuchen Sie es noch einmal.",

    // Javascript CAPTCHA.
    "JavascriptCaptchaNoscript" =>
        "[Bitte schalten Sie Javascript in Ihren Browseroptionen ein,
         damit der Code sichtbar wird.]",

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
