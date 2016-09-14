<?php
/*
 * @wordpress-plugin
 * Plugin Name:       Woocommerce - Quan/Huyen - VietNam
 * Plugin URI:        http://github.com/pnghai/
 * Description:
 * Version:           1.1
 * Author:            pnghai
 * Author URI:        http://github.com/pnghai/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       nam
 * Domain Path:       /languages
 */
defined( 'ABSPATH' ) OR exit;


final class Woocommerce_State_VietNam
{

	/**
	* @var The single instance of the class
	* @author Comfythemes
	* @since 1.0
	*/
	protected static $_instance = null;

	/**
	* Main Plugin Instance
	*
	* Ensures only one instance of Plugin is loaded or can be loaded.
	*
	* @author Comfythemes
	* @since 1.0
	* @static
	* @return Main instance
	*/
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	* Plugin Constructor.
	*
	* @author Comfythemes
	* @since 1.0
	*/
	public function __construct() {

		$this->define_constants();
		$this->init_hooks();

	}

	/**
	* Check plugin Woocommerce is active.
	*
	* @author Comfythemes
	* @since 1.0
	*/
	public static function check_woo_active(){

		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if (is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return true;
		}else{
			return false;
		}
	}

	/**
	* Define Constants
	*
	* @author Comfythemes
	* @since 1.0
	*/
	private function define_constants() {

		$this->define('NAM_VER', '1.0');
		$this->define('NAM_NAME', esc_html__('Woocommerce - Dropdown state - VietNam', 'wzd'));
		$this->define('NAM_FOLDER', basename(dirname(__FILE__)));
		$this->define('NAM_DIR', plugin_dir_path(__FILE__));
		$this->define('NAM_URL', plugin_dir_url(NAM_FOLDER).NAM_FOLDER.'/');
		$this->define('NAM_ASSETS', NAM_URL.'assets/');
		$this->define('NAM_JS', NAM_URL.'assets/js/');
		$this->define('NAM_CSS', NAM_URL.'assets/css/');
		$this->define('NAM_IMG', NAM_URL.'assets/images/');
		$this->define('NAM_JSON',NAM_DIR.'/assets/js/data-state-vn.json');
		$this->nam_json=json_decode(file_get_contents( NAM_JSON ),true);
	}


	/**
	* Define constant if not already set
	*
	* @param  string $name
	* @param  string|bool $value
	* @author Comfythemes
	* @since 1.0
	*/
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	* Hook into actions and filters
	*
	* @author Comfythemes
	* @since 1.0
	*/
	public function init_hooks() {

		if ( ! $this->check_woo_active() ) {
			add_action( 'admin_notices', array( $this, 'installation_notice') );
			return;
		}
		add_action( 'wp_enqueue_scripts', array($this, 'nam_enqueue_scripts') );
		add_action( 'admin_enqueue_scripts', array($this, 'nam_admin_enqueue_scripts') );
		add_filter( 'woocommerce_checkout_fields' , array($this, 'nam_additional_checkout_fields' ));
		add_action( 'woocommerce_checkout_update_order_meta', array($this, 'nam_save_field_distric'));
		add_action('woocommerce_checkout_process', array($this,'nam_fields_validation'));
		add_action('woocommerce_checkout_update_order_review', array($this,'nam_set_district_vn_session'),10,2);

		add_filter( 'woocommerce_default_address_fields', array($this,'wc_custom_order_address_fields'), 10, 1 );
		add_filter('woocommerce_cart_shipping_packages', array($this,'add_custom_data_to_packages'), 10, 1);
		add_filter('woocommerce_get_country_locale',array($this,'custom_override_locale_setting'));
	}

	/**
	* Register the script for the public-facing side of the site.
	*
	* @since    1.0
	*/
	public function nam_enqueue_scripts(){

		if ( is_checkout() || is_wc_endpoint_url('edit-address') ) {

			wp_register_script( 'nam-state-select', NAM_JS . 'nam-state.js', array( ), NAM_VER, true );
			wp_enqueue_script( 'nam-state-select');
			wp_localize_script( 'nam-state-select', 'nam_state_params', array( 'state' => $this->nam_json) );
		}
	}

	public function nam_admin_enqueue_scripts(){
		wp_enqueue_script( 'nam-admin', NAM_JS . 'nam-admin.js', array(), NAM_VER, true );

		if( is_user_logged_in() ){
					$current_user = wp_get_current_user();
					$distric = get_user_meta( $current_user->ID, 'billing_district_vn', true );
				}else{
					$distric = '';
				}

		wp_localize_script( 'nam-admin', 'nam_state_params', array( 'distric' => $distric ) );
	}
	
	/**
	 * Additional fields
	*/
	public function nam_additional_checkout_fields ($fields){
		unset($fields['billing']['billing_state']);

		$fields['billing']['billing_city']['placeholder'] = 'Chọn tỉnh/thành phố';
		$fields['billing']['billing_city']['type'] = 'select';
		$fields['billing']['billing_city']['class'] = array( 'form-row-wide', 'address-field' );
		$fields['billing']['billing_city']['options']=array(""=>"Xin chọn tỉnh/thành phố");
		foreach ($this->nam_json as $key=>$value) {
			$fields['billing']['billing_city']['options'][$value['name']]=$value['name'];
		}

		$districts=array(""=>"Xin chọn quận huyện");
		foreach ($this->nam_json['1']['districts'] as $key=>$value) {
			$districts[$value]=$value;
		}
		$fields['billing']['billing_district_vn'] = array(
			'type' => 'select',
			'label' => 'Quận/huyện',
			'placeholder' => 'Chọn quận/huyện',
			'required'  => true,
			'class'     => array('form-row-wide','address-field', 'update_totals_on_change' ),
			'clear'     => true,
			'options' => $districts
		);
		return ($fields);
	}
	/**
	 *
	 */
	function wc_custom_order_address_fields( $fields ) {

		$fields['city']['class'] = array( 'form-row-wide', 'address-field' );
		$fields['city']['clear'] = true;
		$order = array(
			'first_name',
			'last_name',
			'email',
			'phone',
			'country',
			'city',
		'postcode',
			'district_vn',
			'address_1',
			'address_2',
		);
		foreach( $order as $field ) {
			$ordered_fields[$field] = $fields[$field];
		}
		$fields = $ordered_fields;

		return $fields;
	}
	public function custom_override_locale_setting( $locale ) {
		$locale['VN']['postcode_before_city']=false;
		return $locale;
	}
	/**
	* Save field with user id vs order id
	*/
	public function nam_save_field_distric( $order_id ){

		if ( ! empty( $_POST['billing_district_vn'] ) ) {
					update_post_meta( $order_id, 'billing_district_vn', sanitize_text_field( $_POST['billing_district_vn'] ) );

					if( is_user_logged_in() ){
						$current_user = wp_get_current_user();
						update_user_meta( $current_user->ID, 'billing_district_vn', sanitize_text_field( $_POST['billing_district_vn'] ) );
					}
			}

	}

	public function add_custom_data_to_packages($packages) {
		$packages[0]['destination']['district_vn'] = WC()->session->get('district_vn' );
		return $packages;
	}

		/**
		 * Validate not nulled data
		 */
		public function nam_fields_validation() {
				if ( ! $_POST['billing_district_vn'] )
						wc_add_notice( __( 'Xin nhập quận/huyện của nơi thanh toán.' ), 'error' );
	}
	/**
	 * Set session variable [district_vn]
	 */
	public function nam_set_district_vn_session($post_data){
		parse_str($post_data, $urlparams);
		WC()->session->set( 'district_vn', empty( $urlparams['billing_district_vn'] ) ? '' : sanitize_text_field($urlparams['billing_district_vn']));
	}

		/**
	* Display notice if woocommerce is not installed
	*
	* @author Comfythemes
	* @since 1.0
	*/
		public function installation_notice() {
				echo '<div class="error" style="padding:15px; position:relative;"><a href="http://wordpress.org/plugins/woocommerce/">Woocommerce</a>  must be installed and activated before using <strong>'.NAM_NAME.'</strong> plugin. </div>';
		}

}

/**
* Plugin load
*/
function Woocommerce_State_VietNam_Load_Plugin() {
	return Woocommerce_State_VietNam::instance();
}
$GLOBALS['wsvn'] = Woocommerce_State_VietNam_Load_Plugin();
?>
