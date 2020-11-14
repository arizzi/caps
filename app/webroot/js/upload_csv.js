var csv_contents = "";
var csv_data = [];
var csv_column_separator = ",";
var csv_has_headers = 1;
var csv_headers = [];
var csv_column_map = [];
var csv_auto_detect_headers = 1;

// https://github.com/trekhleb/javascript-algorithms/tree/master/src/algorithms/string/levenshtein-distance
/**
 * @param {string} a
 * @param {string} b
 * @return {number}
 */
function levenshteinDistance(a, b) {
  // Create empty edit distance matrix for all possible modifications of
  // substrings of a to substrings of b.
  const distanceMatrix = Array(b.length + 1).fill(null).map(() => Array(a.length + 1).fill(null));

  // Fill the first row of the matrix.
  // If this is first row then we're transforming empty string to a.
  // In this case the number of transformations equals to size of a substring.
  for (let i = 0; i <= a.length; i += 1) {
    distanceMatrix[0][i] = i;
  }

  // Fill the first column of the matrix.
  // If this is first column then we're transforming empty string to b.
  // In this case the number of transformations equals to size of b substring.
  for (let j = 0; j <= b.length; j += 1) {
    distanceMatrix[j][0] = j;
  }

  for (let j = 1; j <= b.length; j += 1) {
    for (let i = 1; i <= a.length; i += 1) {
      const indicator = a[i - 1] === b[j - 1] ? 0 : 1;
      distanceMatrix[j][i] = Math.min(
        distanceMatrix[j][i - 1] + 1, // deletion
        distanceMatrix[j - 1][i] + 1, // insertion
        distanceMatrix[j - 1][i - 1] + indicator, // substitution
      );
    }
  }

  return distanceMatrix[b.length][a.length];
}

function csv_split_row(row) {
    var result = [];
    while(row.length>0) {
        if (row[0]==='"' || row[0]==="'") {
            var c = row.charAt(0);
            var f = row.indexOf(c + csv_column_separator, 1);
            if (f>=0) {
                result.push(row.substring(1,f));
                row = row.substring(f+csv_column_separator.length+1);
                continue;
            }
            if (row.charAt(row.length-1) === c) {
                result.push(row.substring(1,row.length-2));
                break;
            }
        } 
        var f = row.indexOf(csv_column_separator);
        if (f>=0) {
            result.push(row.substring(0,f));
            row = row.substring(f+csv_column_separator.length);
            continue;
        }
        result.push(row);
        break;
    }
    return result;
}

function csv_to_array() {
    // TODO: usare libreria dedicata, ad esempio: papaparse

    var lines = csv_contents.split("\n");
    csv_data = []
    csv_headers = [];
    for (var i=0; i<lines.length; i++) {
        if (jQuery.trim(lines[i]) === "") {
            continue;
        }

        // var row = lines[i].split(csv_column_separator);
        var row = csv_split_row(lines[i]);

        if (i == 0 && csv_has_headers) {
            csv_headers = row;
        } else {
            csv_data.push(row);
        }
    }
    // populate headers selection

    if (!csv_has_headers) {
        csv_headers = Array(csv_data[0].length);
        for (var i=0;i<csv_data[0].length;i++) {
            csv_headers[i] = "colonna " + (i+1);
        }
    }

    var html = "";
    for (var i=0;i<csv_headers.length;++i) {
        html += "<option value=" + i + ">"+ csv_headers[i]+"</option>\n";
    }
    for (var i=0;i<csv_upload_fields.length;++i) {
        jQuery("select[name='csv_field["+i+"]']").html(html);
        jQuery("select[name='csv_field["+i+"]']").val(csv_column_map[i]);
    }
}

function fill_table_html() {
    table_html = "";
    let validation_context = {};
    for (var i=-1; i<csv_data.length; i++) {
        var row = Array(csv_upload_fields.length+2);
        if (i==-1) {
            delimiter = "th";
            row[0]="";
            var j;
            for (j=1;j<row.length-1;++j) {
                row[j] = csv_upload_fields[j-1];
            }
            row[j] = "validazione";
        } else {
            delimiter = "td";
            var error = null;
            if (csv_validator) {
                var obj = {};
                csv_upload_fields_db.forEach(function(field, j) {
                    obj[field] = csv_data[i][csv_column_map[j]];
                });
                error = csv_validator(obj, validation_context);
            }
            var j = 0;
            row[j] = "<input type='checkbox' id='csv_row_" + i + "' " + (error ? "" : "checked='checked'" )+ ">";
            for (var j=1;j<row.length-1;++j) {
                row[j] = csv_data[i][csv_column_map[j-1]];
            }
            row[j] = error?("<span class='text-danger'>" + error + "</span>"):"";
        }
        table_html += "<tr><"+delimiter+">" + row.join("</"+delimiter+"><"+delimiter+">")+"</"+delimiter+"></tr>\n";
    }
    jQuery("#input_table").html(table_html);
    return table_html;
}

function preparePreview() {
    csv_to_array(csv_contents);
    if (csv_column_map.length != csv_upload_fields.length) {
        csv_column_map = Array(csv_upload_fields.length);
        for (var i=0;i<csv_upload_fields.length;i++) {
            csv_column_map[i]=i;
        }
    }
    for(var i=0;i<csv_upload_fields.length;++i) {
        if (csv_auto_detect_headers) {
            var best = 0;
            var dist = 99999;
            for(var j=0;j<csv_headers.length;++j) {
                var d = levenshteinDistance(csv_upload_fields[i].toLowerCase(),
                    csv_headers[j].toLowerCase());
                // console.log("distance("+csv_upload_fields[i]+","+csv_headers[j]+")="+d);
                if (d<dist) {
                    best = j;
                    dist = d;
                }
            }
            csv_column_map[i] = best;
            jQuery("select[name='csv_field["+i+"]']").val(best);
        } else {
            var val = parseInt(jQuery("select[name='csv_field["+i+"]']").val());
            csv_column_map[i] = val;
        }
    }
    var html = fill_table_html();
    jQuery("#csv_preview_table").html(html);
}

function csv_upload_file(f) {
//    var f = evt.target.files[0];
    if (f) {
        var r = new FileReader();
        r.onload = function(e) {
            csv_contents = e.target.result;
            jQuery("#csv_options_div").show();
            preparePreview();
        }
        r.readAsText(f);
    } else {
        alert("Failed to load file");
    }
}

function csvSubmit() {
    var payload = csv_data.filter(function(row, i) {
        return jQuery("#csv_row_" + i).prop('checked');
    }).map(function(row){
        var obj = {};
        for (var i=0;i<csv_upload_fields.length;i++){
            obj[csv_upload_fields_db[i]] = row[csv_column_map[i]];
        }
        return obj;
    })

    var form = document.createElement('form');
    form.style.visibility = 'hidden';
    form.method = 'POST';
    form.action = '';

    var input = document.createElement('input');
    input.name = '_csrfToken';
    input.value = csrf_token;
    form.appendChild(input);

    input = document.createElement('input');
    input.name = 'payload';
    input.value = JSON.stringify(payload);
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
}

jQuery("document").ready(function(){
    jQuery("#csv_file_input").change(function (evt) {
        csv_upload_file(evt.target.files[0]);
    });
    jQuery("#csv_file_reload").click(function (evt) {
        csv_upload_file(jQuery("#csv_file_input").files[0]);
    });
    jQuery("select[name='csv_separator']").change(function() {
        csv_column_separator = jQuery(this).val();
        preparePreview();
    });
    jQuery("select[name='csv_headers']").change(function() {
        csv_has_headers = parseInt(jQuery(this).val());
        preparePreview();
    })
    for (var i=0;i<csv_upload_fields.length;i++) {
        jQuery("select[name='csv_field["+i+"]']").change(function() {
            csv_auto_detect_headers = 0;
            for (var k=0;k<csv_upload_fields.length;k++){
                csv_column_map[k] = parseInt(jQuery("select[name='csv_field["+k+"]']").val());
            }
            preparePreview();
        })
    }
});
