<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" => "Spambeveiliging:",
    "CaptchaExplain" => "Voer de code die hieronder zichtbaar is in het invoerveld beneden in. Dit is nodig om het automatisch posten van dit formulier door bots tegen te gaan.",
    "CaptchaUnclearExplain" => "Als de code slecht te lezen is, doe dan een zo goed mogelijke gok. Als de code fout is, dan zal een nieuwe afbeelding worden gegenereerd, waarna er nog een kans is om de code juist in te voeren.",
    "CaptchaSpoken" => "Beluister deze code in gesproken vorm (Engels)",
    "CaptchaFieldLabel" => "Voer code in: ",
    "CaptchaWrongCode" => "De ingevoerde code voor de spambeveiliging is niet juist. Probeer het opnieuw.",

    // Mathematical CAPTCHA
    "MaptchaTitle" => "Spambeveiliging:",
    "MaptchaExplain" => "Los de onderstaande som op en voer het antwoord in het invoerveld beneden in. Dit is nodig om het automatisch posten van dit formulier door bots tegen te gaan.",
    "MaptchaQuestion" => "Vraag: hoeveel is {NUMBER1} plus {NUMBER2}?",
    "MaptchaSpoken" => "Luister naar deze vraag in gesproken vorm (Engels)",
    "MaptchaFieldLabel" => "Antwoord: ",
    "MaptchaWrongAnswer" => "Het ingevoerde antwoord voor de spambeveiliging is niet juist. Probeer het opnieuw.",

    // Javascript CAPTCHA.
    "JavascriptCaptchaNoscript" => "[Activeer JavaScript om de code te zien]",

    // Generic message when a block was hit, but the user is still allowed
    // to post an automatically unapproved message.
    "PostingUnapproveError" => "Anti-spam software op deze server heeft gedetecteerd dat uw bericht mogelijk spam is. Het bericht kan nog steeds worden verstuurd, maar deze zal niet direct zichtbaar zijn in het forum. Het bericht zal eerst door een moderator moeten worden goedgekeurd. Het formulier kan nu opnieuw worden verzonden.",

    // Generic message when blocking a message. We do not want to
    // feed specific blocking reasons to those who are blocked, because
    // that info might be used to bypass the blocking reasons.
    "BlockError" => "Anti-spam software op deze server heeft gedetecteerd dat uw bericht mogelijk spam is. Daarom is het bericht geblokkeerd. Als deze blokkering onterecht is, dan bieden we onze verontschuldigingen aan voor de mogelijke overlast. Als u problemen blijft hebben met het versturen van berichten, neem dan contact op met de beheerder van de website voor hulp.<br/><br/><b>Opmerking</b>: Wanneer JavaScript uit staat in de browser of wanneer de browser helemaal geen JavaScript ondersteunt, dan kan dit de reden zijn van de blokkering. Sommige checks zijn afhankelijk van JavaScript voor een goede werking.",
);
?>
