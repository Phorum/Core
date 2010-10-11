<?php
require_once(dirname(__FILE__) . "/class.captcha_base.php");

class captcha_maptcha extends captcha_base
{
    function generate_question_and_answer()
    {
        $lang = $GLOBALS["PHORUM"]["DATA"]["LANG"]["mod_spamhurdles"];

        // Generate random data for the math question.
        $number_1 = rand(1, 25);
        $number_2 = rand(1, 25);
        $answer = $number_1 + $number_2;

        // Generate the MAPTCHA question.
        $question = $lang["MaptchaQuestion"];
        $question = str_replace("{NUMBER1}", $number_1, $question);
        $question = str_replace("{NUMBER2}", $number_2, $question);

        // Store these for the generate_spoken_captcha_text() method.
        $this->qn1 = $number_1;
        $this->op  = " + ";
        $this->qn2 = $number_2;

        return array($question, $answer);
    }

    function generate_captcha_html($question)
    {
        $captcha = '<div id="spamhurdles_captcha_image">'.$question.'</div>';
        return array($captcha, "");
    }

    function generate_spoken_captcha_text($question) {
        return "How much is " . $this->qn1 . $this->op . $this->qn2 . "?";
    }

    function generate_text_strings()
    {
        $lang = $GLOBALS["PHORUM"]["DATA"]["LANG"]["mod_spamhurdles"];
        return array(
            'title'                   => $lang["MaptchaTitle"],
            'explain'                 => $lang["MaptchaExplain"],
            'answerfield_label'       => $lang["MaptchaFieldLabel"],
            'spoken_captcha_linktext' => $lang["MaptchaSpoken"],
            'wrong_answer_error'      => $lang["MaptchaWrongAnswer"],
        );
    }
}
?>
