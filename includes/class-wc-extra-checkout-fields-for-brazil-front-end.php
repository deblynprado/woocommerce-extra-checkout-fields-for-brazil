<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Front-end actions.
 */
class Extra_Checkout_Fields_For_Brazil_Front_End {

	/**
	 * Initialize the front-end actions.
	 */
	public function __construct() {
		global $woocommerce;

		// Load custom order data.
		add_filter( 'woocommerce_load_order_data', array( $this, 'load_order_data' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// New checkout fields.
		add_filter( 'woocommerce_billing_fields', array( $this, 'checkout_billing_fields' ) );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'checkout_shipping_fields' ) );

		// Valid checkout fields.
		add_action( 'woocommerce_checkout_process', array( $this, 'valid_checkout_fields' ) );

		// Found customers details ajax.
		add_filter( 'woocommerce_found_customer_details', array( $this, 'customer_details_ajax' ) );

		// Custom address format.
		if ( version_compare( $woocommerce->version, '2.0.6', '>=' ) ) {
			add_filter( 'woocommerce_localisation_address_formats', array( $this, 'localisation_address_formats' ) );
			add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'formatted_address_replacements' ), 1, 2 );
			add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'order_formatted_billing_address' ), 1, 2 );
			add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'order_formatted_shipping_address' ), 1, 2 );
			add_filter( 'woocommerce_user_column_billing_address', array( $this, 'user_column_billing_address' ), 1, 2 );
			add_filter( 'woocommerce_user_column_shipping_address', array( $this, 'user_column_shipping_address' ), 1, 2 );
			add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'my_account_my_address_formatted_address' ), 1, 3 );
		}
	}

	/**
	 * Load order custom data.
	 *
	 * @param  array $data Default WC_Order data.
	 *
	 * @return array       Custom WC_Order data.
	 */
	public function load_order_data( $data ) {

		// Billing
		$data['billing_persontype']    = '';
		$data['billing_cpf']           = '';
		$data['billing_rg']            = '';
		$data['billing_cnpj']          = '';
		$data['billing_ie']            = '';
		$data['billing_birthdate']     = '';
		$data['billing_sex']           = '';
		$data['billing_number']        = '';
		$data['billing_neighborhood']  = '';
		$data['billing_cellphone']     = '';

		// Shipping
		$data['shipping_number']       = '';
		$data['shipping_neighborhood'] = '';

		return $data;
	}

	/**
	 * Register and enqueues public-facing style sheet and JavaScript files.
	 */
	public function enqueue_scripts() {
		// Load scripts only in checkout.
		if ( is_checkout() || is_account_page() ) {

			// Get plugin settings.
			$settings = get_option( 'wcbcf_settings' );

			// Call jQuery.
			wp_enqueue_script( 'jquery' );

			// Fix checkout fields.
			wp_enqueue_script( 'woocommerce-extra-checkout-fields-for-brazil-front', plugins_url( 'assets/js/frontend/frontend.min.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), Extra_Checkout_Fields_For_Brazil::VERSION, true );
			wp_localize_script(
				'woocommerce-extra-checkout-fields-for-brazil-front',
				'wcbcf_public_params',
				array(
					'state'           => __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'required'        => __( 'required', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'mailcheck'       => isset( $settings['mailcheck'] ) ? 'yes' : 'no',
					'maskedinput'     => isset( $settings['maskedinput'] ) ? 'yes' : 'no',
					'addresscomplete' => isset( $settings['addresscomplete'] ) ? 'yes' : 'no',
					'person_type'     => $settings['person_type']
				)
			);
		}
	}

	/**
	 * New checkout billing fields.
	 *
	 * @param  array $fields Default fields.
	 *
	 * @return array         New fields.
	 */
	public function checkout_billing_fields( $fields ) {

		$new_fields = array();

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		// Billing First Name.
		$new_fields['billing_first_name'] = array(
			'label'       => __( 'First Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'First Name', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first' ),
			'required'    => true
		);

		// Billing Last Name.
		$new_fields['billing_last_name'] = array(
			'label'       => __( 'Last Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Last Name', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last' ),
			'clear'       => true,
			'required'    => true
		);

		if ( 0 != $settings['person_type'] ) {

			// Billing Person Type.
			if ( 1 == $settings['person_type'] ) {
				$new_fields['billing_persontype'] = array(
					'type'     => 'select',
					'label'    => __( 'Person type', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'class'    => array( 'form-row-wide' ),
					'required' => true,
					'options'  => array(
						'0' => __( 'Select', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'1' => __( 'Individuals', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'2' => __( 'Legal Person', 'woocommerce-extra-checkout-fields-for-brazil' )
					)
				);
			}

			if ( 1 == $settings['person_type'] || 2 == $settings['person_type'] ) {
				if ( isset( $settings['rg'] ) ) {
					// Billing CPF.
					$new_fields['billing_cpf'] = array(
						'label'       => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'placeholder' => _x( 'CPF', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'       => array( 'form-row-first' ),
						'required'    => false
					);

					// Billing RG.
					$new_fields['billing_rg'] = array(
						'label'       => __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'placeholder' => _x( 'RG', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'       => array( 'form-row-last' ),
						'required'    => false
					);
				} else {
					// Billing CPF.
					$new_fields['billing_cpf'] = array(
						'label'       => __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'placeholder' => _x( 'CPF', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'       => array( 'form-row-wide' ),
						'required'    => false
					);
				}
			}

			if ( 1 == $settings['person_type'] || 3 == $settings['person_type'] ) {
				// Billing Company.
				$new_fields['billing_company'] = array(
					'label'       => __( 'Company Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'placeholder' => _x( 'Company Name', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
					'class'       => array( 'form-row-wide' ),
					'required'    => false
				);

				// Billing State Registration.
				if ( isset( $settings['ie'] ) ) {
					// Billing CNPJ.
					$new_fields['billing_cnpj'] = array(
						'label'       => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'placeholder' => _x( 'CNPJ', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'       => array( 'form-row-first' ),
						'required'    => false
					);

					$new_fields['billing_ie'] = array(
						'label'       => __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'placeholder' => _x( 'State Registration', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'       => array( 'form-row-last' ),
						'required'    => false
					);
				} else {
					// Billing CNPJ.
					$new_fields['billing_cnpj'] = array(
						'label'       => __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'placeholder' => _x( 'CNPJ', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
						'class'       => array( 'form-row-wide' ),
						'required'    => false
					);
				}
			}

		} else {
			// Billing Company.
			$new_fields['billing_company'] = array(
				'label'       => __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'placeholder' => _x( 'Company', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-wide' ),
				'required'    => false
			);
		}

		if ( isset( $settings['function'] ) ) {			

			// Billing Function.
			$new_fields['billing_function'] = array(
				'type'        => 'select',
				'label'       => __( 'Function', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-first' ),
				'clear'       => false,
				'required'    => true,
				'options'     => array(
					'0'                     => __( 'Select', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'Coordenador de PAA', 'woocommerce-extra-checkout-fields-for-brazil' ) => __( 'Coordenador de PAA', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'School Director', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'School Director', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'Adjunto de Coordena&ccedil;&atilde;o', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Adjunto de Coordena&ccedil;&atilde;o', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'Coordenador Pedag&oacute;gico', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Coordenador Pedag&oacute;gico', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'Teatcher', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Teatcher', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'Other', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Other', 'woocommerce-extra-checkout-fields-for-brazil' )
				)
			);
		}

		if ( isset( $settings['new_client'] ) ) {			

			// Billing New Client.
			$new_fields['billing_new_client'] = array(
				'type'        => 'select',
				'label'       => __( 'Do you have any material of IAB?', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-last' ),
				'clear'       => true,
				'required'    => true,
				'options'     => array(
					'0'                     => __( 'Select', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'no', 'woocommerce-extra-checkout-fields-for-brazil' ) => __( 'No', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'yes', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Yes', 'woocommerce-extra-checkout-fields-for-brazil' )
				)
			);

			$new_fields['billing_materials'] = array(
				'type'        => 'select',
				'label'       => __( 'Wich One?', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-wide' ),
				'clear'       => true,
				'required'    => true,
				'options'     => array(
					'0'                     => __( 'Select', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'zero to three', 'woocommerce-extra-checkout-fields-for-brazil' ) => __( 'Zero to Three', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'pre-school program', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Pre School Program', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'literay program', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Literay Program', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'new series: structure learning', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'New Series: Structure Learning', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'collection proves brazil', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Collection Proves Brazil', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'portuguese language', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Portuguese Language', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'math', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Math', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'science', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Science', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'accelerated learning', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Accelerated Learning', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'fulltime against turn', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Fulltime Against Turn', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'graphics and calligraphy', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Graphics and Calligraphy', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'collection giants books', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Collection Giants Books', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'collection arts in pre-school', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Collection Arts in Pre School', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'teatcher training', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Teatcher Training', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'school management', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'School Management', 'woocommerce-extra-checkout-fields-for-brazil' )
				)
			);
		}

		if ( isset( $settings['birthdate_sex'] ) ) {

			// Billing Birthdate.
			$new_fields['billing_birthdate'] = array(
				'label'       => __( 'Birthdate', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'placeholder' => _x( 'Birthdate', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-first' ),
				'clear'       => false,
				'required'    => true
			);

			// Billing Sex.
			$new_fields['billing_sex'] = array(
				'type'        => 'select',
				'label'       => __( 'Sex', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-last' ),
				'clear'       => true,
				'required'    => true,
				'options'     => array(
					'0'                     => __( 'Select', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'Female', 'woocommerce-extra-checkout-fields-for-brazil' ) => __( 'Female', 'woocommerce-extra-checkout-fields-for-brazil' ),
					__( 'Male', 'woocommerce-extra-checkout-fields-for-brazil' )   => __( 'Male', 'woocommerce-extra-checkout-fields-for-brazil' )
				)
			);

		}

		// Billing Country.
		$new_fields['billing_country'] = array(
			'type'        => 'country',
			'label'       => __( 'Country', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Country', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'update_totals_on_change', 'address-field' ),
			'clear'       => false,
			'required'    => true,
		);

		// Billing Post Code.
		$new_fields['billing_postcode'] = array(
			'label'       => __( 'Post Code', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Post Code', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'update_totals_on_change', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		// Billing Anddress 01.
		$new_fields['billing_address_1'] = array(
			'label'       => __( 'Address', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Address', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'address-field' ),
			'required'    => true
		);

		// Billing Number.
		$new_fields['billing_number'] = array(
			'label'       => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Number', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		// Billing Anddress 02.
		$new_fields['billing_address_2'] = array(
			'label'       => __( 'Address line 2', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Address line 2', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'address-field' )
		);

		// Billing Neighborhood.
		$new_fields['billing_neighborhood'] = array(
			'label'       => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Neighborhood', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
		);

		// Billing City.
		$new_fields['billing_city'] = array(
			'label'       => __( 'City', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'City', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'address-field' ),
			'required'    => true
		);

		// Billing State.
		$new_fields['billing_state'] = array(
			'type'        => 'state',
			'label'       => __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'State', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		if ( isset( $settings['cell_phone'] ) ) {

			// Billing Phone.
			$new_fields['billing_phone'] = array(
				'label'       => __( 'Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'placeholder' => _x( 'Phone', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-first' ),
				'required'    => true
			);

			// Billing Cell Phone.
			$new_fields['billing_cellphone'] = array(
				'label'       => __( 'Cell Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'placeholder' => _x( 'Cell Phone', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-last' ),
				'clear'       => true
			);

			// Billing Email.
			$new_fields['billing_email'] = array(
				'label'       => __( 'Email', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'placeholder' => _x( 'Email', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-wide' ),
				'validate'    => array( 'email' ),
				'clear'       => true,
				'required'    => true
			);

		} else {

			// Billing Phone.
			$new_fields['billing_phone'] = array(
				'label'       => __( 'Phone', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'placeholder' => _x( 'Phone', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-wide' ),
				'required'    => true
			);

			// Billing Email.
			$new_fields['billing_email'] = array(
				'label'       => __( 'Email', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'placeholder' => _x( 'Email', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
				'class'       => array( 'form-row-wide' ),
				'required'    => true
			);

		}

		return apply_filters( 'wcbcf_billing_fields', $new_fields );
	}

	/**
	 * New checkout shipping fields
	 *
	 * @param  array $fields Default fields.
	 *
	 * @return array         New fields.
	 */
	public function checkout_shipping_fields( $fields ) {

		$new_fields = array();

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		// Shipping First Name.
		$new_fields['shipping_first_name'] = array(
			'label'       => __( 'First Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'First Name', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first' ),
			'required'    => true
		);

		// Shipping Last Name.
		$new_fields['shipping_last_name'] = array(
			'label'       => __( 'Last Name', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Last Name', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last' ),
			'clear'       => true,
			'required'    => true
		);

		// Shipping Company.
		$new_fields['shipping_company'] = array(
			'label'       => __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Company', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-wide' )
		);

		// Shipping Country.
		$new_fields['shipping_country'] = array(
			'type'        => 'country',
			'label'       => __( 'Country', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Country', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'update_totals_on_change', 'address-field' ),
			'required'    => true
		);

		// Shipping Post Code.
		$new_fields['shipping_postcode'] = array(
			'label'       => __( 'Post Code', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Post Code', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'update_totals_on_change', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		// Shipping Anddress 01.
		$new_fields['shipping_address_1'] = array(
			'label'       => __( 'Address', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Address', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'address-field' ),
			'required'    => true
		);

		// Shipping Number.
		$new_fields['shipping_number'] = array(
			'label'       => __( 'Number', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Number', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		// Shipping Anddress 02.
		$new_fields['shipping_address_2'] = array(
			'label'       => __( 'Address line 2', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Address line 2', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'address-field' )
		);

		// Shipping Neighborhood.
		$new_fields['shipping_neighborhood'] = array(
			'label'       => __( 'Neighborhood', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'Neighborhood', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true
		);

		// Shipping City.
		$new_fields['shipping_city'] = array(
			'label'       => __( 'City', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'City', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-first', 'address-field' ),
			'required'    => true
		);

		// Shipping State.
		$new_fields['shipping_state'] = array(
			'type'        => 'state',
			'label'       => __( 'State', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'placeholder' => _x( 'State', 'placeholder', 'woocommerce-extra-checkout-fields-for-brazil' ),
			'class'       => array( 'form-row-last', 'address-field' ),
			'clear'       => true,
			'required'    => true
		);

		return apply_filters( 'wcbcf_shipping_fields', $new_fields );
	}

	/**
	 * Checks if the CPF is valid.
	 *
	 * @param  string $cpf
	 *
	 * @return bool
	 */
	protected function is_cpf( $cpf ) {
		$cpf = preg_replace( '/[^0-9]/', '', $cpf );

		if ( 11 != strlen( $cpf ) || preg_match( '/^([0-9])\1+$/', $cpf ) ) {
			return false;
		}

		$digit = substr( $cpf, 0, 9 );

		for ( $j = 10; $j <= 11; $j++ ) {
			$sum = 0;

			for( $i = 0; $i< $j-1; $i++ ) {
				$sum += ( $j - $i ) * ( (int) $digit[ $i ] );
			}

			$summod11 = $sum % 11;
			$digit[ $j - 1 ] = $summod11 < 2 ? 0 : 11 - $summod11;
		}

		return $digit[9] == ( (int) $cpf[9] ) && $digit[10] == ( (int) $cpf[10] );
	}

	/**
	 * Checks if the CNPJ is valid.
	 *
	 * @param  string $cnpj
	 *
	 * @return bool
	 */
	protected function is_cnpj( $cnpj ) {
		$cnpj = sprintf( '%014s', preg_replace( '{\D}', '', $cnpj ) );

		if ( 14 != ( strlen( $cnpj ) ) || ( 0 == intval( substr( $cnpj, -4 ) ) ) ) {
			return false;
		}

		for ( $t = 11; $t < 13; ) {
			for ( $d = 0, $p = 2, $c = $t; $c >= 0; $c--, ( $p < 9 ) ? $p++ : $p = 2 ) {
				$d += $cnpj[ $c ] * $p;
			}

			if ( $cnpj[ ++$t ] != ( $d = ( ( 10 * $d ) % 11 ) % 10 ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Add error message in checkout.
	 *
	 * @param string $message Error message.
	 *
	 * @return string         Displays the error message.
	 */
	protected function add_error( $message ) {
		global $woocommerce;

		if ( version_compare( $woocommerce->version, '2.1', '>=' ) ) {
			wc_add_notice( $message, 'error' );
		} else {
			$woocommerce->add_error( $message );
		}
	}

	/**
	 * Valid checkout fields.
	 *
	 * @return string Displays the error message.
	 */
	public function valid_checkout_fields() {

		// Get plugin settings.
		$settings = get_option( 'wcbcf_settings' );

		if ( 0 != $settings['person_type'] ) {

			// Check CPF.
			if ( ( 1 == $settings['person_type'] && 1 == $_POST['billing_persontype'] ) || 2 == $settings['person_type'] ) {
				if ( empty( $_POST['billing_cpf'] ) ) {
					$this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ) );
				}

				if ( isset( $settings['validate_cpf'] ) && ! empty( $_POST['billing_cpf'] ) && ! $this->is_cpf( $_POST['billing_cpf'] ) ) {
					$this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'CPF', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is not valid', 'woocommerce-extra-checkout-fields-for-brazil' ) ) );
				}

				if ( isset( $settings['rg'] ) && empty( $_POST['billing_rg'] ) ) {
					$this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'RG', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ) );
				}
			}

			// Check Company and CPNJ.
			if ( ( 1 == $settings['person_type'] && 2 == $_POST['billing_persontype'] ) || 3 == $settings['person_type'] ) {
				if ( empty( $_POST['billing_company'] ) ) {
					$this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'Company', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ) );
				}

				if ( empty( $_POST['billing_cnpj'] ) ) {
					$this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ) );
				}

				if ( isset( $settings['validate_cnpj'] ) && ! empty( $_POST['billing_cnpj'] ) && ! $this->is_cnpj( $_POST['billing_cnpj'] ) ) {
					$this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'CNPJ', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is not valid', 'woocommerce-extra-checkout-fields-for-brazil' ) ) );
				}

				if ( isset( $settings['ie'] ) && empty( $_POST['billing_ie'] ) ) {
					$this->add_error( sprintf( '<strong>%s</strong> %s.', __( 'State Registration', 'woocommerce-extra-checkout-fields-for-brazil' ), __( 'is a required field', 'woocommerce-extra-checkout-fields-for-brazil' ) ) );
				}
			}
		}
	}

	/**
	 * Custom country address formats.
	 *
	 * @param  array $formats Defaul formats.
	 *
	 * @return array          New BR format.
	 */
	public function localisation_address_formats( $formats ) {
		$formats['BR'] = "{name}\n{address_1}, {number}\n{address_2}\n{neighborhood}\n{city}\n{state}\n{postcode}\n{country}";

		return $formats;
	}

	/**
	 * Custom country address format.
	 *
	 * @param  array $replacements Default replacements.
	 * @param  array $args         Arguments to replace.
	 *
	 * @return array               New replacements.
	 */
	public function formatted_address_replacements( $replacements, $args ) {
		extract( $args );

		$replacements['{number}']       = $number;
		$replacements['{neighborhood}'] = $neighborhood;

		return $replacements;
	}

	/**
	 * Custom order formatted billing address.
	 *
	 * @param  array $address Default address.
	 * @param  object $order  Order data.
	 *
	 * @return array          New address format.
	 */
	public function order_formatted_billing_address( $address, $order ) {
		$address['number']       = $order->billing_number;
		$address['neighborhood'] = $order->billing_neighborhood;

		return $address;
	}

	/**
	 * Custom order formatted shipping address.
	 *
	 * @param  array $address Default address.
	 * @param  object $order  Order data.
	 *
	 * @return array          New address format.
	 */
	public function order_formatted_shipping_address( $address, $order ) {
		$address['number']       = $order->shipping_number;
		$address['neighborhood'] = $order->shipping_neighborhood;

		return $address;
	}

	/**
	 * Custom user column billing address information.
	 *
	 * @param  array $address Default address.
	 * @param  int $user_id   User id.
	 *
	 * @return array          New address format.
	 */
	public function user_column_billing_address( $address, $user_id ) {
		$address['number']       = get_user_meta( $user_id, 'billing_number', true );
		$address['neighborhood'] = get_user_meta( $user_id, 'billing_neighborhood', true );

		return $address;
	}

	/**
	 * Custom user column shipping address information.
	 *
	 * @param  array $address Default address.
	 * @param  int $user_id   User id.
	 *
	 * @return array          New address format.
	 */
	public function user_column_shipping_address( $address, $user_id ) {
		$address['number']       = get_user_meta( $user_id, 'shipping_number', true );
		$address['neighborhood'] = get_user_meta( $user_id, 'shipping_neighborhood', true );

		return $address;
	}

	/**
	 * Custom my address formatted address.
	 *
	 * @param  array $address   Default address.
	 * @param  int $customer_id Customer ID.
	 * @param  string $name     Field name (billing or shipping).
	 *
	 * @return array            New address format.
	 */
	public function my_account_my_address_formatted_address( $address, $customer_id, $name ) {
		$address['number']       = get_user_meta( $customer_id, $name . '_number', true );
		$address['neighborhood'] = get_user_meta( $customer_id, $name . '_neighborhood', true );

		return $address;
	}

	/**
	 * Add custom fields in customer details ajax.
	 *
	 * @return void
	 */
	public function customer_details_ajax( $customer_data ) {
		$user_id = (int) trim( stripslashes( $_POST['user_id'] ) );
		$type_to_load = esc_attr( trim( stripslashes( $_POST['type_to_load'] ) ) );

		$custom_data = array(
			$type_to_load . '_number' => get_user_meta( $user_id, $type_to_load . '_number', true ),
			$type_to_load . '_neighborhood' => get_user_meta( $user_id, $type_to_load . '_neighborhood', true ),
			$type_to_load . '_persontype' => get_user_meta( $user_id, $type_to_load . '_persontype', true ),
			$type_to_load . '_cpf' => get_user_meta( $user_id, $type_to_load . '_cpf', true ),
			$type_to_load . '_rg' => get_user_meta( $user_id, $type_to_load . '_rg', true ),
			$type_to_load . '_cnpj' => get_user_meta( $user_id, $type_to_load . '_cnpj', true ),
			$type_to_load . '_ie' => get_user_meta( $user_id, $type_to_load . '_ie', true ),
			$type_to_load . '_birthdate' => get_user_meta( $user_id, $type_to_load . '_birthdate', true ),
			$type_to_load . '_sex' => get_user_meta( $user_id, $type_to_load . '_sex', true ),
			$type_to_load . '_cellphone' => get_user_meta( $user_id, $type_to_load . '_cellphone', true )
		);

		return array_merge( $customer_data, $custom_data );
	}
}

new Extra_Checkout_Fields_For_Brazil_Front_End();
