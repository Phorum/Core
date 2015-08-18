<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" =>
        "Mesure anti-SPAM :",
    "CaptchaExplain" =>
        "Recopiez le code que vous voyez dans le champ ci-dessous.
         Cette mesure sert � bloquer les robots informatiques qui tentent
         de polluer ce site.",
    "CaptchaUnclearExplain" =>
        "Si le code n'est pas clair, essayez de le deviner. Si vous
         faites erreur, une nouvelle image sera cr��e et vous pourrez
         essayer � nouveau.",
    "CaptchaSpoken" =>
        "�coutez la version orale du code (en anglais seulement).",
    "CaptchaFieldLabel" =>
        "Saisissez le code ici :",
    "CaptchaWrongCode" =>
        "Le code que vous avez entr� pour la mesure anti-SPAM est incorrect.
         SVP r�-essayer.",

    // CAPTCHA Math�matique
    "MaptchaTitle" =>
        "Mesure anti-SPAM :",
    "MaptchaExplain" =>
        "R�soudre la question math�matique et saisir la r�ponse dans le
         champ ci-dessous. Cette mesure sert � bloquer les robots
         informatiques qui tentent de polluer ce site.",
    "MaptchaQuestion" =>
        "Question : que font {NUMBER1} plus {NUMBER2}?",
    "MaptchaSpoken" =>
        "�coutez la version orale du code (en anglais seulement).",
    "MaptchaFieldLabel" =>
        "R�ponse : ",
    "MaptchaWrongAnswer" =>
        "Le code que vous avez entr� pour la mesure anti-SPAM est incorrect.
         SVP r�-essayer",

    // CAPTCHA en Javascript.
    "JavascriptCaptchaNoscript" =>
        "[SVP activer la fonction JavaScript pour voir le code]",

    // A message that is shown when a bot post is suspected.
    "PostingRejected" =>
        "Les informations que vous avez envoy�es ont �t� rejet�es,
         parce qu'elles semblent envoy�es par un robot d'envois automatiques.",

    // A message for failed spam hurdle checks, for which a repost
    // of the form could make a difference.
    "TryResubmit" =>
        "Vous pouvez essayer � nouveau d'envoyer les informations.",

    // A message for failed spam hurdle checks, for which the problem
    // might be lack of javascript support in the browser (either
    // absent or disabled).
    "NeedJavascript" =>
        "Si votre navigateur n'a pas de javascript, ou s'il est d�sactiv�,
         cela peut-�tre la raison de l'�chec.
         Le Javavascript doit �tre activ� pour que ce formulaire fonctionne.",

    // A message that tells the user to contact the site owner
    // if the problems persist.
    "ContactSiteOwner" =>
        "Si le probl�me d'envoi des informations persiste,
         contactez le responsable du site."
);
?>
