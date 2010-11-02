<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" =>
        "Spamschutz:",
    "CaptchaExplain" =>
        "Bitte gib den Code aus dem unten stehenden Bild in das
         Eingabefeld ein. Damit werden Bots, die versuchen dieses
         Formular automatisch auszufüllen, geblockt.",
    "CaptchaUnclearExplain" =>
        "Wenn der Code schwer zu lesen ist, versuche einfach zu raten.
         Wenn du einen falschen Code eingibst, wird einfach ein neues
         Bild erzeugt und du bekommst eine zweite Chance.",
    "CaptchaSpoken" =>
        "Diesen Code vorlesen lassen (auf English).",
    "CaptchaFieldLabel" =>
        "Code eingeben: ",
    "CaptchaWrongCode" =>
        "Du hast einen falschen Code für die Spamschutz-Abfrage eingegeben.
         Bitte versuche es noch einmal.",

    // Mathematical CAPTCHA
    "MaptchaTitle" =>
        "Spamschutz:",
    "MaptchaExplain" =>
        "Bitte gib die Lösung der Matheaufgabe in das Eingabefeld ein.
         Damit werden Bots, die versuchen dieses Formular automatisch
         auszufüllen, geblockt.",
    "MaptchaQuestion" =>
        "Frage: Was ergibt {NUMBER1} plus {NUMBER2}?",
    "MaptchaSpoken" =>
        "Die Frage vorlesen lassen (auf English).",
    "MaptchaFieldLabel" =>
        "Antwort: ",
    "MaptchaWrongAnswer" =>
        "Du hast eine falsche Antwort für die Spamschutz-Abfrage eingegeben.
         Bitte versuche es noch einmal.",

    // Javascript CAPTCHA.
    "JavascriptCaptchaNoscript" =>
        "[Bitte schalte Javascript in deinen Browseroptionen ein,
         damit du den Code sehen kannst.]",

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
