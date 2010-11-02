<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" =>
        "Mesure anti-SPAM :",
    "CaptchaExplain" =>
        "Recopiez le code que vous voyez dans le champ ci-dessous.
         Cette mesure sert à bloquer les robots informatiques qui tentent
         de polluer ce site.",
    "CaptchaUnclearExplain" =>
        "Si le code n'est pas clair, essayez de le deviner. Si vous
         faites erreur, une nouvelle image sera créée et vous pourrez
		 essayer à nouveau.",
    "CaptchaSpoken" =>
        "Écoutez la version orale du code (en anglais seulement).",
    "CaptchaFieldLabel" =>
        "Saisissez le code ici :",
    "CaptchaWrongCode" =>
        "Le code que vous avez entré pour la mesure anti-SPAM est incorrect.
         SVP ré-essayer.",

    // CAPTCHA Mathématique
    "MaptchaTitle" =>
        "Mesure anti-SPAM :",
    "MaptchaExplain" =>
        "Résoudre la question mathématique et saisir la réponse dans le
         champ ci-dessous. Cette mesure sert à bloquer les robots
         informatiques qui tentent de polluer ce site.",
    "MaptchaQuestion" =>
        "Question : que font {NUMBER1} plus {NUMBER2}?",
    "MaptchaSpoken" =>
        "Écoutez la version orale du code (en anglais seulement).",
    "MaptchaFieldLabel" =>
        "Réponse : ",
    "MaptchaWrongAnswer" =>
        "Le code que vous avez entré pour la mesure anti-SPAM est incorrect.
         SVP ré-essayer",

    // CAPTCHA en Javascript.
    "JavascriptCaptchaNoscript" =>
        "[SVP activer la fonction JavaScript pour voir le code]",

    // A message that is shown when a bot post is suspected.
    "PostingRejected" =>
        "Les informations que vous avez envoyées ont été rejetées,
		 parce qu'elles semblent envoyées par un robot d'envois automatiques.",

    // A message for failed spam hurdle checks, for which a repost
    // of the form could make a difference.
    "TryResubmit" =>
        "Vous pouvez essayer à nouveau d'envoyer les informations.",

    // A message for failed spam hurdle checks, for which the problem
    // might be lack of javascript support in the browser (either
    // absent or disabled).
    "NeedJavascript" =>
        "Si votre navigateur n'a pas de javascript, ou s'il est désactivé,
         cela peut-être la raison de l'échec.
         Le Javavascript doit être activé pour que ce formulaire fonctionne.",

    // A message that tells the user to contact the site owner
    // if the problems persist.
    "ContactSiteOwner" =>
        "Si le problème d'envoi des informations persiste,
		 contactez le responsable du site."
);
?>
