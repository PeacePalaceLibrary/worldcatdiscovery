/*
* Processing of the registration form. Uses the JSON schema 'regSchemaObj' defined in regSchema.js
*
*/
startvalues = {
  dbIds: '638',
  heldBy:'NLVRD',
  itemsPerPage: 10,
  startIndex: 0
};

// Initialize the editor
var editorProperties =
{
  schema: srchSchemaObj,
  startval : startvalues,
  required_by_default: true,
  no_additional_properties: true,
  disable_edit_json: true,
  disable_properties: true,
  disable_collapse: true,
  //remove_empty_properties:true,
};
var editor = new JSONEditor(document.getElementById('editor'),editorProperties);

//further initialization after the form is generated
editor.on('ready',function() {
  editor.show_errors = 'change';  //interaction (default), change, always, never

  // Hook up the submit button to log to the console
  jQuery('#submit').on('click',function() {
    //empty feedback div
    jQuery('#res').html("");

    //Validate
    var errors = editor.validate();

    if(errors.length) {
      //collect and show error messages
      if (debug) console.log(errors);
      msg = '<p>Your request has NOT been sent. Correct the following fields:';
      errors.forEach(function(err) {
        msg += '- ' + editor.getEditor(err.path).schema.title + ': ' + err.message + '<br/>';
      });
      msg += '</p>'
      jQuery('#res').html(msg);
    }
    else {
      // Get the values from the editor      jQuery('#res').html(msg);
      values = editor.getValue();
      if (debug) console.log(values);

      request = jQuery.get('search.ajax.php', values);

      request.done( function(data, textStatus, jqXHR) {
        //if (debug) console.log("Data: "+data+' - textStatus: ' + textStatus);
        jQuery('#list').html(data);
     });

      request.fail(function (jqXHR, textStatus, errorThrown){
        // Log the error to the console
        if (debug) console.log("Error: "+textStatus, errorThrown);
      });
   
    }
  });


  // Hook up the Empty button
  jQuery('#empty').on('click',function() {
    var emptyURL = document.location.origin + document.location.pathname;
    window.location.assign(emptyURL);
  });
});
