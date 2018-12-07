<?php



/*
 * Plugin Name: GBPrimePay Payments
 * Description: 3-D Secure Payment Gateway By GBPrimePay
 * Author: GBPrimePay
 * Author URI: https://www.gbprimepay.com
 * Version: 1.8.0
 * Text Domain: gbprimepay-payments-gateways
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
define( 'AS_GBPRIMEPAY_VERSION', '1.8.0' );
if (!class_exists('AS_Gbprimepay')) {
    class AS_Gbprimepay
    {
        private static $instance;

        private static $log;

        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        protected function __construct()
        {
            add_action( 'admin_init', array( $this, 'check_environment' ) );
            add_action('plugins_loaded', array($this, 'init'));
            add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );





            add_action( 'wp_ajax_check_qrcode_order_status', array( $this, 'my_check_qrcode_order_status' ) );
            add_action( 'wp_ajax_nopriv_check_qrcode_order_status', array( $this, 'my_check_qrcode_order_status' ) );



            add_action( 'init', array( $this, 'create_gbprimepay_qrcode_post_type' ) );
            add_action( 'init', array( $this, 'create_gbprimepay_qrcode_payment_page' ) );
            add_filter( 'template_include', array($this, 'gbprimepay_qrcode_page_template'));



            add_action( 'wp_ajax_check_barcode_order_status', array( $this, 'my_check_barcode_order_status' ) );
            add_action( 'wp_ajax_nopriv_check_barcode_order_status', array( $this, 'my_check_barcode_order_status' ) );



            add_action( 'init', array( $this, 'create_gbprimepay_barcode_post_type' ) );
            add_action( 'init', array( $this, 'create_gbprimepay_barcode_payment_page' ) );
            add_filter( 'template_include', array($this, 'gbprimepay_barcode_page_template'));



            $this->account_settings = get_option('gbprimepay_account_settings');
            $this->payment_settings = get_option('gbprimepay_payment_settings');
            $this->payment_settings_qrcode = get_option('gbprimepay_payment_settings_qrcode');
            $this->payment_settings_barcode = get_option('gbprimepay_payment_settings_barcode');
        }

        public function init() {
            $path = plugin_dir_path(__FILE__);
            include_once(dirname(__FILE__) . '/includes/class-as-gbprimepay-api.php');
            include_once(dirname(__FILE__) . '/includes/customer/class-as-gbprimepay-user-account.php');
            include_once(dirname(__FILE__) . '/includes/include-code/instances.php');

            $this->init_gateway();

            add_action( 'woocommerce_payment_token_deleted', array( $this, 'woocommerce_payment_token_deleted' ), 10, 2 );
        }
        public function add_plugin_page(){
          add_menu_page(
              'GBPrimePay Account Settings',
              'GBPrimePay',
              'manage_options',
              'wc-settings&tab=gbprimepay_settings',
              array($this, 'gbprimepay_account_settings'), 'data:image/svg+xml;base64,'.$this->gbprimepay_svg()
          );
        }
        public function gbprimepay_account_settings(){
        }
        // QR Code
        public function my_check_qrcode_order_status($order_id) {


        	// $gateway = new AS_Gateway_Gbprimepay_Qrcode;


        	$order_id = $_POST['order_id'];
          // $order_id = 685;


        	$order = wc_get_order($order_id);
        	$order_data = $order->get_data();

        	// If order is "completed" or "processing", we can give confirmation that payment has gone through
        	if($order_data['status'] == 'completed' || $order_data['status'] == 'processing')
        	{
        		echo 1; // Payment completed
        	} elseif ($order_data['status'] == 'pending') {
        		echo 0; // Payment not completed
        	}

        	// Always end AJAX-printing scripts with die();
        	die();
        }
        public function create_gbprimepay_qrcode_post_type() {
        	register_post_type('gbprimepay_qrcode',
        		array(
        			'labels' => array(
        				'name' => __('GBPrimePay QR Code'),
        				'singular_name' => __('GBPrimePay QR Code')
        			),
        			'public' => true,
        			'has_archive' => false,
        			'publicly_queryable' => true,
        			'exclude_from_search' => true,
        			'show_in_menu' => false,
        			'show_in_nav_menus' => false,
        			'show_in_admin_bar' => false,
        			'show_in_rest' => false,
        			'hierarchical' => false,
        			'supports' => array('title'),
        		)
        	);
        	flush_rewrite_rules();
        }
        public function create_gbprimepay_qrcode_payment_page() {

        	global $wpdb;

        	// Get the ID of our custom payments page from settings
        	$qrcode_post_id = get_option('qrcode_post_id');

        	// Create a custom GUID (URL) for our custom for our payments page
        	$guid = home_url('/gbprimepay_qrcode/pay');


        	if ($qrcode_post_id && get_post_type($qrcode_post_id) == "gbprimepay_qrcode" && get_the_guid($qrcode_post_id) == $guid) {
        		// Post already created, so return
        		return;
        	} else {
        		// Put together data to create the custom post
        		$page_data = array(
        			'post_status' => 'publish',
        			'post_type' => 'gbprimepay_qrcode',
        			'post_title' => 'pay',
        			'post_content' => 'GBPrimePay QR Code',
        			'comment_status' => 'closed',
        			'guid' => $guid,
        		);

        		// Create the post
        		$qrcode_post_id = wp_insert_post($page_data);

        		// Update our settings with the ID of the newly created post
        		$ppp = update_option('qrcode_post_id', $qrcode_post_id);
        	}
        }
        public function gbprimepay_qrcode_page_template($page_template) {

        	if (get_post_type() && get_post_type() === 'gbprimepay_qrcode') {

        		return dirname(__FILE__) . '/templates/gbprimepay-gateway-qrcode.php';
        	}

        	return $page_template;
        }




        // Bill Payment
        public function my_check_barcode_order_status($order_id) {


        	// $gateway = new AS_Gateway_Gbprimepay_Barcode;


        	$order_id = $_POST['order_id'];
          // $order_id = 685;


        	$order = wc_get_order($order_id);
        	$order_data = $order->get_data();

        	// If order is "completed" or "processing", we can give confirmation that payment has gone through
        	if($order_data['status'] == 'completed' || $order_data['status'] == 'processing')
        	{
        		echo 1; // Payment completed
        	} elseif ($order_data['status'] == 'pending') {
        		echo 0; // Payment not completed
        	}

        	// Always end AJAX-printing scripts with die();
        	die();
        }


        public function create_gbprimepay_barcode_post_type() {
        	register_post_type('gbprimepay_barcode',
        		array(
        			'labels' => array(
        				'name' => __('GBPrimePay Bill Payment'),
        				'singular_name' => __('GBPrimePay Bill Payment')
        			),
        			'public' => true,
        			'has_archive' => false,
        			'publicly_queryable' => true,
        			'exclude_from_search' => true,
        			'show_in_menu' => false,
        			'show_in_nav_menus' => false,
        			'show_in_admin_bar' => false,
        			'show_in_rest' => false,
        			'hierarchical' => false,
        			'supports' => array('title'),
        		)
        	);
        	flush_rewrite_rules();
        }
        public function create_gbprimepay_barcode_payment_page() {

        	global $wpdb;

        	// Get the ID of our custom payments page from settings
        	$barcode_post_id = get_option('barcode_post_id');

        	// Create a custom GUID (URL) for our custom for our payments page
        	$guid = home_url('/gbprimepay_barcode/pay');


        	if ($barcode_post_id && get_post_type($barcode_post_id) == "gbprimepay_barcode" && get_the_guid($barcode_post_id) == $guid) {
        		// Post already created, so return
        		return;
        	} else {
        		// Put together data to create the custom post
        		$page_data = array(
        			'post_status' => 'publish',
        			'post_type' => 'gbprimepay_barcode',
        			'post_title' => 'pay',
        			'post_content' => 'GBPrimePay Bill Payment',
        			'comment_status' => 'closed',
        			'guid' => $guid,
        		);

        		// Create the post
        		$barcode_post_id = wp_insert_post($page_data);

        		// Update our settings with the ID of the newly created post
        		$ppp = update_option('barcode_post_id', $barcode_post_id);
        	}
        }
        public function gbprimepay_barcode_page_template($page_template) {

        	if (get_post_type() && get_post_type() === 'gbprimepay_barcode') {

        		return dirname(__FILE__) . '/templates/gbprimepay-gateway-barcode.php';
        	}

        	return $page_template;
        }
        protected function gbprimepay_svg()
        {
            return base64_encode('<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="-279 369 55 55" style="enable-background:new -279 369 55 55;" xml:space="preserve"><style type="text/css">.st0{fill:#004071}.st1{fill:#FFF}</style><g id="Layer_2_1_"> </g> <g id="Layer_1"> </g> <g id="Layer_3"> <g> <path class="st0" d="M-226.6,395.2v-16.7c0-2.7-2.2-4.9-4.9-4.9h-40.1c-2.7,0-4.9,2.2-4.9,4.9v16.7 C-276.4,395.2-226.6,395.2-226.6,395.2z"/> <path class="st1" d="M-276.4,395.1v19.4c0,2.7,2.2,4.9,4.9,4.9h40.1c2.7,0,4.9-2.2,4.9-4.9v-19.4H-276.4z"/> <g> <path class="st1" d="M-240,391.2c0-0.3-0.2-0.6-0.6-0.6h-2.6c-0.3,0-0.6,0.2-0.6,0.6v2.6c0,0.3,0.2,0.6,0.6,0.6h2.6 c0.3,0,0.6-0.2,0.6-0.6V391.2z"/> <path class="st1" d="M-236.9,383.4c0-0.4-0.3-0.7-0.7-0.7h-3.5c-0.4,0-0.7,0.3-0.7,0.7v3.5c0,0.4,0.3,0.7,0.7,0.7h3.5 c0.4,0,0.7-0.3,0.7-0.7V383.4z"/> <path class="st1" d="M-236.4,376c0-0.3-0.2-0.6-0.6-0.6h-2.6c-0.3,0-0.6,0.2-0.6,0.6v2.6c0,0.3,0.2,0.6,0.6,0.6h2.6 c0.3,0,0.6-0.2,0.6-0.6V376L-236.4,376z"/> <path class="st1" d="M-229.1,387.4c0-0.3-0.2-0.6-0.6-0.6h-2.6c-0.3,0-0.6,0.2-0.6,0.6v2.6c0,0.3,0.2,0.6,0.6,0.6h2.6 c0.3,0,0.6-0.2,0.6-0.6V387.4z"/> <path class="st1" d="M-235.6,389.7c0-0.2-0.2-0.4-0.4-0.4h-1.9c-0.2,0-0.4,0.2-0.4,0.4v1.9c0,0.2,0.2,0.4,0.4,0.4h1.9 c0.2,0,0.4-0.2,0.4-0.4V389.7z"/> <path class="st1" d="M-232.8,380.3c0-0.2-0.1-0.3-0.3-0.3h-1.4c-0.2,0-0.3,0.1-0.3,0.3v1.4c0,0.2,0.1,0.3,0.3,0.3h1.4 c0.2,0,0.3-0.1,0.3-0.3V380.3z"/> <path class="st1" d="M-241.8,377.9c0-0.2-0.1-0.3-0.3-0.3h-1.4c-0.2,0-0.3,0.1-0.3,0.3v1.4c0,0.2,0.1,0.3,0.3,0.3h1.4 c0.2,0,0.3-0.1,0.3-0.3V377.9z"/> <path class="st1" d="M-229,376.6c0-0.2-0.1-0.3-0.3-0.3h-1.4c-0.2,0-0.3,0.1-0.3,0.3v1.4c0,0.2,0.1,0.3,0.3,0.3h1.4 c0.2,0,0.3-0.1,0.3-0.3V376.6z"/> </g> <g> <path class="st1" d="M-261,381.1h2.5c1.1,0,1.9,0.2,2.5,0.6c0.9,0.6,1.3,1.3,1.3,2.3c0,0.9-0.4,1.6-1.2,2.1 c1.2,0.5,1.8,1.4,1.8,2.7c0,1.1-0.4,1.9-1.3,2.5c-0.4,0.3-0.9,0.4-1.4,0.5c-0.3,0-0.8,0.1-1.5,0.1h-2.5V381.1z M-258.8,385.7 c0.5,0,0.9,0,1,0c0.8-0.1,1.3-0.4,1.6-0.9c0.2-0.3,0.2-0.6,0.2-0.9c0-0.6-0.3-1.1-0.9-1.5c-0.4-0.2-1-0.3-2-0.3h-1.3v3.7H-258.8z M-258.6,390.9c0.6,0,1,0,1.2,0c0.9-0.1,1.5-0.4,1.9-1c0.2-0.3,0.3-0.7,0.3-1c0-1-0.4-1.6-1.3-1.9c-0.4-0.1-1.1-0.2-1.9-0.2h-1.4 v4.2H-258.6L-258.6,390.9z"/> <path class="st1" d="M-263.2,386.9h-6.8v1h5.6c0.2,0,0.4,0.1,0.4,0.4c-0.3,0.8-0.8,1.3-1.6,1.8c-0.8,0.6-1.7,0.9-2.7,0.9 c-1.3,0-2.4-0.4-3.4-1.3c-0.9-0.9-1.4-2-1.4-3.3c0-1.3,0.5-2.4,1.5-3.3c0.9-0.8,2-1.2,3.2-1.2c0.8,0,1.5,0.2,2.2,0.5 c0.7,0.4,1.3,0.9,1.7,1.5h1.2c-0.3-0.8-0.9-1.5-1.9-2.1c-0.9-0.6-2-0.9-3.2-0.9c-1.6,0-3,0.6-4.1,1.7c-1.1,1.1-1.7,2.4-1.7,3.9 c0,1.6,0.6,2.9,1.7,4c1.1,1.1,2.5,1.6,4.1,1.6c1.5,0,2.8-0.5,3.9-1.4c1.1-0.9,1.6-1.9,1.8-3.3 C-262.8,387.1-262.9,386.9-263.2,386.9z"/> <path class="st1" d="M-264.6,384c0,0,0.4,0.8,0.9,0.7c0.6-0.1,0.4-0.6,0.3-0.9L-264.6,384z"/> </g> <g> <path class="st0" d="M-272.3,397.8c0.4,0,0.6,0.3,0.6,0.6v3.1c0,0.4-0.3,0.6-0.6,0.6h-3.1c-0.4,0-0.6-0.3-0.6-0.6v-3.1 c0-0.4,0.3-0.6,0.6-0.6L-272.3,397.8L-272.3,397.8z"/> <path class="st0" d="M-266.8,402.9c0.3,0,0.5,0.2,0.5,0.5v2.5c0,0.3-0.2,0.5-0.5,0.5h-2.5c-0.3,0-0.5-0.2-0.5-0.5v-2.5 c0-0.3,0.2-0.5,0.5-0.5H-266.8z"/> <path class="st0" d="M-272.5,405.9c0.2,0,0.4,0.2,0.4,0.4v1.9c0,0.2-0.2,0.4-0.4,0.4h-1.9c-0.2,0-0.4-0.2-0.4-0.4v-1.9 c0-0.2,0.2-0.4,0.4-0.4H-272.5z"/> <path class="st0" d="M-267.7,410c0.2,0,0.4,0.2,0.4,0.4v1.9c0,0.2-0.2,0.4-0.4,0.4h-1.9c-0.2,0-0.4-0.2-0.4-0.4v-1.9 c0-0.2,0.2-0.4,0.4-0.4H-267.7z"/> <path class="st0" d="M-272.1,413.4c0.1,0,0.2,0.1,0.2,0.2v1.2c0,0.1-0.1,0.2-0.2,0.2h-1.2c-0.1,0-0.2-0.1-0.2-0.2v-1.2 c0-0.1,0.1-0.2,0.2-0.2H-272.1z"/> <path class="st0" d="M-267.6,398.1c0.1,0,0.2,0.1,0.2,0.2v1.2c0,0.1-0.1,0.2-0.2,0.2h-1.2c-0.1,0-0.2-0.1-0.2-0.2v-1.2 c0-0.1,0.1-0.2,0.2-0.2H-267.6z"/> </g> <g> <path class="st0" d="M-263.8,399.1h3.6c1.6,0,2.9,0.3,3.7,0.8c1.2,0.7,1.8,1.9,1.8,3.4c0,1-0.3,1.9-0.9,2.6 c-0.6,0.7-1.4,1.1-2.4,1.3c-0.5,0.1-1.1,0.2-1.9,0.2h-2.6v5.6h-1.4V399.1z M-260,406.1c0.7,0,1.1,0,1.4-0.1 c0.5-0.1,0.9-0.2,1.3-0.5c0.8-0.5,1.2-1.3,1.2-2.3c0-1.2-0.6-2.1-1.7-2.6c-0.5-0.2-1.2-0.3-2.1-0.3c-0.1,0-0.2,0-0.4,0 c-0.2,0-0.3,0-0.4,0h-1.8v5.7L-260,406.1L-260,406.1z"/> <path class="st0" d="M-248,399.1h1.5l5.9,13.8h-1.5l-2-4.6h-6.4l-2,4.6h-1.5L-248,399.1z M-244.6,407l-2.7-6.3l-2.7,6.3H-244.6z" /> <path class="st0" d="M-235.5,408.3l-4.8-9.2h1.5l4,7.7l4-7.7h1.5l-4.8,9.2v4.6h-1.4L-235.5,408.3L-235.5,408.3z"/> </g> </g> </g> </svg>');
        }

        public function check_environment() {
            if ( ! defined( 'ifRAME_REQUEST' ) && ( AS_GBPRIMEPAY_VERSION !== get_option( 'as_gbprimepay_version' ) ) ) {
                $this->install();

                do_action( 'woocommerce_gbprimepay_updated' );
            }
        }
        private static function _update_plugin_version() {
            delete_option( 'as_gbprimepay_version' );
            update_option( 'as_gbprimepay_version', AS_GBPRIMEPAY_VERSION );

            return true;
        }
        public function install() {
            if ( ! defined( 'AS_GBPRIMEPAY_INSTALLING' ) ) {
                define( 'AS_GBPRIMEPAY_INSTALLING', true );
            }

            $this->_update_plugin_version();
        }

        public function woocommerce_payment_token_deleted($token_id, $token)
        {
            if ( 'gbprimepay' === $token->get_gateway_id() ) {
                $gbprimepay_api_obj = new AS_Gbprimepay_API();
                $gbprimepay_api_obj->deleteCardAccount($token->get_token());
            }
        }

        public function init_gateway() {
            if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
                return;
            }

            include_once(dirname(__FILE__) . '/includes/class-as-gbprimepay-account.php');
            include_once(dirname(__FILE__) . '/includes/class-as-gbprimepay-gateway.php');
            include_once(dirname(__FILE__) . '/includes/class-as-gbprimepay-gateway-qrcode.php');
            include_once(dirname(__FILE__) . '/includes/class-as-gbprimepay-gateway-barcode.php');
            add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
        }

        public function add_gateways($methods) {
            $methods[] = 'AS_Gateway_Gbprimepay';
            $methods[] = 'AS_Gateway_Gbprimepay_Qrcode';
            $methods[] = 'AS_Gateway_Gbprimepay_Barcode';

            return $methods;
        }

        public static function log( $message ) {
            if ( empty( self::$log ) ) {
                self::$log = new WC_Logger();
            }
            self::$log->add( 'gbprimepay-payment-gateways', $message );
        }
    }

    $GLOBALS['as_gbprimepay'] = AS_Gbprimepay::get_instance();
}
