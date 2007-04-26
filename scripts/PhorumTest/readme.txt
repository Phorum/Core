This is a start of first UnitTests for Phorum.
Actually they are more a test on itself with some functions
to see and check the output.

PhorumTest.php is plain testing and returns a html-site
PhorumTestCoverage.php does the testing and returns a html-site 
                   and generates a coverage report in the report-directory.
                   That takes quite a while and needs the XDebug-PHP-Extension to be installed.

To run these tests / analysis with your webbrowser you need to rename htaccess to .htaccess so that 
it overrides the one in scripts itself. Be aware to don't keep that open to the public.