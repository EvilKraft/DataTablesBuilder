/**
 * Created by Kraft on 31.10.2017.
 */

function oldExportAction(self, e, dt, button, config) {

    let _dtButtons  = $.fn.dataTable.ext.buttons;
    let btnName     = '';
    const classList = button[0].classList;

    // Common option that will use the HTML5 or Flash export buttons
    switch (true) {
        case classList.contains('buttons-copy'):  btnName = _dtButtons.copy(dt, config);  break;
        case classList.contains('buttons-csv'):   btnName = _dtButtons.csv(dt, config);   break;
        case classList.contains('buttons-excel'): btnName = _dtButtons.excel(dt, config); break;
        case classList.contains('buttons-pdf'):   btnName = _dtButtons.pdf(dt, config);   break;
        case classList.contains('buttons-print'): btnName = 'print';                      break;
        default: return;
    }

    _dtButtons[btnName].action.call(self, e, dt, button, config);
}

function newExportAction(e, dt, button, config) {
    let self = this;
    let info = dt.page.info();

    if(info.serverSide === false){
        oldExportAction(self, e, dt, button, config);
        return;
    }

    let oldStart = dt.settings()[0]._iDisplayStart;

    dt.one('preXhr', function (e, s, data) {
        // Just this once, load all data from the server...
        data.start = 0;
        data.length = 2147483647;

        dt.one('preDraw', function (e, settings) {
            // Call the original action function
            oldExportAction(self, e, dt, button, config);

            dt.one('preXhr', function (e, s, data) {
                // DataTables thinks the first item displayed is index 0, but we're not drawing that.
                // Set the property to what it was before exporting.
                settings._iDisplayStart = oldStart;
                data.start = oldStart;
            });

            // Reload the grid with the original page. Otherwise, API functions like table.cell(this) don't work properly.
            setTimeout(dt.ajax.reload, 0);

            // Prevent rendering of the full data to the DOM
            return false;
        });
    });

    // Require the server with the new one-time export settings
    dt.ajax.reload();
}

let exportButtonDefaults = {
    action: newExportAction,
    exportOptions: {
        //columns: ':not(:last-child)'
        //columns: ':not(.action_btns_container)'
        //columns: [ 0, ':visible' ]
        columns: [ 0, ':visible:not(.action_btns_container)' ]
    }
};

export default function initExportCollection() {

    $.fn.dataTable.ext.buttons.excel2 = {
        text: 'Export to Excel 2',
        action: function ( e, dt) {
            const method   = 'GET';
            const exporter = 'excel';
            const tableId  = dt.table().node().id;
            const url      = dt.ajax.url()

            const params = $.param($.extend({}, dt.ajax.params(), {'_dt': tableId, '_exporter': exporter}));

            // Credit: https://stackoverflow.com/a/23797348
            const xhr = new XMLHttpRequest();
            xhr.open(method, method === 'GET' ? (url + '?' +  params) : url, true);
            xhr.responseType = 'arraybuffer';
            xhr.onload = function () {
                if (this.status === 200) {
                    let filename = "";
                    const disposition = xhr.getResponseHeader('Content-Disposition');
                    if (disposition && disposition.indexOf('attachment') !== -1) {
                        const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                        const matches = filenameRegex.exec(disposition);
                        if (matches != null && matches[1]) {
                            filename = matches[1].replace(/['"]/g, '');
                        }
                    }

                    const type = xhr.getResponseHeader('Content-Type');

                    let blob;
                    if (typeof File === 'function') {
                        try {
                            blob = new File([this.response], filename, { type: type });
                        } catch (e) { /* Edge */ }
                    }

                    if (typeof blob === 'undefined') {
                        blob = new Blob([this.response], { type: type });
                    }

                    if (typeof window.navigator.msSaveBlob !== 'undefined') {
                        // IE workaround for "HTML7007: One or more blob URLs were revoked by closing the blob for which they were created. These URLs will no longer resolve as the data backing the URL has been freed."
                        window.navigator.msSaveBlob(blob, filename);
                    }
                    else {
                        const URL = window.URL || window.webkitURL;
                        const downloadUrl = URL.createObjectURL(blob);

                        if (filename) {
                            // use HTML5 a[download] attribute to specify filename
                            const a = document.createElement("a");
                            // safari doesn't support this yet
                            if (typeof a.download === 'undefined') {
                                window.location = downloadUrl;
                            }
                            else {
                                a.href = downloadUrl;
                                a.download = filename;
                                document.body.appendChild(a);
                                a.click();
                            }
                        }
                        else {
                            window.location = downloadUrl;
                        }

                        setTimeout(function() { URL.revokeObjectURL(downloadUrl); }, 100); // cleanup
                    }
                }
            };

            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send(method === 'POST' ? params : null);
        }
    };

    $.fn.dataTable.ext.buttons.export_collection = {
        extend: 'collection',
        text: '<i class="far fa-save" aria-hidden="true"></i>',
        titleAttr: function ( dt ) {
            return dt.i18n( 'buttons.Export', 'Export');
        },
        buttons: [
            $.extend( true, {}, exportButtonDefaults, {
                extend: 'copy',
                className: 'dtExport exportCopy',
            }),
            $.extend( true, {}, exportButtonDefaults, {
                extend: 'csv',
                className: 'dtExport exportCsv',
            }),
            $.extend( true, {}, exportButtonDefaults, {
                extend: 'excel',
                className: 'dtExport exportExcel',
            }),
            $.extend( true, {}, {
                extend: 'excel2',
                className: 'dtExport exportExcel buttons-excel2',
            }),
            $.extend( true, {}, exportButtonDefaults, {
                extend: 'pdf',
                className: 'dtExport exportPdf',
            }),
            $.extend( true, {}, exportButtonDefaults, {
                extend: 'print',
                className: 'dtExport exportPrint',
            }),
        ],
    };
};


