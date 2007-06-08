<?php
//Last updated for version 1.0.9 by Pascal d'Hermilly

$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" => "Spam forebyggelse:",
    "CaptchaExplain" => "Indtast venligst den viste kode i det nedenst&aring;ende inputfelt. Dette er for at blokere spam-robotter som pr&oslash;ver automatisk at skrive reklamer.",
    "CaptchaUnclearExplain" => "Hvis koden er sv&aelig;r at l&aelig;se, s&aring; bare pr&oslash;v alligevel. Hvis du skriver forkert, kommer der bare et nyt billede og du f&aring;r en ny chance.",
    "CaptchaSpoken" => "Lyt til koden udtalt p&aring; engelsk.",
    "CaptchaFieldLabel" => "Intast kode: ",
    "CaptchaWrongCode" => "Du skrev en forkert kode. Pr&oslash;v venligst igen.",

    // Mathematical CAPTCHA
    "MaptchaTitle" => "Spam forebyggelse:",
    "MaptchaExplain" => "L&oslash;s venligst det matematiske sp&oslash;rgsm&aring;l og indtast l&oslash;sningen i feltet. Dette er for at blokere spam-robotter som pr&oslash;ver automatisk at skrive reklamer.",
    "MaptchaQuestion" => "Sp&oslash;rgsm&aring;l: Hvor meget giver {NUMBER1} plus {NUMBER2}?",
    "MaptchaSpoken" => "Lyt til dette sp&oslash;rgsm&aring;l gennem talesyntese.",
    "MaptchaFieldLabel" => "Svar: ",
    "MaptchaWrongAnswer" => "Du svarede ikke korrekt. Pr&oslash;v venligst igen.",

    // Javascript CAPTCHA.
    "JavascriptCaptchaNoscript" => "[Aktiver javascript for at se koden]",

    // Generic message when a block was hit, but the user is still allowed
    // to post an automatically unapproved message.
    "PostingUnapproveError" => "Anti-spam software tror at din besked m&aring;ske er spam. Du kan stadig poste din besked, men den vil ikke v&aelig;re synlig f&oslash;r en moderator har godkendt den. Du kan nu pr&oslash;ve at gensende din besked.",

    // Generic message when blocking a message. We do not want to
    // feed specific blocking reasons to those who are blocked, because
    // that info might be used to bypass the blocking reasons.
    "BlockError" => "Anti-spam software tror at din besked m&aring;ske er spam. Derfor er din besked blevet blokeret. Hvis din besked ikke var spam undskylder vi mange gange for at have blokeret den. Hvis du fortsat har problemer med at blive blokeret s&aring; kontakt forummets administrator.<br/><br/><b>Note</b>: Hvis du har deaktiveret Javascript kan det v&aelig;re derfor du er blevet blokeret. Nogle anti-spam l&oslash;sninger afh&aelig;nger af at Javascript er aktiveret.",
);
?>
