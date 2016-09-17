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
		add_filter( 'woocommerce_checkout_fields' , array($this, 'nam_additional_checkout_fields' ),10,1);
		add_action( 'woocommerce_checkout_update_order_meta', array($this, 'nam_save_field_distric'));
		add_action('woocommerce_checkout_update_order_review', array($this,'nam_set_district_vn_session'),10,2);
		add_filter( 'woocommerce_default_address_fields', array($this,'wc_custom_order_address_fields'), 10, 1 );
		add_filter('woocommerce_cart_shipping_packages', array($this,'add_custom_data_to_packages'), 10, 1);
		add_filter('woocommerce_get_country_locale',array($this,'custom_override_locale_setting'),10,1);
		add_filter('woocommerce_admin_billing_fields', array($this,'add_district_fields'),10,1);
		add_filter('woocommerce_admin_shipping_fields', array($this,'add_district_fields'),10,1);
		add_filter('woocommerce_order_formatted_billing_address', array($this,'nam_order_formatted_billing_address'),10,2);
		add_filter('woocommerce_order_formatted_shipping_address', array($this,'nam_insert_district_to_shipping_address'),10,2);
    add_filter('woocommerce_shipping_address_map_url_parts',array($this,'nam_insert_district_to_shipping_address'),10,2);
		add_filter('woocommerce_formatted_address_replacements',array($this,'nam_formatted_address_replacements'),10,2);
		add_filter('woocommerce_localisation_address_formats',array($this,'nam_localisation_address_formats'),10,1);
		add_filter('woocommerce_my_account_my_address_formatted_address',array($this,'nam_my_account_my_address_formatted_address'),10,3);
		add_filter('woocommerce_address_to_edit',array ($this,'nam_address_to_edit'),10,1);
		add_action('woocommerce_checkout_process', array($this,'is_valid_district'));
    add_filter('woocommerce_get_order_address',array($this,'nam_get_order_address'),10,3);
	}

	/**
	* Register the script for the public-facing side of the site.
	*
	* @since    1.0
	*/
	public function nam_enqueue_scripts(){

		if ( is_checkout() || is_wc_endpoint_url('edit-address') ) {
			$depend=array('jquery');
			if (is_plugin_active('woocommerce-multistep-checkout/woocommerce-multistep-checkout.php'))
				$depend[]='wmc-wizard';
			wp_register_script( 'nam-state-select', NAM_JS . 'nam-state.js', $depend, NAM_VER, true );
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

	function setup_city_selector(&$city_field){
		$city_field['placeholder']='Chọn tỉnh/thành phố';
		$city_field['type']='select';
		$city_field['class'] = array( 'form-row-wide', 'address-field' );
		$city_field['options'] = array(""=>"Xin chọn tỉnh/thành phố");
		foreach ($this->nam_json as $key=>$value) {
			$city_field['options'][$value['name']]=$value['name'];
		}
	}

	function setup_district_selector(&$district_field,$default){
		$districts=array(""=>"Xin chọn quận huyện");
		foreach ($this->nam_json[$default]['districts'] as $key=>$value) {
			$districts[$value]=$value;
		}
		$district_field['type'] = 'select';
		$district_field['label'] = 'Quận/huyện';
		$district_field['placeholder'] = 'Chọn quận/huyện';
		$district_field['required'] = true;
		$district_field['class' ] = array('form-row-wide','address-field', 'update_totals_on_change' );
		$district_field['clear'] = true;
		$district_field['options'] = $districts;
	}
	/**
	 * Additional fields
	*/
	public function nam_additional_checkout_fields ($fields){
		unset($fields['billing']['billing_state']);
		unset($fields['billing']['billing_company']);
		unset($fields['billing']['billing_postcode']);
		unset($fields['shipping']['shipping_postcode']);
		unset($fields['shipping']['shipping_company']);
		$fields['billing']['billing_email']['class'] = array("form-row-wide");
		$fields['billing']['billing_phone']['class']= array('form-row-wide');
		$fields['shipping']['shipping_email']['placeholder']= 'Nhập email người nhận';
		$fields['shipping']['shipping_phone']['required']= true;
		$fields['shipping']['shipping_phone']['placeholder']= 'Nhập số điện thoại người nhận';
		$types=array('billing','shipping');
		foreach ($types as $type){
			$this->setup_city_selector($fields[$type][$type.'_city']);
			if ( is_user_logged_in()){
				$current_user = wp_get_current_user();
				$city = get_user_meta( $current_user->ID, $type.'_city', true );
				$cityid=$this->find_city_id($city);
				$cityid = ($cityid==NULL)?"1":$cityid;
			}
			else $cityid='1';
			$this->setup_district_selector($fields[$type][$type.'_district_vn'],$cityid);	
		}
		return ($fields);
	}
	/**
	 *
	 */
	function wc_custom_order_address_fields( $fields ) {

		$fields['district_vn'] = array (
				'label'        => __( 'Quận/huyện', 'woocommerce' ),
				'required'     => true,
				'class'        => array( 'form-row-wide', 'address-field' )
			);
		//thêm các field này để đảm bảo thứ tự
		$fields['email']= array(
				'label'        => __( 'Email', 'woocommerce' ),
				'required'     => true,
				'class'        => array( 'form-row-wide' ),
				'clear'        => true,
				'autocomplete' => 'email');	
		$fields['phone']= array(
				'label'        => __( 'Phone', 'woocommerce' ),
				'required'     => true,
				'class'        => array( 'form-row-wide' ),
				'clear'        => true,
				'autocomplete' => 'phone');
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
			if (isset($fields[$field]))
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
		if ( ! empty( $_POST['shipping_district_vn']) && !empty( $_POST['ship_to_different_address'] ) ) {
			update_post_meta( $order_id, 'shipping_district_vn', sanitize_text_field( $_POST['shipping_district_vn'] ) );

			if( is_user_logged_in() ){
				$current_user = wp_get_current_user();
				update_user_meta( $current_user->ID, 'shipping_district_vn', sanitize_text_field( $_POST['shipping_district_vn'] ) );
			}
		}
	}

	public function add_custom_data_to_packages($packages) {
		$packages[0]['destination']['district_vn'] = WC()->session->get('district_vn' );
		return $packages;
	}

	/**
	 * Set session variable [district_vn]
	 */
	public function nam_set_district_vn_session($post_data){
		parse_str($post_data, $urlparams);
		$to_district=!empty( $urlparams['billing_district_vn'] )?sanitize_text_field($urlparams['billing_district_vn']):"";
		if (!empty($urlparams['ship_to_different_address']))
			$to_district=!empty( $urlparams['shipping_district_vn'] )?sanitize_text_field($urlparams['shipping_district_vn']):$to_district;
		WC()->session->set( 'district_vn', $to_district);
	}

	public function add_district_fields($fields){
		$new_fields= array(
			'first_name' => $fields['first_name'],
			'last_name' => $fields['last_name'],
			'email' => $fields['email'],
			'phone' => $fields['phone'],
			'city' => $fields['city'],
			'district_vn' => array (
				'label'=>"Quận",
				'show'=>false
				),
			'address_1' => $fields['address_1']
		);
		$new_fields['address_1']['wrapper_class']="form-field-wide";
		$new_fields['phone']['label']="Số điện thoại";
		$new_fields['email']['label']="Email";
		return $new_fields;
	}

	/**
	 * insert district to address string after $address[$key]
	 * default : get billing_district_vn
	 *	@return array
	 */
  function nam_insert_district_to_address ($address,$order,$type="billing",$key="address_2"){
    $offset = array_search($key, array_keys($address));

    $result = array_merge
            (
              array_slice($address, 0, $offset),
              array('district_vn' => ($type==='billing')?$order->billing_district_vn:$order->shipping_district_vn),
              array_slice($address, $offset, null)
            );
    return $result;
  }

	public function nam_insert_district_to_shipping_address($address,$order){
    return $this->nam_insert_district_to_address($address,$order,'shipping');
	}

  public function nam_order_formatted_billing_address($address,$order){
    return $this->nam_insert_district_to_address($address,$order);
  }
	public function nam_formatted_address_replacements($formatted_address,$args){
		extract( $args );
		$formatted_address = array(
			'{first_name}'       => $first_name,
			'{last_name}'        => $last_name,
			'{name}'             => $first_name . ' ' . $last_name,
			'{company}'          => $company,
			'{address_1}'        => $address_1,
			'{address_2}'        => $address_2,
			'{district_vn}'        => $district_vn,
			'{city}'             => $city,
			'{state}'            => $full_state,
			'{postcode}'         => $postcode,
			'{country}'          => $full_country,
			'{first_name_upper}' => strtoupper( $first_name ),
			'{last_name_upper}'  => strtoupper( $last_name ),
			'{name_upper}'       => strtoupper( $first_name . ' ' . $last_name ),
			'{company_upper}'    => strtoupper( $company ),
			'{address_1_upper}'  => strtoupper( $address_1 ),
			'{address_2_upper}'  => strtoupper( $address_2 ),
			'{district_vn_upper}'=> strtoupper($district_vn),
			'{city_upper}'       => strtoupper( $city ),
			'{state_upper}'      => strtoupper( $full_state ),
			'{state_code}'       => strtoupper( $state ),
			'{postcode_upper}'   => strtoupper( $postcode ),
			'{country_upper}'    => strtoupper( $full_country )
		);
		return $formatted_address;
	}

	public function nam_localisation_address_formats($countries){
		$countries['VN']="{name}\n{company}\n{address_1}\n{district_vn}\n{city}\n{country}";
		return $countries;
	}

	public function nam_my_account_my_address_formatted_address($address, $customer_id, $name){
		$address['district_vn'] = get_user_meta( $customer_id, $name . '_district_vn', true );
		return $address;
	}

  public function nam_get_order_address($address, $type,$order){
      $address['district_vn'] = ( 'billing' === $type ) ? $order->billing_district_vn:
      $order->shipping_district_vn;
    return $address;
  }
	function find_city_id($name){
		foreach ($this->nam_json as $key =>$value){
			if (strcmp($value['name'],$name)==0)
				return $key;
		}
		return NULL;
	}
	public function nam_address_to_edit($address){
		unset($address['billing_postcode']);
		unset($address['shipping_postcode']);
		unset($address['billing_address_2']);
		unset($address['shipping_address_2']);
		if (!empty($address['billing_city']))
			$this->setup_city_selector($address['billing_city']);
		if (!empty($address['shipping_city']))
			$this->setup_city_selector($address['shipping_city']);
		if (!empty($address['billing_district_vn']))
			$this->setup_district_selector($address['billing_district_vn'],$this->find_city_id($address['billing_city']['value']));
		if (!empty($address['shipping_district_vn']))
			$this->setup_district_selector($address['shipping_district_vn'],$this->find_city_id($address['shipping_city']['value']));
		return $address;
	}

	public function is_valid_district() {
		$load_address=array("billing"=>"Quận thanh toán");
		if (!empty($_POST['ship_to_different_address']))
			$load_address['shipping']="Quận giao hàng";
		foreach ($load_address as $key=>$warning):
			$district = sanitize_text_field($_POST[$key.'_district_vn']);
			$city = sanitize_text_field ($_POST[$key.'_city']);
			$city_id=$this->find_city_id($city);
			if ($city_id==NULL) return;
			if ($district==NULL) return;
			// your function's body above, and if error, call this wc_add_notice
			if (array_search($district,$this->nam_json[$city_id]['districts'])==FALSE)
				wc_add_notice( $warning. " không hợp lệ, bạn chọn lại cho đúng theo tỉnh/thành nhé", 'error' );
		endforeach;
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
