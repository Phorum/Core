<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" => "Prevención de SPAM:",
    "CaptchaExplain" => "Porfavor introduce el código que ves abajo en el espacio proporcionado. ",
    "CaptchaUnclearExplain" => "Si se te dificulta su lectura, adivinalo. Si introduces un código erróneo, se creará una nueva imagen.",
    "CaptchaSpoken" => "Escucha el código.",
    "CaptchaFieldLabel" => "Introduce el código: ",
    "CaptchaWrongCode" => "No proporcionaste el código correcto, porfavor intentalo de nuevo.",

    // Mathematical CAPTCHA
    "MaptchaTitle" => "Prevención de Spam:",
    "MaptchaExplain" => "Porfavor, soluciona el problema matemático en el campo proporcionado abajo. Esta es una forma de blocker robots que intentan postear automáticamente.",
    "MaptchaQuestion" => "Pregunta: cuánto es {NUMBER1} mas {NUMBER2}?",
    "MaptchaSpoken" => "Escucha la pregunta.",
    "MaptchaFieldLabel" => "Respuesta: ",
    "MaptchaWrongAnswer" => "No proporcionaste la respuesta correcta. Pofavor, intenta de nuevo.",

    // Javascript CAPTCHA.
    "JavascriptCaptchaNoscript" => "[Porfavor, activa JavaScript para ver el código]",

    // Generic message when a block was hit, but the user is still allowed
    // to post an automatically unapproved message.
    "PostingUnapproveError" => "Software Anti-Spam en este servidor ha detectado que tu mensaje podría ser spam. Aún puedes postear este mensaje, pero no se podrá visualizar en los foros hasta que un moderador lo apruebe. Puedes intentar someter de nuevo tu mensaje.",

    // Generic message when blocking a message. We do not want to
    // feed specific blocking reasons to those who are blocked, because
    // that info might be used to bypass the blocking reasons.
    "BlockError" => "Software Anti-Spam en este servidor ha detectado que tu mensaje podría se Spam, y por lo tanto ha sido bloqueado. Si tu mensaje no era spam, disculpa la inconveniencia. Si tus mensajes continúan siendo bloqueados, porfavor contacta al administrador de este sitio para que te ayude. <br/><br/><b>Note</b>: Si tienes habilitado JavaScript en tu navegador o si tu navegador no lo respalda, esta podría ser la razón de que tus mensajes sean bloqueados. Algúnas de las medidas Anti-Spam dependen de JavaScript.",
);
?>
