<?php

if(!defined("PHORUM")) return;


function phorum_gen_password($charpart=4, $numpart=3)
{
    $vowels = array("a", "e", "i", "o", "u");
    $cons = array("b", "c", "d", "g", "h", "j", "k", "l", "m", "n", "p", "r", "s", "t", "u", "v", "w", "tr", "cr", "br", "fr", "th", "dr", "ch", "ph", "wr", "st", "sp", "sw", "pr", "sl", "cl");

    $num_vowels = count($vowels);
    $num_cons = count($cons);

    $password="";

    for($i = 0; $i < $charpart; $i++){
        $password .= $cons[mt_rand(0, $num_cons - 1)] . $vowels[mt_rand(0, $num_vowels - 1)];
    }

    $password = substr($password, 0, $charpart);

    if($numpart){
        $max=(int)str_pad("", $numpart, "9");
        $min=(int)str_pad("1", $numpart, "0");
        
        $num=(string)mt_rand($min, $max);
    }

    return strtolower($password.$num);
}    
    
function phorum_check_ban_lists($value, $type)
{
    if(!isset($GLOBALS['PHORUM']['banlists'])) return true;

    $banlists = $GLOBALS['PHORUM']['banlists'];

    $value = trim($value);

    if (!empty($value)) {
        if (isset($banlists[$type]) && is_array($banlists[$type])) {
            foreach($banlists[$type] as $item) {
                if (($item["pcre"] && @preg_match("/\b$item[string]\b/i", $value)) ||
                        (!$item["pcre"] && stristr($value , $item["string"]))) {
                    return false;
                } 
            } 
        } 
    } 

    return true;
} 


/*    

    function phorum_dyn_profile_html($field, $value="")
    {

        // $PHORUM["PROFILE_FIELDS"][]=array("name"=>"real_name", "type"=>"text", "length"=>100, "required"=>0);
        // $PHORUM["PROFILE_FIELDS"][]=array("name"=>"email", "type"=>"text", "length"=>100, "required"=>1);
        // $PHORUM["PROFILE_FIELDS"][]=array("name"=>"hide_email", "type"=>"bool", "default"=>1);
        // $PHORUM["PROFILE_FIELDS"][]=array("name"=>"sig", "type"=>"text", "length"=>0, "required"=>0);


        $PHORUM=$GLOBALS["PHORUM"];

        $html="";

        switch ($field["type"]){

            case "text":
                if($field["length"]==0){
                    $html="<textarea name=\"$field[name]\" rows=\"15\" cols=\"50\" style=\"width: 100%\">$value</textarea>";
                } else {
                    $html="<input type=\"text\" name=\"$field[name]\" size=\"30\" maxlength=\"$field[length]\" value=\"$value\" />";
                }
                break;
            case "check":
                $html ="<input type=\"checkbox\" name=\"$field[name]\" value=\"1\" ";
                if($value) $html.="checked ";
                $html.="/> $field[caption]";
                break;
            case "radio":
                foreach($field["options"] as $option){
                    $html.="<input type=\"radio\" name=\"$field[name]\" value=\"$option\" ";
                    if($value==$option) $html.="checked ";
                    $html.="/> $option&nbsp;&nbsp;";
                }
                break;
            case "select":
                $html ="<select name=\"$field[name]\" size=\"1\">";
                foreach($field["options"] as $option){
                    $html.="<option value=\"$option\"";
                    if($value==$option) $html.=" selected";
                    $html.=">$option</option>";
                }
                $html.="</select>";
                break;

        }

        return $html;

    }

*/

?>
