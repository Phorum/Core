<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" => "Mesure anti-SPAM :",
    "CaptchaExplain" => "Inscrivez le code que vous voyez dans le champs approprié. Cette mesure sert à bloquer les robots informatiques qui tentent de polluer ce site.",
    "CaptchaUnclearExplain" => "Si le code n'est pas clair, essayer de le deviner. Si vous faites erreur, une nouvelle image sera crée et vous aurez la chance de ré-essayer.",
    "CaptchaSpoken" => "Écoutez la version orale du code (en anglais seulement).",
    "CaptchaFieldLabel" => "Répéter le code ici :",
    "CaptchaWrongCode" => "Le code que vous avez entré pour la mesure anti-SPAM est incorrect. SVP ré-essayer.",

    // CAPTCHA Mathématique
    "MaptchaTitle" => " Mesure anti-SPAM :",
    "MaptchaExplain" => " Résoudre la question mathématique et insérer la réponse dans le champs approprié. Cette mesure sert à bloquer les robots informatiques qui tentent de polluer ce site.",
    "MaptchaQuestion" => "Question : que font {NUMBER1} plus {NUMBER2} ?",
    "MaptchaSpoken" => " Écoutez la version orale du code (en anglais seulement).",
    "MaptchaFieldLabel" => "Réponse : ",
    "MaptchaWrongAnswer" => " Le code que vous avez entré pour la mesure anti-SPAM est incorrect. SVP ré-essayer",

    // CAPTCHA en Javascript.
    "JavascriptCaptchaNoscript" => "[SVP activer la fonction JavaScript pour voir le code]",

    // Message générique quand un blocage a eu lieu mais l'usager peut toujours
    // publier un message qui sera automatiquement modéré.
    "PostingUnapproveError" => " Notre logiciel anti-SPAM a détecté que votre message pouvait être un SPAM. Vous pouvez toujours publier ce message mais il ne sera pas visible avant qu'un modérateur l'est approuvé. Vous pouvez continuer à publier d'autres messages.",

    // Message générique lorsqu'un message a été bloqué. Nous ne voulons pas fournir
    // d'explication pour les raisons du blocage parce que cette information pourrait
    // être utilisée pour contourner les mesures de sécurités.
    "BlockError" => " Notre logiciel anti-SPAM a détecté que votre message pouvait être un SPAM. Si votre message n'était pas un SPAM, alors toute nos excuses pour le dérangement causé. Si vos messages sont régulièrement bloqués, SVP contactez les administrateur du site.<br/><br/><b>Note</b> : Si la fonction JavaScript de votre fureteur (<i>browser</i>) est désactivée ou qu'elle n'est pas supportée, ceci pourrait être la raison que vos messages sont bloqués. Certaines fonctionnalités de notre logiciel anti-SPAM utilise les fonction JavaScript.",
);
?>
