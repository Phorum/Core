<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" => "Spamschutz:",
    "CaptchaExplain" => "Bitte gib den Code aus dem unten stehenden Bild in das Eingabefeld ein. Damit werden Bots, die versuchen dieses Formular automatisch auszufüllen, geblockt.",
    "CaptchaUnclearExplain" => "Wenn der Code schwer zu lesen ist, versuche einfach zu raten. Wenn du einen falschen Code eingibst, wird einfach ein neues Bild erzeugt und du bekommst eine zweite Chance.",
    "CaptchaSpoken" => "Diesen Code vorlesen lassen (auf English).",
    "CaptchaFieldLabel" => "Code eingeben: ",
    "CaptchaWrongCode" => "Du hast einen falschen Code für die Spamschutz-Abfrage eingegeben. Bitte versuche es noch einmal.",

    // Mathematical CAPTCHA
    "MaptchaTitle" => "Spamschutz:",
    "MaptchaExplain" => "Bitte gib die Lösung der Matheaufgabe in das Eingabefeld ein. Damit werden Bots, die versuchen dieses Formular automatisch auszufüllen, geblockt.",
    "MaptchaQuestion" => "Frage: Was ergibt {NUMBER1} plus {NUMBER2}?",
    "MaptchaSpoken" => "Die Frage vorlesen lassen (auf English).",
    "MaptchaFieldLabel" => "Antwort: ",
    "MaptchaWrongAnswer" => "Du hast eine falsche Antwort für die Spamschutz-Abfrage eingegeben. Bitte versuche es noch einmal.",

    // Javascript CAPTCHA.
    "JavascriptCaptchaNoscript" => "[Bitte schalte Javascript in deinen Browseroptionen ein, damit du den Code sehen kannst.]",

    // Generic message when a block was hit, but the user is still allowed
    // to post an automatically unapproved message.
    "PostingUnapproveError" => "Anti-Spam Software auf diesem Server hat deine Nachricht als vermutlichen Spam erkannt. Du kannst die Nachricht noch immer abschicken, aber sie wird nicht im Forum erscheinen, bis ein Moderator sie freigibt. Du kannst dein Post nun noch einmal absenden.",

    // Generic message when blocking a message. We do not want to
    // feed specific blocking reasons to those who are blocked, because
    // that info might be used to bypass the blocking reasons.
    "BlockError" => "Anti-Spam Software auf diesem Server hat deine Nachricht als vermutlichen Spam erkannt. Darum wurde die Nachricht geblockt. Wenn deine Nachricht kein Spam war, möchten wir uns für die Umstände, die durch das Blocken entstehen, entschuldigen. Solltest du mehrfach Probleme haben, dass deine Nachrichten geblockt werden, wende dich bitte an einen Administrator.<br/><br/><b>Hinweis</b>: Wenn JavaScript in deinem Browser ausgeschaltet ist er es gar nicht unterstützt, dann könnte dies der Grund sein, warum deine Nachrichten geblockt werden. Einige der verwendeten Anti-Spam Maßnahmen verwenden JavaScript.",
);
?>
