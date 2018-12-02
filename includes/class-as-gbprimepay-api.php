<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class AS_Gbprimepay_API {
    private static $username = '';
    private static $user_password = '';
    private static $testmode = true;
    private static $getCardArray;

    public static function set_user_credentials($username, $user_password, $testmode) {
        self::$username = $username;
        self::$user_password = $user_password;
        self::$testmode = $testmode;
    }

    public static function get_user_credentials_if_not_exist() {
        $options = get_option('gbprimepay_payment_settings');

        if ((!self::$username || !self::$user_password) && $options) {
            self::$username = $options['username'];
            self::$user_password = $options['password'];
            self::$testmode = 'yes' === $options['testmode'];
        }

    }

    public function __construct()
    {
        // self::get_user_credentials_if_not_exist();
    }



        public static function get_credentials($paytype)
        {

          $payment_settings = get_option('gbprimepay_payment_settings');
          $payment_settings_qrcode = get_option('gbprimepay_payment_settings_qrcode');
          $payment_settings_barcode = get_option('gbprimepay_payment_settings_barcode');
          if ($paytype === 'direct') {
              if ($payment_settings['enabled'] === 'yes') {
                $check_credentials = self::check_credentials();
              }
          }
          if ($paytype === 'qrcode') {
              if ($payment_settings_qrcode['enabled'] === 'yes') {
                $check_credentials = self::check_credentials();
              }
          }
          if ($paytype === 'barcode') {
              if ($payment_settings_barcode['enabled'] === 'yes') {
                $check_credentials = self::check_credentials();
              }
          }
          return $check_credentials;


      }


      public static function check_credentials()
      {

        $account_settings = get_option('gbprimepay_account_settings');
        $payment_settings = get_option('gbprimepay_payment_settings');
        $payment_settings_qrcode = get_option('gbprimepay_payment_settings_qrcode');
        $payment_settings_barcode = get_option('gbprimepay_payment_settings_barcode');

          if ($account_settings['environment'] === 'prelive') {
              $url = gbp_instances('URL_CHECKPUBLICKEY_TEST');
          } else {
              $url = gbp_instances('URL_CHECKPUBLICKEY_LIVE');
          }
          $callback = AS_Gbprimepay_API::sendPublicCurl("$url", [], 'GET');

              if (!empty($callback['merchantId']) && !empty($callback['initialShop']) && !empty($callback['merchantName'])) {
                      if ($account_settings['environment'] === 'prelive') {
                          $url = gbp_instances('URL_CHECKPRIVATEKEY_TEST');
                      } else {
                          $url = gbp_instances('URL_CHECKPRIVATEKEY_LIVE');
                      }
                      $callback = AS_Gbprimepay_API::sendPrivateCurl("$url", [], 'GET');
                          if (!empty($callback['merchantId']) && !empty($callback['initialShop']) && !empty($callback['merchantName'])) {
                            if ($account_settings['environment'] === 'prelive') {
                                $url = gbp_instances('URL_CHECKCUSTOMERKEY_TEST');
                            } else {
                                $url = gbp_instances('URL_CHECKCUSTOMERKEY_LIVE');
                            }
                            $callback = AS_Gbprimepay_API::sendTokenCurl("$url", [], 'POST');
                                if (!empty($callback['merchantId']) && !empty($callback['initialShop']) && !empty($callback['merchantName'])) {
                                        return true;
                                }else{
                                  return false;
                                }
                          }else{
                            return false;
                          }
              }else{
                return false;
              }

          return false;

    }

    public static function check_is_available()
    {

    $account_settings = get_option('gbprimepay_account_settings');
    $payment_settings = get_option('gbprimepay_payment_settings');
    $payment_settings_qrcode = get_option('gbprimepay_payment_settings_qrcode');
    $payment_settings_barcode = get_option('gbprimepay_payment_settings_barcode');



    if ($payment_settings['enabled'] === 'yes') {

    if ($account_settings['environment'] === 'prelive') {
        $url = gbp_instances('URL_CHECKPUBLICKEY_TEST');
    } else {
        $url = gbp_instances('URL_CHECKPUBLICKEY_LIVE');
    }
    $callback = AS_Gbprimepay_API::sendPublicCurl("$url", [], 'GET');

        if (!empty($callback['merchantId']) && !empty($callback['initialShop']) && !empty($callback['merchantName'])) {
                if ($account_settings['environment'] === 'prelive') {
                    $url = gbp_instances('URL_CHECKPRIVATEKEY_TEST');
                } else {
                    $url = gbp_instances('URL_CHECKPRIVATEKEY_LIVE');
                }
                $callback = AS_Gbprimepay_API::sendPrivateCurl("$url", [], 'GET');
                    if (!empty($callback['merchantId']) && !empty($callback['initialShop']) && !empty($callback['merchantName'])) {
                      if ($account_settings['environment'] === 'prelive') {
                          $url = gbp_instances('URL_CHECKCUSTOMERKEY_TEST');
                      } else {
                          $url = gbp_instances('URL_CHECKCUSTOMERKEY_LIVE');
                      }
                      $callback = AS_Gbprimepay_API::sendTokenCurl("$url", [], 'POST');
                          if (!empty($callback['merchantId']) && !empty($callback['initialShop']) && !empty($callback['merchantName'])) {
                                  return 0;
                          }else{
                            return 3;
                          }
                    }else{
                      return 3;
                    }
        }else{
          return 3;
        }

    return 2;
    }else{
    return 3;
    }
    }

    public static function encode($string,$key)
    {
      $key = sha1($key);
      $strLen = strlen($string);
      $keyLen = strlen($key);
      $j = 0;
      $hash = '';
          for ($i = 0; $i < $strLen; $i++) {
              $ordStr = ord(substr($string,$i,1));
              if ($j == $keyLen) { $j = 0; }
              $ordKey = ord(substr($key,$j,1));
              $j++;
              $hash .= strrev(base_convert(dechex($ordStr + $ordKey),16,36));
          }
      return $hash;
    }
    public static function generateID()
      {
        $microtime = md5(microtime());
        $encoded = self::encode($microtime , "GBPrimePay");
        $serial = implode('-', str_split(substr(strtolower($encoded), 0, 32), 5));
        return $serial;
      }
    public static function getMerchantId()
    {

      $account_settings = get_option('gbprimepay_account_settings');

        if ($account_settings['environment'] === 'prelive') {
            $url = gbp_instances('URL_CHECKPUBLICKEY_TEST');
            $configkey = $account_settings['test_public_key'];
        } else {
            $url = gbp_instances('URL_CHECKPUBLICKEY_LIVE');
            $configkey = $account_settings['live_public_key'];
        }

        if (empty($configkey)) {
            return false;
        }

        $field = [];
        $type = 'GET';

        $key = base64_encode("{$configkey}".":");
        $ch = curl_init($url);


        $request_headers = array(
            "Accept: application/json",
            "Authorization: Basic {$key}",
            "Cache-Control: no-cache",
            "Content-Type: application/json",
        );





        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

        $body = curl_exec($ch);


        $json = json_decode($body, true);
        if (isset($json['error'])) {
            return false;
        }

        curl_close($ch);
        return $json['merchantId'];
    }









        public static function sendPublicCurl($url, $field, $type)
        {
            $account_settings = get_option('gbprimepay_account_settings');

            if ($account_settings['environment'] === 'prelive') {
                $configkey = $account_settings['test_public_key'];
            } else {
                $configkey = $account_settings['live_public_key'];
            }

            if (empty($configkey)) {
                return false;
            }



            $key = base64_encode("{$configkey}".":");
            $ch = curl_init($url);


            $request_headers = array(
                "Accept: application/json",
                "Authorization: Basic {$key}",
                "Cache-Control: no-cache",
                "Content-Type: application/json",
            );





            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

            $body = curl_exec($ch);


            $json = json_decode($body, true);
            if (isset($json['error'])) {
                return false;
            }

            curl_close($ch);
            return json_decode($body, true);
        }


        public static function sendPrivateCurl($url, $field, $type)
        {
            $account_settings = get_option('gbprimepay_account_settings');
            if ($account_settings['environment'] === 'prelive') {
                $configkey = $account_settings['test_secret_key'];
            } else {
                $configkey = $account_settings['live_secret_key'];
            }

            if (empty($configkey)) {
                return false;
            }



            $key = base64_encode("{$configkey}".":");
            $ch = curl_init($url);

            $request_headers = array(
                "Accept: application/json",
                "Authorization: Basic {$key}",
                "Cache-Control: no-cache",
                "Content-Type: application/json",
            );





            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

            $body = curl_exec($ch);

            $json = json_decode($body, true);
            if (isset($json['error'])) {
                return false;
            }

            curl_close($ch);
            return json_decode($body, true);
        }
        public static function sendTokenCurl($url, $field, $type)
        {
            $account_settings = get_option('gbprimepay_account_settings');
            if ($account_settings['environment'] === 'prelive') {
                $configkey = $account_settings['test_token_key'];
            } else {
                $configkey = $account_settings['live_token_key'];
            }


            if (empty($configkey)) {
                return false;
            }

            $ch = curl_init($url);

            $request_headers = array(
                "Accept: application/json",
                "Cache-Control: no-cache",
                "Content-Type: application/x-www-form-urlencoded",
            );


            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "token=".urlencode($configkey));

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

            $body = curl_exec($ch);

            $json = json_decode($body, true);
            if (isset($json['error'])) {
                return false;
            }

            curl_close($ch);
            return json_decode($body, true);
        }


        public static function sendAPICurl($url, $field, $type)
        {
            $account_settings = get_option('gbprimepay_account_settings');
            if ($account_settings['environment'] === 'prelive') {
                $configkey = $account_settings['test_public_key'];
            } else {
                $configkey = $account_settings['live_public_key'];
            }

            if (empty($configkey)) {
                return false;
            }

            $key = base64_encode("{$configkey}".":");
            $ch = curl_init($url);


            $request_headers = array(
                "Accept: application/json",
                "Authorization: Basic {$key}",
                "Cache-Control: no-cache",
                "Content-Type: application/json",
            );


            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $field);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

            $body = curl_exec($ch);

            $json = json_decode($body, true);
            if (isset($json['error'])) {
                return false;
            }

            curl_close($ch);
            return json_decode($body, true);
        }

        public static function sendCHARGECurl($url, $field, $type)
        {
            $account_settings = get_option('gbprimepay_account_settings');
            if ($account_settings['environment'] === 'prelive') {
                $configkey = $account_settings['test_secret_key'];
            } else {
                $configkey = $account_settings['live_secret_key'];
            }

            if (empty($configkey)) {
                return false;
            }

            $key = base64_encode("{$configkey}".":");
            $ch = curl_init($url);


            $request_headers = array(
                "Accept: application/json",
                "Authorization: Basic {$key}",
                "Cache-Control: no-cache",
                "Content-Type: application/json",
            );


            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $field);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

            $body = curl_exec($ch);

            $json = json_decode($body, true);
            if (isset($json['error'])) {
                return false;
            }

            curl_close($ch);
            return json_decode($body, true);
        }

        public static function sendQRCurl($url, $field, $type)
        {
            $account_settings = get_option('gbprimepay_account_settings');
            if ($account_settings['environment'] === 'prelive') {
                $configkey = $account_settings['test_public_key'];
            } else {
                $configkey = $account_settings['live_public_key'];
            }

            if (empty($configkey)) {
                return false;
            }

            $ch = curl_init($url);


            $request_headers = array(
                "Cache-Control: no-cache",
                "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
            );


            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $field);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

            $body = curl_exec($ch);

            if ($body=="Incomplete information") {
              $body = 'error : Incomplete information';
            }else{
              // $body = ob_start();'\n<img src="data:image/png;base64,' . base64_encode($body) . '">';
              $body = 'data:image/png;base64,' . base64_encode($body) . '';
            }




            curl_close($ch);
            return $body;
        }
        public static function sendBARCurl($url, $field, $type)
        {
            $account_settings = get_option('gbprimepay_account_settings');
            if ($account_settings['environment'] === 'prelive') {
                $configkey = $account_settings['test_public_key'];
            } else {
                $configkey = $account_settings['live_public_key'];
            }

            if (empty($configkey)) {
                return false;
            }

            $ch = curl_init($url);


            $request_headers = array(
                "Cache-Control: no-cache",
                "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW"
            );


            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $field);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

            $body = curl_exec($ch);

            if ($body=="Incomplete information") {
              $body = 'error : Incomplete information';
            }else{
              // $body = ob_start();'\n<img src="data:image/png;base64,' . base64_encode($body) . '">';
              $body = 'data:application/pdf;base64,' . base64_encode($body) . '';
            }




            curl_close($ch);
            return $body;
        }



















    /**
     * ============ CARD ACCOUNT METHODS =============
     */
    public static function createCardAccount($body) {
        try {

                $customer_rememberCard = $body['is_save'];
                $cc_number = $body['number'];
                $customer_cc_exp_month = $body['expiry_month'];
                $customer_cc_exp_year = $body['expiry_year'];
                $cc_cid = $body['cvv'];
                $customer_full_name = $body['full_name'];
                $getgbprimepay_customer_id = $body['user_id'];

                $account_settings = get_option('gbprimepay_account_settings');

                if ($account_settings['environment'] === 'prelive') {
                      $url = gbp_instances('URL_API_TEST');
                } else {
                      $url = gbp_instances('URL_API_LIVE');
                }




                $iniactive = 0;
                  if((isset($customer_cc_exp_month)) && (isset($customer_cc_exp_year))){







                $field = "{\r\n\"rememberCard\": $customer_rememberCard,\r\n\"card\": {\r\n\"number\": \"$cc_number\",\r\n\"expirationMonth\": \"$customer_cc_exp_month\",\r\n\"expirationYear\": \"$customer_cc_exp_year\",\r\n\"securityCode\": \"$cc_cid\",\r\n\"name\": \"$customer_full_name\"\r\n}\r\n}";


                $callback = AS_Gbprimepay_API::sendAPICurl("$url", $field, 'POST');

                if ($callback['resultCode']=="54") {

                }else if ($callback['resultCode']=="02") {

                }else if ($callback['resultCode']=="00") {
                      $token_id = $callback['card']['token'];
                      $iniactive = 1;
                }
              }
                if($iniactive==1 && !empty($token_id)){

                $currentdate = date('Y-m-d H:i');

                $response = array(
                    'active' => true,
                    'created_at' => $currentdate,
                    'updated_at' => $currentdate,
                    'id' => $token_id,
                    'id_customer' => $getgbprimepay_customer_id,
                    'links' => array(
                                    'self' => "/card_accounts/$token_id",
                                    'users' => "/card_accounts/$token_id/users"
                                ),
                    'card' => $callback['card'],
                );


                self::$getCardArray = $response;

            AS_Gbprimepay::log(  'createCardAccount Response: ' . print_r( $response, true ) );
          }

            if ($response) return $response;
            else {
                throw new Exception(__('Something went wrong while creating card account.'));
            }
        } catch (Exception $e) {
            wc_add_notice( $e->getMessage(), 'error' );

            AS_Gbprimepay::log(  'createCardAccount error Response: ' . print_r( $e->getMessage(), true ) );

            return;
        }
    }
    public static function getCardAccount($id) {
        try {

            $loadCard = self::$getCardArray;
            $response = array(
                'active' => $loadCard['active'],
                'created_at' => $loadCard['created_at'],
                'updated_at' => $loadCard['updated_at'],
                'id' => $loadCard['id'],
                'card' => array(
                                'type' => 'Visa',
                                'full_name' => 'full name',
                                'number' => $loadCard['card']['number'],
                                'expiry_month' => $loadCard['card']['expirationMonth'],
                                'expiry_year' => $loadCard['card']['expirationYear']
                            ),
                'links' => array(
                                'self' => '/card_accounts/'.$loadCard['id'],
                                'users' => '/card_accounts/'.$loadCard['id'].'/users'
                            ),
            );

            AS_Gbprimepay::log(  'getCardAccount Response: ' . print_r( $response, true ) );

            if ($response) return $response;
            else {
                throw new Exception(__('Something went wrong while get card account.'));
            }
        } catch (Exception $e) {
            wc_add_notice( $e->getMessage(), 'error' );

            AS_Gbprimepay::log(  'getCardAccount error Response: ' . print_r( $e->getMessage(), true ) );

            return;
        }
    }

    public static function deleteCardAccount($cardId) {
        try {

            $response = array(
                'card_account' => 'Successfully redacted',
            );
            AS_Gbprimepay::log(  'deleteCardAccount Response: ' . print_r( $response, true ) );
            if ($response) return $response;
            else {
                throw new Exception(__('Something went wrong while deleting card account.'));
            }
        } catch (Exception $e) {
//            wc_add_notice( $e->getMessage(), 'error' );
            AS_Gbprimepay::log(  'deleteCardAccount error Response: ' . print_r( $e->getMessage(), true ) );

            return;
        }
    }
    /**
     * ============ END OF CARD ACCOUNT METHODS =============
     */
    /**
     * ============ CHARGE METHODS =============
     */

    /**
     * @param $accountId
     * @param WC_Order $order
     */
    public static function createCharge($accountId, $order)
    {
        try {



          $callgetMerchantId = self::getMerchantId();
          $callgenerateID = self::generateID();


          $amount = $order->get_total();
          $itemamount = number_format((($amount * 100)/100), 2, '.', '');
          $itemdetail = 'Charge for order ' . $order->get_order_number();
          // $itemReferenceId = ''.substr(time(), 4, 5).'00'.$order->get_order_number();
          $itemReferenceId = '00000'.$order->get_order_number();
          $itemcustomerEmail = $order->get_billing_email();
          $customer_full_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
          $gbprimepayCardId = $accountId;
          $otpCode = 'N';

          $gbprimepayUser = new AS_Gbprimepay_User_Account(get_current_user_id(), $order); // get gbprimepay user obj
          $getgbprimepay_customer_id = $gbprimepayUser->get_gbprimepay_user_id();

          $account_settings = get_option('gbprimepay_account_settings');



          if($account_settings['environment']=='production'){
                  if(gbp_instances('3D_SECURE_PAYMENT')==TRUE){

                        // 3-D Secure Payment


                  }else{

                        // GBPrimePay Payment
                        $url = gbp_instances('URL_CHARGE_LIVE');
                        $otpCode = 'N';
                        $field = "{\r\n\"amount\": $itemamount,\r\n\"referenceNo\": \"$itemReferenceId\",\r\n\"detail\": \"$itemdetail\",\r\n\"customerName\": \"$customer_full_name\",\r\n\"customerEmail\": \"$itemcustomerEmail\",\r\n\"merchantDefined1\": \"$callgenerateID\",\r\n\"merchantDefined2\": null,\r\n\"merchantDefined3\": null,\r\n\"merchantDefined4\": null,\r\n\"merchantDefined5\": null,\r\n\"card\": {\r\n\"token\": \"$gbprimepayCardId\"\r\n},\r\n\"otp\": \"$otpCode\"\r\n}\r\n";

                  }
          }else{

                        // GBPrimePay Payment
                        $url = gbp_instances('URL_CHARGE_TEST');
                        $otpCode = 'N';
                        $field = "{\r\n\"amount\": $itemamount,\r\n\"referenceNo\": \"$itemReferenceId\",\r\n\"detail\": \"$itemdetail\",\r\n\"customerName\": \"$customer_full_name\",\r\n\"customerEmail\": \"$itemcustomerEmail\",\r\n\"merchantDefined1\": \"$callgenerateID\",\r\n\"merchantDefined2\": null,\r\n\"merchantDefined3\": null,\r\n\"merchantDefined4\": null,\r\n\"merchantDefined5\": null,\r\n\"card\": {\r\n\"token\": \"$gbprimepayCardId\"\r\n},\r\n\"otp\": \"$otpCode\"\r\n}\r\n";

          }

          $callback = AS_Gbprimepay_API::sendCHARGECurl("$url", $field, 'POST');


          $gbpReferenceNo_action = isset($callback['gbpReferenceNo']) ? $callback['gbpReferenceNo'] : '';
          if($gbpReferenceNo_action==true){
            $callbackgbpReferenceNo = $callback['gbpReferenceNo'];
          }else{
            $callbackgbpReferenceNo = '';
          }

              $chargeResponse = array(
                  "id" => $callgenerateID,
                  "tokenreference" => $gbprimepayCardId,
                  "resultCode" => $callback['resultCode'],
                  "amount" => $itemamount,
                  "referenceNo" => $itemReferenceId,
                  "gbpReferenceNo" => $callbackgbpReferenceNo,
                  "detail" => $itemdetail,
                  "customerName" => $customer_full_name,
                  "customerEmail" => $itemcustomerEmail,
                  "merchantDefined1" => $callgenerateID,
                  "merchantDefined2" => null,
                  "merchantDefined3" => null,
                  "merchantDefined4" => null,
                  "merchantDefined5" => null,
                   "related" => array(
                                  "self" => "$getgbprimepay_customer_id",
                                  "buyers" => "$callgetMerchantId",
                               ),
                   "links" => array(
                                   "self" => "/charges/$callgenerateID",
                                   "buyers" => "/charges/$callgenerateID/buyers",
                                   "sellers" => "/charges/$callgenerateID/sellers",
                                   "status" => "/charges/$callgenerateID/status",
                                   "fees" => "/charges/$callgenerateID/fees",
                                   "transactions" => "/charges/$callgenerateID/transactions",
                                   "batch_transactions" => "/charges/$callgenerateID/batch_transactions",
                                 ),
              );


            if (!$chargeResponse || !array_key_exists('id', $chargeResponse)) {
                throw new Exception(__('Cannot create charge.'));
            }

                AS_Gbprimepay::log(  'createCharge Request: ' . print_r( $chargeResponse, true ) );

            return $chargeResponse;
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            AS_Gbprimepay::log(  'createCharge error Response: ' . print_r( $e->getMessage(), true ) );

            return;
        }
    }










        /**
         * @param $accountId
         * @param WC_Order $order
         */
        public static function createSecureCharge($accountId, $order)
        {
            try {



              $callgetMerchantId = self::getMerchantId();
              $callgenerateID = self::generateID();


              $amount = $order->get_total();
              $itemamount = number_format((($amount * 100)/100), 2, '.', '');
              $itemdetail = 'Charge for order ' . $order->get_order_number();
              // $itemReferenceId = ''.substr(time(), 4, 5).'00'.$order->get_order_number();
              $itemReferenceId = '00000'.$order->get_order_number();
              $itemcustomerEmail = $order->get_billing_email();
              $customer_full_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
              $gbprimepayCardId = $accountId;
              $otpCode = 'N';

              $gbprimepayUser = new AS_Gbprimepay_User_Account(get_current_user_id(), $order); // get gbprimepay user obj
              $getgbprimepay_customer_id = $gbprimepayUser->get_gbprimepay_user_id();

              $account_settings = get_option('gbprimepay_account_settings');



              if($account_settings['environment']=='production'){
                      if(gbp_instances('3D_SECURE_PAYMENT')==TRUE){

                            // 3-D Secure Payment
                            $url = gbp_instances('URL_CHARGE_LIVE');
                            $otpCode = 'Y';

                            $gbprimepay_otpurl = WC()->session->get('gbprimepay_otpurl');

                            $otpResponseUrl = $gbprimepay_otpurl['ResponseUrl'];
                            $otpBackgroundUrl = $gbprimepay_otpurl['BackgroundUrl'];

                            $field = "{\r\n\"amount\": $itemamount,\r\n\"referenceNo\": \"$itemReferenceId\",\r\n\"detail\": \"$itemdetail\",\r\n\"customerName\": \"$customer_full_name\",\r\n\"customerEmail\": \"$itemcustomerEmail\",\r\n\"merchantDefined1\": \"$callgenerateID\",\r\n\"merchantDefined2\": null,\r\n\"merchantDefined3\": null,\r\n\"merchantDefined4\": null,\r\n\"merchantDefined5\": null,\r\n\"card\": {\r\n\"token\": \"$gbprimepayCardId\"\r\n},\r\n\"otp\": \"$otpCode\",\r\n\"responseUrl\": \"$otpResponseUrl\",\r\n\"backgroundUrl\": \"$otpBackgroundUrl\"\r\n}\r\n";


                      }else{

                            // GBPrimePay Payment

                      }
              }else{

                            // GBPrimePay Payment

              }

              $callback = AS_Gbprimepay_API::sendCHARGECurl("$url", $field, 'POST');



              if ($callback['resultCode']=="00") {
                if (!empty($callback['gbpReferenceNo']) && ($otpCode == 'Y')) {

                      $account_settings = get_option('gbprimepay_account_settings');

                      $otp_url = gbp_instances('URL_3D_SECURE_LIVE');
                      $otp_publicKey = $account_settings['live_public_key'];
                      $otp_gbpReferenceNo = $callback['gbpReferenceNo'];

                      $RedirectURL =  add_query_arg(
                                      array(
                                          'page' => rawurlencode($otp_url),
                                          'publicKey' => rawurlencode($otp_publicKey),
                                          'gbpReferenceNo' => rawurlencode($otp_gbpReferenceNo)
                                      ), WP_PLUGIN_URL."/" . plugin_basename( dirname(__FILE__) ) . '/redirect/secure.php');


              }
              }


    $gbpReferenceNo_action = isset($callback['gbpReferenceNo']) ? $callback['gbpReferenceNo'] : '';
    if($gbpReferenceNo_action==true){
      $callbackgbpReferenceNo = $callback['gbpReferenceNo'];
    }else{
      $callbackgbpReferenceNo = '';
    }




                  $chargeResponse = array(
                      "id" => $callgenerateID,
                      "tokenreference" => $gbprimepayCardId,
                      "resultCode" => $callback['resultCode'],
                      "amount" => $itemamount,
                      "referenceNo" => $itemReferenceId,
                      "gbpReferenceNo" => $callbackgbpReferenceNo,
                      "detail" => $itemdetail,
                      "customerName" => $customer_full_name,
                      "customerEmail" => $itemcustomerEmail,
                      "merchantDefined1" => $callgenerateID,
                      "merchantDefined2" => null,
                      "merchantDefined3" => null,
                      "merchantDefined4" => null,
                      "merchantDefined5" => null,
                       "related" => array(
                                      "self" => "$getgbprimepay_customer_id",
                                      "buyers" => "$callgetMerchantId",
                                   ),
                       "links" => array(
                                       "self" => "/charges/$callgenerateID",
                                       "buyers" => "/charges/$callgenerateID/buyers",
                                       "sellers" => "/charges/$callgenerateID/sellers",
                                       "status" => "/charges/$callgenerateID/status",
                                       "fees" => "/charges/$callgenerateID/fees",
                                       "transactions" => "/charges/$callgenerateID/transactions",
                                       "batch_transactions" => "/charges/$callgenerateID/batch_transactions",
                                     ),
                  );




                  WC()->session->set('gbprimepay_otpcharge', $chargeResponse);

                  $waitResponse = array(
                        "RedirectURL" => $RedirectURL,
                  );


                if (!$chargeResponse || !array_key_exists('id', $chargeResponse)) {
                    throw new Exception(__('Cannot create secure charge.'));
                }

                    AS_Gbprimepay::log(  'createSecureCharge Request: ' . print_r( $chargeResponse, true ) );

                // return $chargeResponse;
                return $waitResponse;
            } catch (Exception $e) {
                wc_add_notice($e->getMessage(), 'error');
                AS_Gbprimepay::log(  'createSecureCharge error Response: ' . print_r( $e->getMessage(), true ) );

                return;
            }
        }





    /**
     * ============ END OF CHARGE METHODS =============
     */

    /**
     * ============ USER METHODS =============
     */

    /**
     * @param WC_Customer $wpUser
     * @param int $gbprimepayUserId
     * @return mixed
     */
    public static function createUser($wpUser, $gbprimepayUserId)
    {
        if (!$wpUser->get_billing_country()) {
            throw new Exception(__('Please set your billing country first.'));
        }
        try {

            $response = array(
                'id' => $gbprimepayUserId,
                'email' => $wpUser->get_email(),
                'first_name' => $wpUser->get_first_name(),
                'last_name' => $wpUser->get_last_name(),
                'mobile' => $wpUser->get_billing_phone(),
                'address_line_1' => $wpUser->get_billing_address_1(),
                'state' => $wpUser->get_billing_state(),
                'city' => $wpUser->get_billing_city(),
                'zip' => $wpUser->get_billing_postcode()
            );

            AS_Gbprimepay::log(  'createUser Request: ' . print_r( $response, true ) );

            return $response;
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            AS_Gbprimepay::log(  'createUser error Request: ' . print_r( $e->getMessage(), true ) );

            return;
        }
    }

    /**
     * @param int $gbprimepayUserId
     * @param WC_ORder $order
     */
    public static function createUserWithOrder($gbprimepayUserId, $order)
    {
        try {

            $response = array(
                'id' => $gbprimepayUserId,
                'email' => $order->get_billing_email(),
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'mobile' => $order->get_billing_phone(),
                'address_line_1' => $order->get_billing_address_1(),
                'state' => $order->get_billing_state(),
                'city' => $order->get_billing_city(),
                'zip' => $order->get_billing_postcode()
            );

            AS_Gbprimepay::log(  'createUserWithOrder Request: ' . print_r( $response, true ) );

            return $response;
        } catch (Exception $e) {
            echo $e->getMessage();
            AS_Gbprimepay::log(  'createUserWithOrder error Request: ' . print_r( $e->getMessage(), true ) );

            die();
        }
    }

    public static function getUserFromGbprimepay($gbprimepay_user_id)
    {
        try {

            global $wpdb;

            $wp_user_id = $wpdb->get_var( $wpdb->prepare( "
                SELECT user_id
                FROM $wpdb->usermeta
                WHERE meta_key = '_gbprimepay_user_id'
                AND meta_value = '%s'
            ", $gbprimepay_user_id ) );

            if ( ! $wp_user_id ) {
                return;
            }

            $wpUser = new WC_Customer( $wp_user_id );

            if ( ! $wpUser ) {
                return;
            }




            AS_Gbprimepay::log(  'gbprimepay_user_id Request: ' . print_r( $gbprimepay_user_id, true ) );

            $response = array(
                'id' => $gbprimepay_user_id,
                'email' => $wpUser->get_email(),
                'first_name' => $wpUser->get_first_name(),
                'last_name' => $wpUser->get_last_name(),
                'mobile' => $wpUser->get_billing_phone(),
                'address_line_1' => $wpUser->get_billing_address_1(),
                'state' => $wpUser->get_billing_state(),
                'city' => $wpUser->get_billing_city(),
                'zip' => $wpUser->get_billing_postcode()
            );

            AS_Gbprimepay::log(  'getUserFromGbprimepay Request: ' . print_r( $response, true ) );

            if (array_key_exists('id', $response)) {
                return $response;
            } else throw new Exception(__('User not exist on Gbprimepay server.'));
        } catch(Exception $e) {
            echo $e->getMessage();
            die();
        }
    }
    /**
     * ============ END OF USER METHODS =============
     */
}
