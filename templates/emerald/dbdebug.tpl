{! This template will be used in case database debugging is enabled.         }
{! Enabled debugging is done by adding a "dbdebug" field to the db config.   }
{! If its value is "1", then the number of executed queries will be tracked. }
{! If its value is "2", then all queries will be shown, including timing.    }

{IF DBDEBUG}
  Total number of database queries: {DBDEBUG->count}<br />
  {IF DBDEBUG->queries}
  Total time for database queries: {DBDEBUG->time}<br />
  <table style="border-collapse: collapse" class="list">
    <tr>
      <th style="text-align: left">Number</th>
      <th style="text-align: left">Query&nbsp;time</th>
      <th style="text-align: left">Query</th>
    </tr>
    {LOOP DBDEBUG->queries}
      <tr>
        <td>{DBDEBUG->queries->number}</td>
        <td>{DBDEBUG->queries->time}</td>
        <td>{DBDEBUG->queries->query}</td>
      </tr>
    {/LOOP DBDEBUG->queries}
  </table>
  {/IF}
{/IF}

