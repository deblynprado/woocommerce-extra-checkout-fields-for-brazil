/* global wcbcf_public_params */
/**
 * Interacting with New Client and Materials fields.
 */
(function ( $ ) {
	'use strict';

	$(function () {

		/**
		 * Hide materials fields
		 *
		 * @param  {string}
		 *
		 * @return {void}
		 */
		function materialsFields( current ) {
			$( '#billing_materials_field' ).hide();			

			if ( '1' === current ) {
				$( '#billing_materials_field' ).show();				
			}			
		}

		if ( '0' !== wcbcf_public_params.new_client ) {
			// Required fields.
			$( '#billing_materials_field label' )
				.append( ' <abbr class="required" title="' + wcbcf_public_params.required + '">*</abbr>' );

			if ( '1' === wcbcf_public_params.new_client ) {
				materialsFields( $( '#billing_new_client' ).val() );

				$( '#billing_new_client' ).on( 'change', function () {
					var current = $( this ).val();

					materialsFields( current );
				});
			}
		}

	});

}( jQuery ) );