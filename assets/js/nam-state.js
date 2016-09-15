/*global wc_country_select_params */
/**
* Edit by Comfythemes
* @since 1.3
*/
jQuery.noConflict();
jQuery( document ).ready(function( $ ) {

	// wc_country_select_params is required to continue, ensure the object exists
	if ( typeof nam_state_params === 'undefined' ) {
		return false;
	}
	if ( $().select2 ) {
		$( '#billing_city, #shipping_city' ).select2({minimumResultsForSearch: Infinity} );
		var wc_district_select_select2 = function() {
			$( '#billing_district_vn,#shipping_district_vn' ).show( function() {
				$( this ).select2( {
					width: '100%'});
			});
		};
		wc_district_select_select2(); 
		$( document.body ).bind( 'city_to_district_changed', function() {
			wc_district_select_select2();
		});
	};
	
	var state = nam_state_params.state;
	$(document.body).on('change',"[id$='_city']", function(e) {

		var value = $(this).find(':selected').index(),
			city 	= $(this).val();

		if( (city!==undefined) && (city!==null) &&  (city.length > 0) ){

			var districts = state[value].districts,
			options = '';

			for( var index in districts ) {
				if ( districts.hasOwnProperty( index ) ) {
					options = options + '<option data-key="' + index + '" value="' + districts[ index ] + '">' + districts[ index ] + '</option>';
				}
			}
			var current_id=$(this).attr('id');
			var district_vn='#'+current_id.slice(0,-4)+"district_vn";
			$(district_vn).html( '<option value="">Xin chọn quận/huyện</option>' + options ).select2("val","");
		}
		$(document).trigger('city_to_district_changed');
	});
});
