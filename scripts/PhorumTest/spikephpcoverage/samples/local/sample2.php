<?php
    $temp = array(
        'report_name' => $report_name,
        'db_name' => $db_name,
        'created_by' => $_SESSION['webdbUsername'],
        'report_sql' => $report_sql
    );

    if(1 == 0) {
        echo "Something\n";
    }
    else if(1 == 1) { echo "Something else\n"; }
    else {
        echo "Anything\n";
    }

    if(false) {
        /* $x = "3"; */ ; // something
    }
    else 
        $filter = $ops = array(2*3);

    $var1 
    += time();

    $var2 
    += 2;

    $a = 2*3;

    class AClass {
        public $a;
        var $b = "b";
        private $c = "b"; 
        protected $d = "b";

        function __construct() {
            echo "in Base class\n";
        }

        function foo() {
            return "hi";
        }
    }

    class BClass extends AClass {
        function __construct() {
            echo "in Sub class\n";
            parent::__construct();
        }

        function foo() {
            return "low";
        }
    }

    $b = new BClass();
    $b->foo();

    $a_variable = 
    "some string";
    $a_variable
    = "some string";

    $SupportLevelInfo['sla_units'] = $row['default_sla_units'];
    $SupportLevelInfo['support_level_description'] = 
    $row['support_level_description'];

    $strCollation = "str";
    echo '            <th>' . "\n"   
    . '                &nbsp;' . $strCollation 
    . '            </th>' . "\n"; 

?>
