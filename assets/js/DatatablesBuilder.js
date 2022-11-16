import initActionBtns       from './dataTableActionBtns.min.js';
import initColumnFilter     from './dataTableColumnFilter.min.js';
import exportButtonDefaults from './dataTableExportCollection.min.js';
import initSelectAllBtn     from './dataTableSelectAllBtn.min.js';

(function($) {
    initActionBtns();
    initColumnFilter();
    exportButtonDefaults();
    initSelectAllBtn();

    /**
     * Initializes the datatable dynamically.
     */
    $.fn.initDataTables = function(config, options) {
        const url = window.location.origin + window.location.pathname;

        let root = this;
        root.html('<table id="'+config.name+'" class="table table-striped table-bordered table-hover table-sm" style="width: 100%;"></table>');
        //root.html(config.template);

        for (const column of config.options.columns){
            if(column.className === 'action_btns_container'){
                column.render = $.fn.dataTable.render.dataTableActionBtns();
                column.width  = '1%';
            }
        }

        // Merge all options from different sources together and add the Ajax loader
        let dtOpts = $.extend({}, typeof config.options === 'function' ? {} : config.options, options, {
            ajax: {
                url: url + '/datatable',
                type: config.method
            },
        });

        return new Promise((fulfill, reject) => {
            let dt = $('table', root).DataTable(dtOpts);

            fulfill(dt);
        });
    };

    $.extend( true, $.fn.dataTable.defaults, {

        scrollY: true /*'64vh'*/,
        scrollX: true,
        scrollCollapse: true,
        scroller: {
            loadingIndicator: true,
            displayBuffer: 20
        },


        buttons: [
            'select_all',
            'export_collection',
            {
                extend: 'colvis',
                text: '',
                className: 'dtColVisBtn',
                titleAttr: 'Column visibility',
                columns: ":not('.action_btns_container')",
                postfixButtons: [ 'colvisRestore' ],
            },
        ],

        /*                language: (function() {
                            let data = {{ dtLanguage|raw }};
                            $.ajaxSetup({async: false});

                            $.getJSON( "/libs/DataTables/Plugins/i18n/ru.json", function( json ) {
                                data = $.extend(true, {}, data, json);
                            });
                            $.ajaxSetup({async: true});

                            return data;
                        }()),*/

        // language: {{ dtLanguage|raw }},

        select: {
            style: 'multi',
            selector: 'td:not(.action_btns_container)',
            blurable: true
        },
        stateSave: true,
        stateSaveParams: function (settings, data) {
            delete data.search;
            for (let i = 0; i < data.columns.length; i++){
                delete data.columns[i].search;
            }
        },


        dataTableColumnFilter: true,


        // autoWidth: false, //Disable smart width calculations

        // Fixed header
        // fixedHeader: {
        //     header: true,
        //     footer: true
        // }

    });
    $.fn.dataTable.Buttons.defaults.dom.button.className = 'btn btn-default';
    //$.fn.dataTable.settings.oScroll.iBarWidth = '20px';

    $.fn.dataTable.ext.errMode = 'throw';     //throw a Javascript error to the browser's console, rather than alerting it.

}(jQuery));

/**
 * Gets the data table height based upon the browser page
 * height and the data table vertical position.
 *
 * @return integer Data table height, in pixels.
 */
function jsGetDataTableHeightPx() {
    let id = "tableData";

    // set default return height
    let retHeightPx = 350;

    // no nada if there is no dataTable (container) element
    let dataTable = document.getElementById(id);
    if(!dataTable) {
        return retHeightPx;
    }

    //console.log($("#tableData").DataTable().page.len());

    // do nada if we can't determine the browser height
    const pageHeight = $(window).height();
    if(pageHeight < 0) {
        return retHeightPx;
    }

    // determine the data table height based upon the browser page height
    let dataTableHeight = pageHeight - 320; //default height
    let dataTablePos = $("#"+id).offset();
    if(dataTablePos != null && dataTablePos.top > 0) {
        // the data table height is the page height minus the top of the data table,
        // minus space for any buttons at the bottom of the page
        dataTableHeight = pageHeight - dataTablePos.top - 120;


        // clip height to min. value
        retHeightPx = Math.max(100, dataTableHeight);
    }

    //    retHeightPx = retHeightPx + 50;

    return retHeightPx;
}