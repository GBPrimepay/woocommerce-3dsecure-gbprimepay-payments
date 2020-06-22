<?php

class AS_Gateway_Gbprimepay_Barcode extends WC_Payment_Gateway_eCheck
{
    public $environment;
    public $description3;

    public function __construct()
    {
        $this->id = 'gbprimepay_barcode';
        $this->method_title = __('GBPrimePay Bill Payment', 'gbprimepay-payment-gateways-barcode');
        $this->method_description = sprintf(__('Bill Payment integration with GBPrimePay'));
        $this->has_fields = true;

        $this->init_form_fields();

        // load settings
        $this->init_settings();

        $this->account_settings = get_option('gbprimepay_account_settings');
        $this->payment_settings = get_option('gbprimepay_payment_settings');
        $this->payment_settings_barcode = get_option('gbprimepay_payment_settings_barcode');

        $this->title = $this->payment_settings_barcode['title'];
        $this->description3 = $this->payment_settings_barcode['description3'];

        $this->environment = $this->account_settings['environment'];

        // AS_Gbprimepay_API::set_user_credentials($this->username, $this->password, $this->environment);
        update_option('gbprimepay_payment_settings_barcode', $this->settings);
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action( 'init', array( $this, 'my_check_order_status' ) );
        add_action( 'woocommerce_api_'. strtolower( get_class($this) ), array( $this, 'barcode_callback_handler' ) );
        add_action( 'init', 'barcode_callback_handler' );
    }

    public function init_form_fields()
    {
        $this->form_fields = include('settings-formfields-gbprimepay-barcode.php');
    }

    /**
     * Check if this gateway is enabled
     */
    public function is_available()
    {
        if ($this->payment_settings_barcode['enabled'] === 'yes') {
            return AS_Gbprimepay_API::get_credentials('barcode');
        }
        return false;
    }

    public function payment_fields()
    {
        $user = wp_get_current_user();
        $total = WC()->cart->total;

        // if paying from order, we need to get total from order not cart.
        if (isset($_GET['pay_for_order']) && !empty($_GET['key'])) {
            $order = wc_get_order(wc_get_order_id_by_order_key(wc_clean($_GET['key'])));
            $total = $order->get_total();
        }

        if ($user->ID) {
            $user_email = get_user_meta($user->ID, 'billing_email', true);
            $user_email = $user_email ? $user_email : $user->user_email;
        } else {
            $user_email = '';
        }

        if (is_add_payment_method_page()) {
            $pay_button_text = __('Add Card', 'gbprimepay-payment-gateways-barcode');
            $total = '';
        } else {
            $pay_button_text = '';
        }

        echo '<div
			id="gbprimepay-payment-data"
			data-panel-label="' . esc_attr($pay_button_text) . '"
			data-description="'. esc_attr($this->description3) .'"
			data-email="' . esc_attr($user_email) . '"
			data-amount="' . esc_attr($total) . '">';

        if ( $this->description3 ) {
            echo '<p>'.wpautop( wp_kses_post( $this->description3) ).'</p>';
        }

        echo '</div>';
    }

    function process_payment( $order_id ) {
      global $woocommerce;
      $order = new WC_Order( $order_id );

      $order->add_order_note('Order created and status set to Pending payment.');
      $order->update_status('pending', __( 'Awaiting Bill Payment integration with GBPrimePay.', 'gbprimepay-payment-gateways' ));

      $redirect = add_query_arg(array('order_id' => $order->get_id(), 'key' => $order->get_order_key()), get_permalink(get_option('barcode_post_id')));
      return array(
        'result' => 'success',
        'redirect' => $redirect
      );
    }



    public function request_payment($order_id) {


      $order = wc_get_order($order_id);

      $callgetMerchantId = AS_Gbprimepay_API::getMerchantId();
      $callgenerateID = AS_Gbprimepay_API::generateID();


      $amount = 1.10;
      $itemamount = number_format((($amount * 100)/100), 2, '.', '');
      $itemdetail = 'Charge for order ' . $order->get_order_number();
      // $itemReferenceId = ''.substr(time(), 4, 5).'00'.$order->get_order_number();
      $itemReferenceId = '00000'.$order->get_order_number();
      $itemcustomerEmail = $order->get_billing_email();
      $customer_full_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();



      $gbprimepayUser = new AS_Gbprimepay_User_Account(get_current_user_id(), $order); // get gbprimepay user obj
      $getgbprimepay_customer_id = $gbprimepayUser->get_gbprimepay_user_id();

      $account_settings = get_option('gbprimepay_account_settings');

      $return_url_barcode = $this->get_return_url($order);

      if($account_settings['environment']=='production'){
        $url = gbp_instances('URL_BARCODE_LIVE');
        $itemtoken = $account_settings['live_token_key'];
      }else{
        $url = gbp_instances('URL_BARCODE_TEST');
        $itemtoken = $account_settings['test_token_key'];
      }

      $itemresponseurl = $this->get_return_url($order);
      $itembackgroundurl = home_url()."/" . 'wc-api/AS_Gateway_Gbprimepay_Barcode/';
      $itemcustomerEmail = $order->get_billing_email();


      $field = "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"token\"\r\n\r\n$itemtoken\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"amount\"\r\n\r\n$itemamount\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"referenceNo\"\r\n\r\n$itemReferenceId\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"payType\"\r\n\r\nF\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"responseUrl\"\r\n\r\n$itemresponseurl\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"backgroundUrl\"\r\n\r\n$itembackgroundurl\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"detail\"\r\n\r\n$itemdetail\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"customerName\"\r\n\r\n$customer_full_name\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"customerEmail\"\r\n\r\n$itemcustomerEmail\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"merchantDefined1\"\r\n\r\n$callgenerateID\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"merchantDefined2\"\r\n\r\n$getgbprimepay_customer_id\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"merchantDefined3\"\r\n\r\n$itemReferenceId\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"merchantDefined4\"\r\n\r\n\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"merchantDefined5\"\r\n\r\n\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--";


      AS_Gbprimepay::log(  'generatebarcode Request: ' . print_r( $field, true ) );

      $barcodeResponse = AS_Gbprimepay_API::sendBARCurl("$url", $field, 'POST');



        if ($barcodeResponse=="Incomplete information") {
        }else{

          wp_enqueue_script(
            'gbprimepay-barcode-ajax-script',
            plugin_dir_url( __FILE__ ) . '../assets/js/gbprimepay-barcode-ajax.js',
            array('jquery')
          );

          wp_localize_script(
            'gbprimepay-barcode-ajax-script',
            'barcode_ajax_obj',
            array('ajaxurl' => admin_url('admin-ajax.php'))
          );



echo '<style>';
echo '.barcode_display {';
echo 'overflow:hidden;';
echo 'height:100%;';
echo '}';
echo '.barcode_display object{';
echo 'height: auto;';
echo '}';
echo '</style>';




              echo '<input type="hidden" id="gbprimepay-barcode-order-id" value="' . $order_id . '">';
              echo '<div class="barcode_display" id="gbprimepay-barcode-waiting-payment" style="display:block;">';
              echo '<object width="100%" height="100%" data="' . $barcodeResponse . '" type="application/pdf" class="internal" style="min-height: 730px;"><embed src="' . $barcodeResponse . '" type="application/pdf" style="min-height: 730px;"/></object>';
              echo '<br><br><br><br></div>';
              echo '<div class="barcode_display" id="gbprimepay-barcode-payment-successful" style="display:none;">';
              echo $this->display_payment_success_message($return_url_barcode);
              echo '</div>';


        }
    }

 	public function display_payment_success_message($return_url_barcode) {
 		return "
 			<center>
        <br><br>
        <img src='" . plugins_url('/gbprimepay-payments-gateways/assets/images/checked.png') . "'  style='padding:0px 0px 0px 0px;windth:100%;'>
 				<h3>GBPrimePay Bill Payment Payment Successful!</h3>
 				<img src='" . plugins_url('/gbprimepay-payments-gateways/assets/images/gbprimepay-logo-pay.png') . "' style='padding:0px 0px 0px 0px;windth:100%;'>
 				<br><br><br>
 				Pay with Bill Payment Payment has been received and \"Order is Now Complete\".
 				<br><br><br>
 				Redirecting...
 				<br><br><br><br><br><br>
 				<script>function redirect_to_shop() { window.location.href = '" . $return_url_barcode . "'; }</script>
 			</center>";
 	}
  public function barcode_callback_handler() {

      $raw_post = @file_get_contents( 'php://input' );
      $payload  = json_decode( $raw_post );
      $referenceNo = $payload->{'referenceNo'};
      $order_id = substr($payload->{'referenceNo'}, 5);

                $order = wc_get_order($order_id);
                  if ( isset( $payload->{'resultCode'} ) ) {
                            if ($payload->{'resultCode'} == '00') {
                                    $order->payment_complete($payload->{'id'});
                                    update_post_meta($order_id, 'Gbprimepay Charge ID', $payload->{'id'});
                                    $order->add_order_note(__( 'GBPrimePay Bill Payment Authorized.'));
                            }else{
                                    $order->update_status( 'failed', sprintf( __( 'GBPrimePay Bill Payment failed.', 'gbprimepay-payment-gateways' ) ) );
                            }
                    $account_settings = get_option('gbprimepay_account_settings');
                    if ($account_settings['logging'] === 'yes') {
                        AS_Gbprimepay::log(  'Bill Payment Callback Handler: ');
                    }

                  }

    }

    public function send_failed_order_email($order_id)
    {
        $emails = WC()->mailer()->get_emails();
        if (!empty($emails) && !empty($order_id)) {
            $emails['WC_Email_Failed_Order']->trigger($order_id);
        }
    }

    public function log( $message ) {
        $options = get_option('gbprimepay_payment_settings');

        if ( 'yes' === $options['logging'] ) {
            AS_Gbprimepay::log($message);
        }
    }
}
