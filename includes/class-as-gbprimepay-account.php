<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! class_exists( 'AS_Gbprimepay_ACCOUNT' ) ) :


function gbprimepay_settings() {

	class AS_Gbprimepay_ACCOUNT extends WC_Settings_Page {


		public function __construct() {

			$this->id    = 'gbprimepay_settings';
			$this->label = __( 'GBPrimePay Settings', 'gbprimepay-payment-gateways' );

			add_filter( 'woocommerce_settings_tabs_array',        array( $this, 'add_settings_page' ), 20 );
			add_action( 'woocommerce_settings_' . $this->id,      array( $this, 'output' ) );
			add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'woocommerce_sections_' . $this->id,      array( $this, 'output_sections' ) );

      self::gbprimepay_load_start();

		}



		public function get_sections() {

			$sections = array(
				''         => __( 'setting', 'gbprimepay-payment-gateways' )
			);

			return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
		}


		public function get_settings( $current_section = '' ) {

				$settings = apply_filters( 'as_gbprimepay_account_settings', array(

          'section_title' => array(
              'name'     => __( '', 'woocommerce-gbprimepay-settings' ),
              'type'     => 'title',
              'desc'     => 'GBPrimePay Account Settings<hr>',
              'id'       => 'gbprimepay_account_settings_section'
          ),

          'environment'         => array(
            'title'       => __( 'Environment', 'gbprimepay-payment-gateways' ),
            'type'        => 'select',
            'class'       => 'wc-enhanced-select',
            'desc_tip' => __( 'Set The Test Mode or Production Mode', 'gbprimepay-payment-gateways' ),
            'default'     => 'prelive',
            'options'     => array(
              'prelive'          => __( 'Test Mode', 'gbprimepay-payment-gateways' ),
              'production' => __( 'Production Mode', 'gbprimepay-payment-gateways' ),
            ),
            'id'   => 'gbprimepay_account_settings[environment]'
          ),
          'live_public_key' => array(
              'title'       => __( 'Production Public Key', 'gbprimepay-payment-gateways' ),
              'type'        => 'text',
              'desc_tip' => __( 'Get your Public Key credentials from GB Prime Pay.', 'gbprimepay-payment-gateways' ),
              'default'     => __( '', 'gbprimepay-payment-gateways' ),
              'id'   => 'gbprimepay_account_settings[live_public_key]'
          ),
          'live_secret_key' => array(
              'title'       => __( 'Production Secret Key', 'gbprimepay-payment-gateways' ),
              'type'        => 'text',
              'desc_tip' => __( 'Get your Secret Key credentials from GB Prime Pay.', 'gbprimepay-payment-gateways' ),
              'default'     => __( '', 'gbprimepay-payment-gateways' ),
              'id'   => 'gbprimepay_account_settings[live_secret_key]'
          ),
          'live_token_key'     => array(
            'title'       => __( 'Production Token', 'gbprimepay-payment-gateways' ),
            'type'        => 'textarea',
            'css'         => 'width:90%;',
            'desc_tip' => __( 'Get your Token Key credentials from GB Prime Pay.', 'gbprimepay-payment-gateways' ),
            'default'     => __( '', 'gbprimepay-payment-gateways' ),
            'id'   => 'gbprimepay_account_settings[live_token_key]'
          ),
          'test_public_key' => array(
              'title'       => __( 'Test Public Key', 'gbprimepay-payment-gateways' ),
              'type'        => 'text',
              'desc_tip' => __( 'Get your Public Key credentials from GB Prime Pay.', 'gbprimepay-payment-gateways' ),
              'default'     => __( '', 'gbprimepay-payment-gateways' ),
              'id'   => 'gbprimepay_account_settings[test_public_key]'
          ),
          'test_secret_key' => array(
              'title'       => __( 'Test Secret Key', 'gbprimepay-payment-gateways' ),
              'type'        => 'text',
              'desc_tip' => __( 'Get your Secret Key credentials from GB Prime Pay.', 'gbprimepay-payment-gateways' ),
              'default'     => __( '', 'gbprimepay-payment-gateways' ),
              'id'   => 'gbprimepay_account_settings[test_secret_key]'
          ),
          'test_token_key'     => array(
            'title'       => __( 'Test Token', 'gbprimepay-payment-gateways' ),
            'type'        => 'textarea',
            'css'         => 'width:90%;',
            'desc_tip' => __( 'Get your Token Key credentials from GB Prime Pay.', 'gbprimepay-payment-gateways' ),
            'default'     => __( '', 'gbprimepay-payment-gateways' ),
            'id'   => 'gbprimepay_account_settings[test_token_key]'
          ),
          'Logging'     => array(
              'title'       => __( 'Logging', 'gbprimepay-payment-gateways' ),
              'desc'       => __( 'Enable debug logging', 'gbprimepay-payment-gateways' ),
              'type'        => 'checkbox',
              'default'     => 'no',
              'desc_tip'    => __( 'Save debug messages to the WooCommerce System Status log.', 'gbprimepay-payment-gateways' ),
              'id'   => 'gbprimepay_account_settings[logging]'
          ),

					'sectionend'     => array(
						'type' => 'sectionend',
						'id'   => 'gbprimepay_account_settings_section'
					),

				) );
			return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );

		}

		public function output() {

			global $current_section;

			$settings = $this->get_settings( $current_section );
      self::gbprimepay_top();
			WC_Admin_Settings::output_fields( $settings );
      self::availablemethods();
      self::gbprimepay_load_end();
		}
    public function notice_message($message) {

    $account_settings = get_option('gbprimepay_account_settings');

    if($account_settings['environment']=='prelive'){
      $echopaymode = sprintf(__('Test Mode'));
    }else{
      $echopaymode = sprintf(__('Production Mode'));
    }
        switch ($message) {
          case 0:
          ?>
          <div class="gbp_notice_message onlyloaded success notice notice-success is-dismissible" style="display:none;padding: 12px 12px;">
          <p><strong>Verified, GBPrimePay Payments Settings is already set in <?echo $echopaymode;?></strong></p>
          </div>
          <?
          break;
          case 2:
          break;
          case 3:
          ?>
          <div class="gbp_notice_message onlyloaded error notice notice-error is-dismissible" style="display:none;padding: 12px 12px;">
          <p><strong>Error!, Missing credentials in config in <?echo $echopaymode;?></strong></p>
          </div>
          <?
          break;
          default:
          break;
        }
  }

    public function gbprimepay_top() {
      ?>
      <img style="margin:15px 0px 0px -12px !important;" src="<?php echo plugins_url( '../assets/images/gbprimepay-logo.png', __FILE__ ); ?>" alt="gbprimepay.com">
      <h2>GBPrimePay Payments<small class="wc-admin-breadcrumb"><a href="admin.php?page=wc-settings&amp;tab=checkout" aria-label="Return to payments"><img draggable="false" class="emoji" alt="?" src="https://s.w.org/images/core/emoji/11/svg/2934.svg"></a></small></h2>
      <?
    }
    public function gbprimepay_load_start() {
        ?><script type="text/javascript">jQuery('.gbp_notice_message').hide();</script><?
    }
    public function gbprimepay_load_end() {
      ?><script type="text/javascript">setTimeout(function(){jQuery('.gbp_notice_message').show();}, 700);</script><?
    }
    public function availablemethods() {

        $account_settings = get_option('gbprimepay_account_settings');
        $payment_settings = get_option('gbprimepay_payment_settings');
        $payment_settings_qrcode = get_option('gbprimepay_payment_settings_qrcode');
        $payment_settings_barcode = get_option('gbprimepay_payment_settings_barcode');
        if(gbp_instances('3D_SECURE_PAYMENT')==TRUE){
            if($account_settings['environment']=='prelive'){
              $ccintegration = sprintf(__('3-D Secure Credit Card Payment Gateway with GBPrimePay (3-D Secure only available in Production Mode)'));
            }else{
              $ccintegration = sprintf(__('3-D Secure Credit Card Payment Gateway with GBPrimePay'));
            }
        }else{
          $ccintegration = sprintf(__('Credit Card integration with GBPrimePay'));
        }

        if($account_settings['environment']=='prelive'){
          $echopaymode = sprintf(__('Test Mode'));
        }else{
          $echopaymode = sprintf(__('Production Mode'));
        }


        if ($payment_settings['enabled'] === 'yes') {
          $echoenabledpayment = '<span class="woocommerce-input-toggle woocommerce-input-toggle--enabled">Yes</span>';
        }else{
          $echoenabledpayment = '<span class="woocommerce-input-toggle woocommerce-input-toggle--disabled">No</span>';

        }
        if ($payment_settings_qrcode['enabled'] === 'yes') {
          $echoenabledpayment_qrcode = '<span class="woocommerce-input-toggle woocommerce-input-toggle--enabled">Yes</span>';
        }else{
          $echoenabledpayment_qrcode = '<span class="woocommerce-input-toggle woocommerce-input-toggle--disabled">No</span>';

        }
        if ($payment_settings_barcode['enabled'] === 'yes') {
          $echoenabledpayment_barcode = '<span class="woocommerce-input-toggle woocommerce-input-toggle--enabled">Yes</span>';
        }else{
          $echoenabledpayment_barcode = '<span class="woocommerce-input-toggle woocommerce-input-toggle--disabled">No</span>';

        }

?>
<div class="wrap">
<hr><h3>Payment Methods (<?echo $echopaymode;?>)</h3>
     <table class="widefat fixed striped" cellspacing="0" style="width:60%;min-width:640px;">
							<thead>
								<tr>
									<th style="padding: 10px;width:50%;" class="name">Payment Method</th>
                  <th style="text-align: center; padding: 10px;" class="status">Status</th>
                  <th style="text-align: center; padding: 10px;" class="setting"></th>
                </tr>
							</thead>
							<tbody>
								<tr>
                  <td class="name" style="padding-left: 30px;">
													<span id="span-for-active-button"><?echo $ccintegration;?></span>
												</td>
                  <td class="status" style="text-align: center;">
                    <?echo $echoenabledpayment;?>
                  </td><td class="setting" style="text-align: center;">
													<a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=gbprimepay">Configuration</a>
									</td>
                </tr>
                <tr>
                  <td class="name" style="padding-left: 30px;">
													<span id="span-for-active-button">QR Code integration with GBPrimePay</span>
												</td>
                  <td class="status" style="text-align: center;">
                    <?echo $echoenabledpayment_qrcode;?>
                  </td><td class="setting" style="text-align: center;">
													<a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=gbprimepay_qrcode">Configuration</a>
									</td>
                </tr>
                <tr>
                  <td class="name" style="padding-left: 30px;">
													<span id="span-for-active-button">Bill Payment integration with GBPrimePay</span>
												</td>
                  <td class="status" style="text-align: center;">
                    <?echo $echoenabledpayment_barcode;?>
                  </td><td class="setting" style="text-align: center;">
													<a href="admin.php?page=wc-settings&amp;tab=checkout&amp;section=gbprimepay_barcode">Configuration</a>
									</td>
                </tr>

              </tbody>
						</table>
    <br>
    <hr>
   </div>
<?php
}

		public function save() {

			global $current_section;

			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::save_fields( $settings );
      $checked_check_is_available = AS_Gbprimepay_API::check_is_available();
      self::notice_message($checked_check_is_available);
		}

	}

	return new AS_Gbprimepay_ACCOUNT();

}
add_filter( 'woocommerce_get_settings_pages', 'gbprimepay_settings', 15 );

endif;
