function dtRowDelete(event) {
    event.preventDefault();
    let btn = $(event.currentTarget);
    btn.trigger("blur");

    let id  = btn.data('pkey');
    let row = $('#row_'+id);
    let api = $(event.delegateTarget).DataTable();

    if (confirm(api.i18n('buttons.rowDeleteConfirm', 'Are you sure you wont to delete this item?'))){
        $.ajax({
            url: window.location.href+'/'+id,
            type: "DELETE",
            dataType: "json",
        }).done(function(data) {
            if(data.status === 1){
                api.row(row).remove().draw();

                appendAlert('success', api.i18n('buttons.itemDeleted', 'Item deleted'));
            }else{
                data.errors.forEach(function(error) {
                    appendAlert('error', error.message);
                });
            }
        }).fail(function(jqXHR, textStatus) {
            appendAlert('error', textStatus);
        });
    }
}

function dtRowsDelete(event, dt, node, conf) {
    let rows = dt.rows('.selected');
    let ids  = rows.nodes().to$().map(function() {return $(this).data('pkey');}).get();
//      let ids  = rows.ids().toArray().map(function(value) { return value.replace(/row_(.+)/, "$1"); });

    if(ids.length === 0){
        return;
    }

    if(!confirm(dt.i18n('buttons.rowsDeleteConfirm', 'Are you sure you wont to delete selected items?'))){
        return;
    }

    $.ajax({
        url: window.location.href+"/"+ids.join(','),
        type: "DELETE",
        dataType: "json",
    }).done(function(data) {
        if(data.status === 1){
            rows.remove().draw();

            appendAlert('success', dt.i18n('buttons.itemsDeleted', 'Items deleted'));
        }else{
            data.errors.forEach(function(error) {
                appendAlert('error', error.message);
            });
        }
    }).fail(function(jqXHR, textStatus) {
        appendAlert('error', textStatus);
    });
}

function dtRowMove(event){
    event.preventDefault();
    let btn = $(event.currentTarget);
    btn.trigger("blur");

    const id  = btn.data('pkey');

    $.ajax({
        url: window.location.href+'/'+id+'/move',
        type: "PUT",
        dataType: "json",
        data: {direction: event.data.direction}
    }).done(function(data) {
        if(data.status === 1){
            $(event.delegateTarget).DataTable().draw();
        }else{
            data.errors.forEach(function(error) {
                appendAlert('error', error.message);
            });
        }
    }).fail(function(jqXHR, textStatus) {
        appendAlert('error', textStatus);
    });
}

export default function initActionBtns() {
    $.fn.dataTable.render.dataTableActionBtns = function (  ) {
        const className = "btn btn-link text-decoration-none";

        return function ( data, type, row, meta ) {
            if(type !== 'display' || meta.settings.bDrawing === false) {
                return data;
            }

            const id      = row['DT_RowData']['pkey'];
//          const id      = row['DT_RowId'].replace(/row_(.+)/, "$1");
            const url     = window.location.href + '/' + id;
            const api     = new $.fn.dataTable.Api(meta.settings);
            const actions = api.init().dataTableActionBtns;
            const isFirst = meta.row === 0;
            const isLast  = meta.row === meta.settings.json.recordsTotal - 1;

            let newData = '';
            for (let i = 0; i < actions.length; ++i) {
                switch (actions[i]){
                    case 'create'   : break;
                    case 'update'   : newData += '<a      href="'+url+'"     class="'+className+' text-primary dtRowUpdate" title="'+api.i18n('buttons.edit', 'Edit')+'"></a>';          break;
                    case 'delete'   : newData += '<button data-pkey="'+id+'" class="'+className+' text-danger  dtRowDelete" title="'+api.i18n('buttons.delete', 'Delete')+'"></button>'; break;
                    case 'addChild' : newData += '<a      href="'+url+'/new" class="'+className+' text-success dtRowChild"  title="'+api.i18n('buttons.addChild', 'Add child')+'"></a>'; break;

                    case 'move'     :
                        const mvUpDisabled = (isFirst) ? ' disabled' : '';
                        const mvDnDisabled = (isLast)  ? ' disabled' : '';

                        newData += '<button data-pkey="'+id+'" class="'+className+' dtRowMoveUp '+mvUpDisabled+'" title="'+api.i18n('buttons.moveUp', 'Move up')+'"></button>';
                        newData += '<button data-pkey="'+id+'" class="'+className+' dtRowMoveDn '+mvDnDisabled+'" title="'+api.i18n('buttons.moveDn', 'Move down')+'"></button>';

                        break;
                }
            }

            return newData;
        };
    };

    $.fn.dataTable.ext.buttons.create = {
        titleAttr: function ( dt ) {
            return dt.i18n( 'buttons.create', 'Add new item');
        },
        className: 'dtCreateBtn',
        action: function ( e, dt, node, config ) {
            window.location = window.location.href+'/new';
        }
    };

    $.fn.dataTable.ext.buttons.delete = {
        titleAttr: function ( dt ) {
            return dt.i18n( 'buttons.deleteItems', 'Delete items');
        },
        className: 'dtDeleteBtn',
        action: function ( e, dt, node, conf ) {
            dtRowsDelete(e, dt, node, conf);
        },
        enabled: false,
        init: function ( dt , node, config ) {
            let that = this;

            dt.on( 'draw.dt.DT select.dt.DT deselect.dt.DT', function () {
                if ( that.select.items() === 'row' ) {
                    that.enable(that.rows({selected: true}).count() !== 0);
                }
            });
        }
    };

    $.fn.dataTable.dataTableActionBtns = function ( inst ) {
        let api = new $.fn.dataTable.Api( inst );

        const container = new $.fn.dataTable.Buttons(api, {
            //    name: 'main',
            buttons: api.init().dataTableActionBtns || ['create', 'delete'],
        }).container();

        // API so the feature wrapper can return the node to insert
        this.container = function () {
            return container;
        };
    };
    //    $.fn.DataTable.dataTableActionBtns = $.fn.dataTable.dataTableActionBtns;

    // Subscribe the feature plug-in to DataTables, ready for use
    $.fn.dataTable.ext.feature.push({
        fnInit: function( settings ){
            const btn = new $.fn.dataTable.dataTableActionBtns(settings);
            return btn.container();
        },
        cFeature: "b"
    });

    $(document).on( 'init.dt', function(e){
        $(e.target).on('click', '.dtRowDelete', dtRowDelete);
        $(e.target).on('click', '.dtRowMoveUp', {direction: 'up'}, dtRowMove);
        $(e.target).on('click', '.dtRowMoveDn', {direction: 'dn'}, dtRowMove);
    });
};
