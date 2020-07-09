<?php

class AS_Gateway_Gbprimepay_Installment extends WC_Payment_Gateway_CC
{
    public $environment;
    public $description2;

    public function __construct()
    {
        $this->id = 'gbprimepay_installment';
        $this->method_title = __('GBPrimePay Credit Card Installment', 'gbprimepay-payment-gateways-installment');
        $this->method_description = sprintf(__('Credit Card Installment integration with GBPrimePay'));
        $this->has_fields = true;

        $this->init_form_fields();

        // load settings
        $this->init_settings();


        $this->account_settings = get_option('gbprimepay_account_settings');
        $this->payment_settings = get_option('gbprimepay_payment_settings');
        $this->payment_settings_installment = get_option('gbprimepay_payment_settings_installment');

        $this->title = $this->payment_settings_installment['title'];
        $this->description2 = $this->payment_settings_installment['description2'];

        $this->environment = $this->account_settings['environment'];

        // AS_Gbprimepay_API::set_user_credentials($this->username, $this->password, $this->environment);
        update_option('gbprimepay_payment_settings_installment', $this->settings);
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        // add_action( 'init', array( $this, 'my_check_order_status' ) );
        add_action( 'init', array( $this, 'installment_callback_handler' ) );
        add_action( 'woocommerce_api_'. strtolower( get_class($this) ), array( $this, 'installment_callback_handler' ) );
    }

    public function init_form_fields()
    {


        $this->form_fields = include('settings-formfields-gbprimepay-installment.php');
    }

    /**
     * Check if this gateway is enabled
     */
    public function is_available()
    {
        if ($this->payment_settings_installment['enabled'] === 'yes') {
            return AS_Gbprimepay_API::get_credentials('installment');
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
            $pay_button_text = __('Add Card', 'gbprimepay-payment-gateways-installment');
            $total = '';
        } else {
            $pay_button_text = '';
        }
        $echocode = ''."\r\n";
        $echocode .= '<div style="padding:1.25em 0 0 0;margin-top:-1.25em;display:inline-block;"><img style="float: left;max-height: 2.8125em;" src="'.plugin_dir_url( __DIR__ ).'assets/images/installment.png'.'" alt=""></div>'."\r\n";
        $echocode .= ''."\r\n";
        echo $echocode;

        echo '<div
			id="gbprimepay-payment-data"
			data-panel-label="' . esc_attr($pay_button_text) . '"
			data-description="'. esc_attr($this->description2) .'"
			data-email="' . esc_attr($user_email) . '"
			data-amount="' . esc_attr($total) . '">';

        if ( $this->description2 ) {
            echo '<p>'.wpautop( wp_kses_post( $this->description2) ).'</p>';
        }

        $this->form();

        echo '</div>';
    }

    function process_payment( $order_id ) {
      global $woocommerce;
      $order = new WC_Order( $order_id );

      $order->add_order_note('Order created and status set to Pending payment.');
      $order->update_status('pending', __( 'Awaiting Credit Card Installment integration with GBPrimePay.', 'gbprimepay-payment-gateways' ));

      $redirect = add_query_arg(array('order_id' => $order->get_id(), 'key' => $order->get_order_key()), get_permalink(get_option('installment_post_id')));
      return array(
        'result' => 'success',
        'redirect' => $redirect
      );
    }


    public function payment_scripts()
    {
        if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order']) && !is_add_payment_method_page()) {
            return;
        }
        wp_enqueue_script('gbprimepay_installment', plugin_dir_url( __DIR__ ) .'assets/js/gbprimepay.js');

        wp_enqueue_script( 'gbprimepay_installment', 'https://sdk.paylike.io/3.js', '', '3.0', true );
    // wp_enqueue_script( 'gbprimepay-installment', 'https://code.jquery.com/jquery-3.4.1.slim.min.js', '', '', true );
    wp_enqueue_script( 'gbprimepay_installment', 'https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/crypto-js.min.js', '', '', true );
    wp_enqueue_script( 'gbprimepay_installment', 'https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/hmac-sha256.min.js', '', '', true );
    wp_enqueue_script(
      'gbprimepay_installment',
      plugin_dir_url( __DIR__ ) . 'assets/js/gbprimepay-installment-inc.js',
      '', '', true
    );
          }



  public function installment_callback_handler() {

    $raw_post = @file_get_contents( 'php://input' );
		$payload  = json_decode( $raw_post );
    $referenceNo = $payload->{'referenceNo'};
    $order_id = substr($payload->{'referenceNo'}, 7);

              $order = wc_get_order($order_id);
                if ( isset( $payload->{'resultCode'} ) ) {
                          if ($payload->{'resultCode'} == '00') {
                                  $order->payment_complete($payload->{'merchantDefined1'});
                                  update_post_meta($order_id, 'Gbprimepay Charge ID', $payload->{'merchantDefined1'});
                                  $order->add_order_note(__( 'GBPrimePay Credit Card Installment Payment Authorized.'));
                          }else{
                                  $order->update_status( 'failed', sprintf( __( 'GBPrimePay Credit Card Installment Payment failed.', 'gbprimepay-payment-gateways' ) ) );
                          }
                      AS_Gbprimepay::log(  'Credit Card Installment Callback Handler: ' . print_r( $payload, true ) );

                }

  }



  public function form()
  {


      wp_enqueue_script(
        'gbprimepay-installment',
        plugin_dir_url( __DIR__ ) . 'assets/js/gbprimepay-installment.js',
        '', '', true
      );

      echo '<input type="text" name="publicKey" value="dvypKqSKMYfEomBA4YLyaCrWTaRrCEyN">
      <input type="text" name="referenceNo" value="1611600101">
      <input type="text" name="responseUrl" value="https://wooseven.beprovider.net/checkout/order-received/101/?key=wc_order_Um4qyADj4N86C
     ">
      <input type="text" name="backgroundUrl" value="https://wooseven.beprovider.net/wc-api/AS_Gateway_Gbprimepay/">
      <input type="text" name="detail" placeholder="Detail" value="Charge for order 101"><br/>
      <input type="number" name="amount" maxlength="13" placeholder="Amount" value="11074.50"><br/>
      <input type="text" name="bankCode" maxlength="3" placeholder="Bank Code" value="014"><br/>
      <input type="number" name="term" maxlength="2" placeholder="The number of monthly installments" value="6"><br/>
      <input type="text" name="checksum" placeholder="checksum" value="{checksum}"><br/>
      <input id="button" type="button" onClick="genChecksum()" value="Generate Checksum">';

      echo '<select id="CCInstallmentToSelect" data-bankCode="#bankCode" data-term="#term">
            <option value=""></option>
            <optgroup label="TextValue[\'Kasikornbank Public Company Limited.\',\'004\']">
                    <option value="3">3 months</option>
                    <option value="4">4 months</option>
                    <option value="5">5 months</option>
                    <option value="6">6 months</option>
                    <option value="7">7 months</option>
                    <option value="8">8 months</option>
                    <option value="9">9 months</option>
                    <option value="10">10 months</option>
                </optgroup>
                <optgroup label="TextValue[\'Krung Thai Bank Public Company Limited.\',\'006\']">
                        <option value="3">3 months</option>
                        <option value="4">4 months</option>
                        <option value="5">5 months</option>
                        <option value="6">6 months</option>
                        <option value="7">7 months</option>
                        <option value="8">8 months</option>
                        <option value="9">9 months</option>
                        <option value="10">10 months</option>
                </optgroup>
                <optgroup label="TextValue[\'Thanachart Bank Public Company Limited.\',\'065\']">
                    <option value="3">3 months</option>
                    <option value="4">4 months</option>
                    <option value="6">6 months</option>
                    <option value="9">9 months</option>
                    <option value="10">10 months</option>
                </optgroup>
                <optgroup label="TextValue[\'Bank of Ayudhya Public Company Limited.\',\'025\']">
                    <option value="3">3 months</option>
                    <option value="4">4 months</option>
                    <option value="6">6 months</option>
                    <option value="9">9 months</option>
                    <option value="10">10 months</option>
                </optgroup>
                <optgroup label="TextValue[\'Krungsri First Choice.\',26]">
                    <option value="3">3 months</option>
                    <option value="4">4 months</option>
                    <option value="6">6 months</option>
                    <option value="9">9 months</option>
                    <option value="10">10 months</option>
                    <option value="12">12 months</option>
                    <option value="18">18 months</option>
                    <option value="24">24 months</option>
                </optgroup>
                <optgroup label="TextValue[\'Siam Commercial Bank Public Company Limited.\',\'014\']">
                    <option value="3">3 months</option>
                    <option value="4">4 months</option>
                    <option value="6">6 months</option>
                    <option value="10">10 months</option>
                </optgroup>
        </select>
        <label>Issuers Bank</label>
        <select id="bankCode" name="bankCode" class="form-control">
            <option value="" data-keep="true">Card issuer bank..</option>
        </select>

        <label>Terms</label>
        <select id="term" name="term" class="form-control">
            <option value="" data-keep="true">The number of monthly installments..</option>
        </select>

      <br><br><span id="info" name="info" class="form-control"></span><br><br>';






















      $fields = array();

      $cvc_field = '<p class="form-row form-row-last">
    <label for="' . esc_attr($this->id) . '-card-cvc">' . esc_html__('Card code', 'woocommerce') . ' <span class="required">*</span></label>
    <input id="' . esc_attr($this->id) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" name="' . esc_attr($this->id) . '-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__('CVC', 'woocommerce') . '" ' . $this->field_name('card-cvc') . ' style="width:100px" />
  </p>';

      $default_fields = array(
          'card-number-field' => '<p class="form-row form-row-wide">
      <label for="' . esc_attr($this->id) . '-card-number">' . esc_html__('Card number', 'woocommerce') . ' <span class="required">*</span></label>
      <input id="' . esc_attr($this->id) . '-card-number" name="' . esc_attr($this->id) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name('card-number') . ' />
    </p>',
          'card-expiry-field' => '<p class="form-row form-row-first">
      <label for="' . esc_attr($this->id) . '-card-expiry">' . esc_html__('Expiry (MM/YY)', 'woocommerce') . ' <span class="required">*</span></label>
      <input id="' . esc_attr($this->id) . '-card-expiry" name="' . esc_attr($this->id) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="' . esc_attr__('MM / YY', 'woocommerce') . '" ' . $this->field_name('card-expiry') . ' />
    </p>',
      );

      if (!$this->supports('credit_card_form_cvc_on_saved_method')) {
          $default_fields['card-cvc-field'] = $cvc_field;
      }

      $default_fields['card-token'] = '<input id="' . esc_attr($this->id) . '-card-token" style="display: none;" class="input-text"' . $this->field_name('card-token') . ' />';

      $fields = wp_parse_args($fields, apply_filters('woocommerce_credit_card_form_fields', $default_fields, $this->id));
      ?>

      <fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
          <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>
          <?php
          foreach ($fields as $field) {
              echo $field;
          }
          ?>
          <?php do_action('woocommerce_credit_card_form_end', $this->id); ?>
          <div class="clear"></div>
      </fieldset>
      <?php

      if ($this->supports('credit_card_form_cvc_on_saved_method')) {
          echo '<fieldset>' . $cvc_field . '</fieldset>';
      }



  }
    public function send_failed_order_email($order_id)
    {
        $emails = WC()->mailer()->get_emails();
        if (!empty($emails) && !empty($order_id)) {
            $emails['WC_Email_Failed_Order']->trigger($order_id);
        }
    }
}
