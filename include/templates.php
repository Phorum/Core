<?php

    if(!defined("PHORUM")) return;

    function phorum_import_template($tplfile, $outfile)
    {

        global $PHORUM;

        $fp=fopen($tplfile, "r");
        $page=fread($fp, filesize($tplfile));
        fclose($fp);

        preg_match_all("/\{[\!\/A-Za-z].+?\}/s", $page, $matches);

        settype($oldloopvar, "string");
        settype($loopvar, "string");
        settype($olddatavar, "string");
        settype($datavar, "string");

        foreach($matches[0] as $match){
            unset($parts);

            $string=substr($match, 1, -1);

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

                    // DATA is default array
                    $index="DATA";
                    
                    // check for loopvars and use TMP if it is one.
                    if(strstr($parts[1], "'") && isset($loopvars)  && count($loopvars)){
                        $varname=substr($parts[1], 0, strpos($parts[1], "'"));
                        if(isset($loopvars[$varname])){                    
                            $index="TMP";
                        }
                    }                    

                    if(isset($parts[2])){
                        if(!is_numeric($parts[2]) && !defined($parts[2])){
                            $parts[2]="\"$parts[2]\"";
                        }
                        $repl="<?php $prefix(isset(\$PHORUM['$index']['$parts[1]']) && \$PHORUM['$index']['$parts[1]']==$parts[2]){ ?>";
                    } else {
                        $repl="<?php $prefix(isset(\$PHORUM['$index']['$parts[1]']) && !empty(\$PHORUM['$index']['$parts[1]'])){ ?>";
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
                        $repl="<?php $parts[1]; ?>";
                        $repl="<?php \$PHORUM[\"DATA\"]['$parts[1]']=$parts[2]";
                    } else {
                        // DATA is default array
                        $index="DATA";

                        // check for loopvars and use TMP if it is one.
                        if(strstr($parts[2], "'") && isset($loopvars)  && count($loopvars)){
                            $varname=substr($parts[2], 0, strpos($parts[2], "'"));
                            if(isset($loopvars[$varname])){
                                $index="TMP";
                            }
                        }

                        $repl="<?php \$PHORUM[\"DATA\"]['$parts[1]']=\$PHORUM['$index']['$parts[2]']; ?>";
                    }
                    break;


                // this is just for echoing vars from DATA or TMP if it is a loopvar
                default:

                    if(defined($parts[0])){
                        $repl="<?php echo $parts[0]; ?>";
                    } else {
                        // DATA is default array
                        $index="DATA";

                        // check for loopvars and use TMP if it is one.
                        if(isset($loopvars)  && count($loopvars)){
                            $varname=$parts[0];
                            while(strstr($varname, "]")){
                                $varname=substr($varname, 0, strrpos($varname, "]")-1);
                                if(isset($loopvars[$varname])){
                                    $index="TMP";
                                    break;
                                }
                            }
                        }

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

?>
