        <div class="PhorumFooterPlug" align="center">
          This <a href="http://www.phorum.org/">forum</a> powered by <a href="http://www.phorum.org/">Phorum</a>.
        </div>
      <!-- Some info for db-debugging -->
      {IF DBDEBUG}
      <small>
      {DBDEBUG->count} queries run.<br /><br />
      {LOOP DBDEBUG->queries}
      {DBDEBUG->queries->query}<br />
      {/LOOP DBDEBUG->queries}
      </small>
      {/IF}
{! these are the two div�s from header.tpl }
      </div>
    </div>
  </body>
</html>