<?php

// The length for the CAPTCHA codes that we generate.
define("CAPTCHA_CODE_LENGTH", 5);

// The default set of characters to create random strings with.
define("CAPTCHA_RANDOMCHARS",
    "0123456789" .
    "abcdefghijklmnopqrstuvwxyz",
    "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
);

class captcha_base
{
    // {{{ Method: generate_captcha()
    /**
     * Generates a CAPTCHA. This is the main method of the class,
     * which will be called by Phorum for creating a CAPTCHA.
     * It will generally not be overridden by descending classes.
     *
     * It will return an array containing the following fields:
     *
     * - answer:
     *   The answer for the CAPTCHA, to compare against user's answer.
     * - error:
     *   The error string to display to the user, in case the CAPTCHA
     *   was answered wrong.
     * - input_fieldname:
     *   The name of the input field where the users enters the answer.
     * - spoken_text:
     *   The text to feed to the speech engine, in case the CAPTCHA
     *   is turned into spoken text for vision impaired people.
     * - html_form:
     *   The main HTML form code for the CAPTCHA.
     * - html_after_form:
     *   Extra HTML code, that has to be run after the form (at the end
     *   of the page). This can for example be used for executing javascript
     *   code that would delay page loading when it would be run directly
     *   within the form.
     */
    function generate_captcha()
    {
        $lang = $GLOBALS["PHORUM"]["DATA"]["LANG"]["mod_spamhurdles"];
        $conf = $GLOBALS["PHORUM"]["mod_spamhurdles"]["captcha"];

        list($question, $answer) = $this->generate_question_and_answer();

        // Generate text strings for the CAPTCHA.
        $strings = $this->generate_text_strings();

        // Generate HTML code for the CAPTCHA question part.
        list($html_form, $html_after_form) =
            $this->generate_captcha_html($question);

        // Create HTML and text for a spoken version of the captcha.
        $spoken_html = "";
        $spoken_text = "";
        if ($conf["spoken_captcha"] && file_exists($conf["flite_location"])) {
            $say = $this->generate_spoken_captcha_text($question);
            if ($say !== NULL) {
                $spoken_text = $say;
                $spoken_html =
                    '<div id="spamhurdles_spoken_captcha"><a href="{SPOKENURL}">' .
                    $strings["spoken_captcha_linktext"] .
                    '</a></div>';
            }
        }

        // Since we can simply assign any fieldname that we like here,
        // just take a random one as an extra hurdle.
        $fieldname = $this->generate_random_string(10);

        // Generate the full CAPTCHA HTML code.
        $fldlen = strlen($answer);
        $html_form =
            '<div id="spamhurdles_captcha">' .
            '<div id="spamhurdles_captcha_title">'.$strings["title"].'</div>' .
            '<div id="spamhurdles_captcha_explain">'.$strings["explain"].'</div>' .
            $html_form . $spoken_html .
            '<label for="spamhurdles_captcha_answer_input">' .
            $strings["answerfield_label"] .
            '</label>' .
            '<input type="text" name="'.$fieldname.'" ' .
            'id="spamhurdles_captcha_answer_input" ' .
            'value="{FIELDVALUE}" size="' . ($fldlen+1) . '" ' .
            'maxlength="' . $fldlen. '" />' .
            '</div>';

        return array(
            "question"        => $question,
            "answer"          => $answer,
            "input_fieldname" => $fieldname,
            "spoken_text"     => $spoken_text,
            "html_form"       => $html_form,
            "html_after_form" => $html_after_form,
            "error"           => $strings["wrong_answer_error"]
        );
    }
    // }}}

    // {{{ Method: generate_capcha_html()
    /**
     * Generate the HTML for displaying the captcha question. This method
     * has to return an array, containing two elements:
     *
     * html_form
     *   The main HTML form code for the CAPTCHA.
     *
     * html_after_form
     *   Extra HTML code, that has to be run after the form (at the end
     *   of the page). This can for example be used for executing javascript
     *   code that would delay page loading when it would be run directly
     *   within the form.
     *
     * @param string $question
     *     The text representation of the question to display.
     *
     * @return array
     *     An array containing the html form and after form code.
     */
    function generate_captcha_html($question)
    {
        die("generate_captcha_html() method not implemented for " .
            "CAPTCHA class " . htmlspecialchars(get_class($this)));
    }
    // }}}

    // {{{ Method: generate_question_and_answer()
    /**
     * Generates a question and an answer for the CAPTCHA. These are
     * plain text representations of a question and answer.
     *
     * @return array
     *     An array containing the question and answer strings.
     */
    function generate_question_and_answer()
    {
        // Create a safe CAPTCHA code, with characters that cannot be mistaken
        // for other characters by accident. A bit of time, remote info and
        // random data is used to create a nice code.
        $chars = "34679bCcdDeEFGhHjJkKLmMnNpPtTuUvVwWxXyY";
        $code = $this->generate_random_string(CAPTCHA_CODE_LENGTH, $chars);

        return array($code, $code);
    }
    // }}}

    // {{{ Method: generate_text_strings()
    /**
     * Generates the text strings to use in the CAPTCHA. The method has
     * to return an array, containing the following key fields:
     *
     * title
     *   The title to display above the CAPTCHA.
     * explain
     *   The explanation for using the CAPTCHA.
     * answerfield_label
     *   The label for the field where the user can enter the answer.
     * spoken_captcha_linktext
     *   The text for the link to the spoken CAPTCHA.
     * wrong_answer_error
     *   THe message to display in case the user answers the CAPTCHA wrong.
     *
     * @return array
     *     An array containing the required text strings.
     */
    function generate_text_strings()
    {
        $lang = $GLOBALS["PHORUM"]["DATA"]["LANG"]["mod_spamhurdles"];
        return array(
            'title'                   => $lang["CaptchaTitle"],
            'explain'                 => $lang["CaptchaExplain"],
            'answerfield_label'       => $lang["CaptchaFieldLabel"],
            'spoken_captcha_linktext' => $lang["CaptchaSpoken"],
            'wrong_answer_error'      => $lang["CaptchaWrongCode"],
        );
    }
    // }}}

    // {{{ Method: generate_spoken_captcha_text()
    /**
     * Converts the question into a string that has to be fed to
     * the speech engine, to create a spoken CAPTCHA for vision
     * impaired users. It also returns the text to use on the
     * HTML link to the spoken CAPTCHA.
     *
     * This default implementation will simply spell out the
     * question.
     *
     * @param string $question
     *     The CAPTCHA question.
     *
     * @return array
     *     An array containing the text to feed to
     *     the speech engine and the text to use on the link
     *     to listen to the CAPTCHA.
     */
    function generate_spoken_captcha_text($question)
    {
        // Generate the text to say.
        $spell_it_out = array(
            'A' => 'A for Alpha',   'B' => 'B for Bravo',
            'C' => 'C for Charlie', 'D' => 'D for Delta',
            'E' => 'E for Eco',     'F' => 'EF for Fox',
            'G' => 'G for Golf',    'H' => 'H for Hotel',
            'I' => 'I for India',   'J' => 'JAY for Juliet',
            'K' => 'K for Kilo',    'L' => 'EL for Lima',
            'M' => 'M for Mike',    'N' => 'N for November',
            'O' => 'O for Oscar',   'P' => 'PEE for Papa',
            'Q' => 'Q for Quebec',  'R' => 'ARE for Romeo',
            'S' => 'S for Sierra',  'T' => 'TEA for Tango',
            'U' => 'YOU for Union', 'V' => 'V for Victor',
            'W' => 'W for Whisky',  'X' => 'X for X-ray',
            'Y' => 'WHY for Yanky', 'Z' => 'Z for Zulu',
            '0' => 'the number 0',  '1' => 'the number 1',
            '2' => 'the number 2',  '3' => 'the number 3',
            '4' => 'the number 4',  '5' => 'the number 5',
            '6' => 'the number 6',  '7' => 'the number 7',
            '8' => 'the number 8',  '9' => 'the number 9',
            ' ' => 'a whitespace',
        );


        $say = "";
        $len = strlen($question);
        for($i=0; $i<$len; $i++) {
            $char = strtoupper(substr($question, $i, 1));
            $say .= "Character " . ($i+1) . " is " .
                    $spell_it_out[$char] .
                    ($i == ($len-2) ? ", and " :
                      (($i+1) == $len ? "." : ", "));

        }

        return $say;
    }
    // }}}

    // {{{ Method: generate_image()
    /**
     * Generates an image to show when the imagecaptcha=... parameter
     * is set in the Phorum URL. This method is called from outside
     * this class by the module script spamhurdles.php.
     */
    function generate_image($answer) {
        die("generate_image() method not implemented for " .
            "CAPTCHA class " . htmlspecialchars(get_class($this)));
    }
    // }}}

    // {{{ Method: generate_random_string()
    /**
     * Utility method for generating random strings.
     *
     * @param integer $length
     *     The length of the random string to create.
     *
     * @param string $chars
     *     A string containing the characters to choose from or
     *     NULL to use the default random characters.
     *
     * @return string
     *     The random string.
     */
    function generate_random_string($length, $chars = NULL)
    {
        if ($chars === NULL) $chars = CAPTCHA_RANDOMCHARS;
        $string = '';
        for ($i = 0; $i<$length; $i++) {
            $string .= substr($chars, rand(0, strlen($chars)-1), 1);
        }
        return $string;
    }
    // }}}

    // {{{ Method: check_answer()
    /**
     * This method will check if an entered captcha code is valid.
     *
     * @param array $info
     *     An array, containing CAPTCHA information. This is the array
     *     that was generated by {@link generate_captcha()}.
     *
     * @return mixed
     *     An error string in case of problems.
     *     NULL if the code is valid.
     */
    function check_answer($info)
    {
        $PHORUM = $GLOBALS["PHORUM"];

        // Retrieve the posted answer.
        $fn = $info["input_fieldname"];
        $fieldvalue = isset($_POST[$fn]) ? strtoupper(trim($_POST[$fn])) : "";

        // Check the answer.
        if ($fieldvalue != strtoupper($info["answer"])) {
            return isset($spamhurdles["captcha"]["error"])
              ? $spamhurdles["captcha"]["error"]
              : $PHORUM["DATA"]["LANG"]["mod_spamhurdles"]["CaptchaWrongCode"];
        }
        return NULL;
    }
}

?>
