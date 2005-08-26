<?php

if(!defined("PHORUM")) return;

function phorum_import_template($tplfile, $outfile)
{

    $fp=fopen($tplfile, "r");
    $page=fread($fp, filesize($tplfile));
    fclose($fp);

    preg_match_all("/\{[\!\/A-Za-z].+?\}/s", $page, $matches);

    settype($oldloopvar, "string");
    settype($loopvar, "string");
    settype($olddatavar, "string");
    settype($datavar, "string");
    $loopvars = array();

    foreach($matches[0] as $match){
        unset($parts);

        $string=substr($match, 1, -1);

        $string = trim($string);

        // pre-parse pointer variables
        if(strstr($string, "->")){
            $string=str_replace("->", "']['", $string);
        }

        $parts=explode(" ", $string);

        switch(strtolower($parts[0])){

            // Comment
            case "!":

                $repl="<?php // ".implode(" ", $parts)." ?>";
                break;


            case "include":

                $repl="<?php include phorum_get_template('$parts[1]'); ?>";
                break;

            case "include_once":

                $repl="<?php include_once phorum_get_template('$parts[1]'); ?>";
                break;

            case "include_var": // include a file given by a variable

                $repl="<?php include_once phorum_get_template( \$PHORUM[\"DATA\"]['$parts[1]']); ?>";
                break;

            // A define is used to create vars for the engine to use.
            case "define":

                $repl="<?php \$PHORUM[\"TMP\"]['$parts[1]']='";
                array_shift($parts);
                array_shift($parts);
                foreach($parts as $part){
                    $repl.=str_replace("'", "\\'", $part)." ";
                }
                $repl=trim($repl)."'; ?>";
                break;


            // A var is used to create vars for the template.
            case "var":

                $repl="<?php \$PHORUM[\"DATA\"]['$parts[1]']='";
                array_shift($parts);
                array_shift($parts);
                foreach($parts as $part){
                    $repl.=str_replace("'", "\\'", $part)." ";
                }
                $repl=trim($repl)."'; ?>";
                break;


            // starts a loop
            case "loop":

                $loopvars[$parts[1]]=true;
                $repl="<?php if(isset(\$PHORUM['DATA']['$parts[1]']) && is_array(\$PHORUM['DATA']['$parts[1]'])) foreach(\$PHORUM['DATA']['$parts[1]'] as \$PHORUM['TMP']['$parts[1]']){ ?>";
                break;


            // ends a loop
            case "/loop":

                $repl="<?php } unset(\$PHORUM['TMP']['$parts[1]']); ?>";
                unset($loopvars[$parts[1]]);
                break;


            // if and elseif are the same accept how the line starts
            case "if":
            case "elseif":

                // determine if or elseif
                $prefix = (strtolower($parts[0])=="if") ? "if" : "} elseif";

                // are we wanting == or !=
                if(strtolower($parts[1])=="not"){
                    $operator="!=";
                    $parts[1]=$parts[2];
                    if(isset($parts[3])){
                        $parts[2]=$parts[3];
                        unset($parts[3]);
                    } else {
                        unset($parts[2]);
                    }
                } else {
                    $operator="==";
                }

                $index=phorum_determine_index($loopvars, $parts[1]);

                // if there is no part 2, check that the value is set and not empty
                if(!isset($parts[2])){
                    if($operator=="=="){
                        $repl="<?php $prefix(isset(\$PHORUM['$index']['$parts[1]']) && !empty(\$PHORUM['$index']['$parts[1]'])){ ?>";
                    } else {
                        $repl="<?php $prefix(!isset(\$PHORUM['$index']['$parts[1]']) || empty(\$PHORUM['$index']['$parts[1]'])){ ?>";
                    }

                // if it is numeric, a constant or a string, simply set it as is
                } elseif(is_numeric($parts[2]) || defined($parts[2]) || preg_match('!"[^"]*"!', $parts[2])) {
                        $repl="<?php $prefix(isset(\$PHORUM['$index']['$parts[1]']) && \$PHORUM['$index']['$parts[1]']$operator$parts[2]){ ?>";

                // we must have a template var
                } else {

                    $index_part2=phorum_determine_index($loopvars, $parts[2]);

                    // this is a really complicated IF we are building.

                    $repl="<?php $prefix(isset(\$PHORUM['$index']['$parts[1]']) && isset(\$PHORUM['$index_part2']['$parts[2]']) && \$PHORUM['$index']['$parts[1]']$operator\$PHORUM['$index_part2']['$parts[2]']) { ?>";

                }

                // reset $prefix
                $prefix="";
                break;


            // create an else
            case "else":

                $repl="<?php } else { ?>";
                break;


            // close an if
            case "/if":

                $repl="<?php } ?>";
                break;

            case "assign":
                if(defined($parts[2])){
                    $repl="<?php \$PHORUM[\"DATA\"]['$parts[1]']=$parts[2]; ?>";
                } else {
                    $index=phorum_determine_index($loopvars, $parts[2]);

                    $repl="<?php \$PHORUM[\"DATA\"]['$parts[1]']=\$PHORUM['$index']['$parts[2]']; ?>";
                }
                break;


            // this is just for echoing vars from DATA or TMP if it is a loopvar
            default:

                if(defined($parts[0])){
                    $repl="<?php echo $parts[0]; ?>";
                } else {

                    $index=phorum_determine_index($loopvars, $parts[0]);

                    $repl="<?php echo \$PHORUM['$index']['$parts[0]']; ?>";
                }
        }

        $page=str_replace($match, $repl, $page);
    }

    if($fp=fopen($outfile, "w")){
        fputs($fp, "<?php if(!defined(\"PHORUM\")) return; ?>\n");
        fputs($fp, $page);
        fclose($fp);
    }

}


function phorum_determine_index($loopvars, $varname)
{
    if(isset($loopvars) && count($loopvars)){
        while(strstr($varname, "]")){
            $varname=substr($varname, 0, strrpos($varname, "]")-1);
            if(isset($loopvars[$varname])){
                return "TMP";
                break;
            }
        }
    }

    return "DATA";
}

?>
