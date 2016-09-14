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
	var state = nam_state_params.state;
	$(document.body).on('change','#billing_city', function(e) {

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

			$('#billing_district_vn').html( '<option value="">Xin chọn quận/huyện</option>' + options ).select2("val","");
		}

	});

});
