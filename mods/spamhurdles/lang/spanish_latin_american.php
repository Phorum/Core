<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" =>
        "Prevención de SPAM:",
    "CaptchaExplain" =>
        "Porfavor introduce el código que ves abajo en el espacio
         proporcionado. ",
    "CaptchaUnclearExplain" =>
        "Si se te dificulta su lectura, adivinalo. Si introduces un
         código erróneo, se creará una nueva imagen.",
    "CaptchaSpoken" =>
        "Escucha el código.",
    "CaptchaFieldLabel" =>
        "Introduce el código: ",
    "CaptchaWrongCode" =>
        "No proporcionaste el código correcto, porfavor intentalo de nuevo.",

    // Mathematical CAPTCHA
    "MaptchaTitle" =>
        "Prevención de Spam:",
    "MaptchaExplain" =>
        "Porfavor, soluciona el problema matemático en el campo
         proporcionado abajo. Esta es una forma de blocker robots que
         intentan postear automáticamente.",
    "MaptchaQuestion" =>
        "Pregunta: cuánto es {NUMBER1} mas {NUMBER2}?",
    "MaptchaSpoken" =>
        "Escucha la pregunta.",
    "MaptchaFieldLabel" =>
        "Respuesta: ",
    "MaptchaWrongAnswer" =>
        "No proporcionaste la respuesta correcta. Pofavor, intenta de nuevo.",

    // Javascript CAPTCHA.
    "JavascriptCaptchaNoscript" =>
        "[Porfavor, activa JavaScript para ver el código]",

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
