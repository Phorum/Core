<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" =>
        "Prevenção de SPAM:", 
    "CaptchaExplain" =>
        "Por favor, digite o código que você visualiza no campo abaixo
         Este sistema é para bloquear robos que tentam usar este formulário automaticamente.",
    "CaptchaUnclearExplain" =>
        "Se o código está dificil de ler, tente colocar o código que você achar. 
         Se você digitar o codigo errado, será gerado um novo código
         e outra chance de digitar o codigo correto.",
    "CaptchaSpoken" =>
        "Escute este código.",
    "CaptchaFieldLabel" => 
        "Digite o Código: ",
    "CaptchaWrongCode" =>
        "Você digitou o codigo incorreto. 
         Tente novamente.",

    // Mathematical CAPTCHA
    "MaptchaTitle" =>
        "Prevenção de SPAM:",
    "MaptchaExplain" =>
        "Por favor, resolva a questão matemática e digite o resultado no campo abaixo. Este sistema é para bloquear robos que tentam usar este formulário automaticamente.",
    "MaptchaQuestion" =>
        "Pergunta: Quanto é {NUMBER1} mais {NUMBER2}?",
    "MaptchaSpoken" =>
        "Escute este código..",
    "MaptchaFieldLabel" =>
        "Resposta: ",
    "MaptchaWrongAnswer" =>
        "Você digitou o codigo incorreto. 
         Tente novamente.",
    
    // Javascript CAPTCHA.
    "JavascriptCaptchaNoscript" =>
        "[Por favor, habilite o javascript para visualizar o código]",

    // A message that is shown when a bot post is suspected.
    "PostingRejected" =>
        "As informações que você digitou foram rejeitadas,
         pois parece ser enviada por robôs.",

    // A message for failed spam hurdle checks, for which a repost
    // of the form could make a difference.
    "TryResubmit" =>
        "Você pode tentar enviar novamente seus dados.",

    // A message for failed spam hurdle checks, for which the problem
    // might be lack of javascript support in the browser (either
    // absent or disabled).
    "NeedJavascript" =>
        "Se o seu navegador não tem suporte para javascript ou está desabilitado,
         isto poderá ser a causa do problema.
         JavaScript precisa estar habilitado.",

    // A message that tells the user to contact the site owner
    // if the problems persist.
    "ContactSiteOwner" => 
        "Se continuar tendo problemas com bloqueio de dados, 
         então entre em contato com o proprietário do site para obter ajuda."
);

?>
