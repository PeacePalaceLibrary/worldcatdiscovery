// Custom validators must return an array of errors or an empty array if valid
JSONEditor.defaults.custom_validators.push(function(schema, value, path) {
  var errors = [];
  if(schema.format==="datebirth") {
    if (value == '') {
      //ok
    }
    else if(/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/.test(value)) {
      p = value.split('-');
      ok = (Number(p[0])>1900) && (Number(p[0])<2050);
      ok = ok && (Number(p[1])>0) && (Number(p[1])<13);
      ok = ok && (Number(p[2])>0) && (Number(p[2])<32);
      if(!ok) {
        // Errors must be an object with `path`, `property`, and `message`
        errors.push({
          path: path,
          property: 'format',
          message: 'Dates must be in the format "YYYY-MM-DD"'
        });
      }
    }
    else {
      errors.push({
        path: path,
        property: 'format',
        message: 'Dates must be in the format "YYYY-MM-DD"'
      });
    }
  }
  return errors;
});


