import assert from 'assert';

import curricula from '../src/models/curricula.js';
import degrees from '../src/models/degrees.js';
import exams from '../src/models/exams.js';
import groups from '../src/models/groups.js';
import proposals from '../src/models/proposals.js';
import jsdom from 'jsdom';

import CapsAttachment from '../src/modules/attachment';
import { loadDashboardData } from '../src/modules/dashboard';





describe('models', function() {

});

describe('modules', function() {

    describe('upload-csv', function() {
        global.window = new jsdom.JSDOM().window;
        global.document = window.document;
        
        // import UploadCsv from '../src/modules/upload-csv';
        var UploadCsv = require("../src/modules/upload-csv.js");
        const headers = ['nome','codice','settore','crediti'];
        const line = ["giorgio", "XY", "MAT", "6"];
        var csv = new UploadCsv({
            upload_fields: headers
        });
        
        describe('split_row', () => {
            it('simple line', () => {
                assert.deepStrictEqual(csv.split_row(line.join(",")), line);
            });
            it('quoted fields', () => {
                assert.deepStrictEqual(csv.split_row('"'+line.join('","')+'"'), line);
            });
        });
        describe('to_array', () => {
            var contents = [
                headers.join(","), 
                line.join(","), 
                line.join(",")].join("\r\n")+"\r\n"; 
            csv.to_array(contents);
            it('three lines', () => {
                assert.deepStrictEqual(csv.data,[line,line]);
            });
        });
    });
});