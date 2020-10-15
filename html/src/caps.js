/* The following lines contain the includes for bundle-js; they are sorted
 * in the correct way. */
// require 'jquery'
// require 'jquery-easing/dist/jquery.easing.1.3.umd.min.js'
// require 'bootstrap/dist/js/bootstrap.bundle.min.js'
// require './sb-admin-2.js'

global.jQuery = require('jquery');

const jQueryEasing = require('jquery.easing');

const bootstrap = require('bootstrap');
const sbadmin = require('./sb-admin-2.js');

global.Chart = require('chart.js');

/*
 * This file contains a few utility functions that are useful throughout the CAPS code base, and not linked to a
 * specific page.
 */

global.Caps = {
    /*
     * This is a bit of a hack: we change the URL in the page to make the
     * controller render the CSV version of the content. This will keep all
     * the specified filters in place.
     */
    'downloadCSV': function() {
        location.pathname += '.csv';
    },

    // Remove the key&value pair from the URL, mainly uesd to remove some filters
    // for the current table
    'removeQueryParam': function(param) {
        let url = window.location.href;
        let rx = new RegExp(param + '=[^&]*');
        location.href = url.replace(rx, '');
    },

    // Submit a form by injecting the name and value of an element; this is used
    // in several places where we allow to operate on a set of elements selected
    // by checkboxes, even with buttons that are outside the HTML <form> tags.
    'submitForm': function(form_id, data, action_message) {
        if (action_message == null || confirm(action_message)) {
            let form = document.getElementById(form_id);

            for (var key in data) {
                // Insert hidden input entries in the form
                let inp = document.createElement('input');
                inp.name = key;
                inp.value = data[key];
                inp.style = "display: none";
                form.appendChild(inp);
            }

            form.submit();
        }
    },
};
