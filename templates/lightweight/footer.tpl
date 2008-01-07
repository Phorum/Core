<div id="footer-plug">This <a href="http://www.phorum.org/">forum</a> is powered by <a href="http://www.phorum.org/">Phorum</a>.</div>

<!-- Some info for db-debugging -->
{IF DBDEBUG}
<small>
{DBDEBUG->count} queries run.<br /><br />
{LOOP DBDEBUG->queries}
{DBDEBUG->queries->query}<br />
{/LOOP DBDEBUG->queries}
</small>
{/IF}
</div>
</body>
</html>