<?php
$PHORUM["DATA"]["LANG"]["mod_spamhurdles"] = array
(
    // Code CAPTCHA
    "CaptchaTitle" => "Spam korumasý:",
    "CaptchaExplain" => "Lütfen aþaðýdaki kodu giriniz. Bu koruma otomatik hesap açan botlar için geliþtirilmiþtir.",
    "CaptchaUnclearExplain" => "Eðer kodu okumakta zorlanýyorsanýz, sadece tahmin edin. Eðer yanlýþ kod girerseniz, yeniden girmeniz için yeni bir kod yaratýlacaktýr.",
    "CaptchaSpoken" => "Kodu konuþma formunda dinle. (Not: Konuþma formu ingilizcedir.)",
    "CaptchaFieldLabel" => "Kodu Gir: ",
    "CaptchaWrongCode" => "Kodu verildiði gibi girmediniz. Lütfen tekrar deneyiniz.",

    // Mathematical CAPTCHA
    "MaptchaTitle" => "Spam korumasý:",
    "MaptchaExplain" => "Please, solve the mathematical question and enter the answer in the input field below. This is for blocking bots that try to post this form automatically.",
    "MaptchaQuestion" => "Question: how much is {NUMBER1} plus {NUMBER2}?",
    "MaptchaSpoken" => "Listen to this question in spoken form.",
    "MaptchaFieldLabel" => "Answer: ",
    "MaptchaWrongAnswer" => "You did not provide the correct answer for the spam prevention question. Please try again.",

    // Javascript CAPTCHA.
    "JavascriptCaptchaNoscript" => "[Lütfen tarayýcýnýzýn JavaScript seçeneðini aktif hale getiriniz.]",

    // Generic message when a block was hit, but the user is still allowed
    // to post an automatically unapproved message.
    "PostingUnapproveError" => "Anti-spam yazýlýmý mesajýnýzýn spam olabileceðini algýladý. Bu mesajý halen gönderebilirsiniz, fakat forum yöneticileri mesajý onaylamadan diðer kullanýcýlar tarafýndan görüntülenemeyecektir.",

    // Generic message when blocking a message. We do not want to
    // feed specific blocking reasons to those who are blocked, because
    // that info might be used to bypass the blocking reasons.
    "BlockError" => "Anti-spam yazýlýmý mesajýnýzýn spam olabileceðini algýladý. Bu yüzden mesajýnýz engellendi. Eðer mesajýnýz bir spam deðilse bunun için özür dileriz. Eðer mesajýnýzýn bloklanmasý ile ilgili halen bir sorun yaþýyorsanýz, lütfen site yöneticileri ile temasa geçiniz.<br/><br/><b>Not</b>: Eðer tarayýcýnýzda JavaScript özelliðini iptal ettiyseniz ya da tarayýcýnýz JavaScript desteklemiyor ise sebep bu olabilir. Bazý anti-spam korumasý gerekçeleri JavaScript ile alakalýdýr.",
);
?>
