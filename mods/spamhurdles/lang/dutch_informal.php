<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" =>
        "Spambeveiliging:",
    "CaptchaExplain" =>
        "Voer de code die hieronder zichtbaar is in het invoerveld beneden in.
         Dit is nodig om het automatisch posten van dit formulier door bots
         tegen te gaan.",
    "CaptchaUnclearExplain" =>
        "Als de code slecht te lezen is, doe dan een zo goed mogelijke gok.
         Als de code fout is, dan zal een nieuwe afbeelding worden gegenereerd,
         waarna er nog een kans is om de code juist in te voeren.",
    "CaptchaSpoken" =>
        "Beluister deze code in gesproken vorm (Engels)",
    "CaptchaFieldLabel" =>
        "Voer code in: ",
    "CaptchaWrongCode" =>
        "De ingevoerde code voor de spambeveiliging is niet juist.
         Probeer het opnieuw.",

    // Mathematical CAPTCHA
    "MaptchaTitle" =>
        "Spambeveiliging:",
    "MaptchaExplain" =>
        "Los de onderstaande som op en voer het antwoord in het invoerveld
         beneden in. Dit is nodig om het automatisch posten van dit
         formulier door bots tegen te gaan.",
    "MaptchaQuestion" =>
        "Vraag: hoeveel is {NUMBER1} plus {NUMBER2}?",
    "MaptchaSpoken" =>
        "Luister naar deze vraag in gesproken vorm (Engels)",
    "MaptchaFieldLabel" =>
        "Antwoord: ",
    "MaptchaWrongAnswer" =>
        "Het ingevoerde antwoord voor de spambeveiliging is niet juist.
         Probeer het opnieuw.",

    // Javascript CAPTCHA.
    "JavascriptCaptchaNoscript" =>
        "[Activeer JavaScript om de code te zien]",

    // A message that is shown when a bot post is suspected.
    "PostingRejected" =>
        "De gegevens die je naar de server hebt verzonden zijn geweigerd,
         omdat het lijkt dat deze verstuurd zijn door een automatische bot.",

    // A message for failed spam hurdle checks, for which a repost
    // of the form could make a difference.
    "TryResubmit" =>
        "Je kunt proberen het formulier opnieuw te verzenden.",

    // A message for failed spam hurdle checks, for which the problem
    // might be lack of javascript support in the browser (either
    // absent or disabled).
    "NeedJavascript" =>
        "Wanneer je browser geen JavaScript ondersteunt of wanneer
         JavaScript uitgeschakeld is, dan kan dit de oorzaak zijn
         van het probleem. JavaScript moet actief zijn om dit
         formulier te kunnen versturen.",

    // A message that tells the user to contact the site owner
    // if the problems persist.
    "ContactSiteOwner" =>
        "Wanneer je problemen blijft houden, neem dan contact op met
         de eigenaar van deze website voor hulp."
);
?>
