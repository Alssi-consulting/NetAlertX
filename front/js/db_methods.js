// -----------------------------------------------------------------------------
// General utilities to interact with the database
// -----------------------------------------------------------------------------

// // Call to render lists
// renderList(
//   options,
//   callbackToGenerateEntries,
//   valuesArray,
//   placeholder,
//   targetField,
//   transformers
// );

// --------------------------------------------------
// Read data and place intotarget location, callback processies the results
function renderList(
  options,
  processDataCallback,
  valuesArray,
  placeholder,
  targetField,
  transformers
) {
  // Check if there are options provided
  if (options.length > 0) {
    // Determine if the first option's name is an SQL query
    const sqlQuery = isSQLQuery(options[0].name) ? options[0].name : "";

    // If there is an SQL query, fetch additional options
    if (sqlQuery) {
      // remove first item containing the SQL query
      options.shift();

      const apiUrl = `php/server/dbHelper.php?action=read&rawSql=${btoa(encodeURIComponent(sqlQuery))}`;

      $.get(apiUrl, function (sqlOptionsData) {
        
        // Parse the returned SQL data 
        const sqlOption = JSON.parse(sqlOptionsData);

        // Concatenate options from SQL query with the supplied options
        options = options.concat(sqlOption);
        

        // Process the combined options
        setTimeout(() => {
          processDataCallback(
            options,
            valuesArray,
            targetField,
            transformers,
            placeholder
          );
        }, 1);
      });
    } else {
      // No SQL query, directly process the supplied options
      setTimeout(() => {
        processDataCallback(
          options,
          valuesArray,
          targetField,
          transformers,
          placeholder
        );
      }, 1);
    }
  } else {
    // No options provided, directly process with empty options
    setTimeout(() => {
      processDataCallback(
        options,
        valuesArray,
        targetField,
        transformers,
        placeholder
      );
    }, 1);
  }
}


// --------------------------------------------------
// Check if database is locked
function checkDbLock() {
  $.ajax({
    url: "/php/server/query_logs.php?file=db_is_locked.log", 
    type: "GET",

    success: function (response) {
      // console.log(response);
      if (response == 0) {
        // console.log('Database is not locked');
        $(".header-status-locked-db").hide();
      } else {
        console.log("🟥 Database is locked:");
        console.log(response);
        $(".header-status-locked-db").show();
      }
    },
    error: function () {
      console.log("🟥 Error checking database lock status");
      $(".header-status-locked-db").show();
    },
  });
}

setInterval(checkDbLock(), 1000);
