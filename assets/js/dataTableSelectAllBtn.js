/**
 * Created by Kraft on 02.09.2016.
 */
export default function initSelectAllBtn() {
	$.fn.dataTable.ext.buttons.select_all = {
		page: 'all',

		action: function ( e, dt, node, config ) {
			let rows_all      = dt.rows({page: config.page});
			let rows_selected = dt.rows({page: config.page, selected: true});

			if(rows_selected.data().length < rows_all.data().length){
				rows_all.select();
			}else{
				rows_all.deselect();
			}
		},
		init: function ( dt , node, config ) {
			let that = this;

			// .DT namespace listeners are removed by DataTables automatically on table destroy
			dt.on( 'draw.dt.DT select.dt.DT deselect.dt.DT', function () {
				if ( that.select.items() === 'row' ) {
					const count_all      = that.rows({page: config.page}).count();
					const count_selected = that.rows({page: config.page, selected: true}).count();

					if(count_selected === count_all){
						that.text('<i class="far fa-check-square"></i>');
						that[0].node.setAttribute('title', that.i18n('buttons.selectNone', 'Deselect all'));
					}else if(count_selected > 0){
						that.text('<i class="far fa-minus-square"></i>');
						that[0].node.setAttribute('title', that.i18n('buttons.selectAll', 'Select All'));
					}
					else{
						that.text('<i class="far fa-square"></i>');
						that[0].node.setAttribute('title', that.i18n('buttons.selectAll', 'Select All'));
					}
				}
			});
		},

	};
};