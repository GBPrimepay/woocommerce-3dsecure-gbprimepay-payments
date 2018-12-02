<?php

function gbp_instances( $instances ) {
    $inc = array(
  	'3D_SECURE_PAYMENT' => TRUE,  // Enabling 3-D Secure payment(TRUE/FALSE).
                                  // Please be informed that you must contact GB Prime Pay support team before enable or disable this option.
                                  // (3-D Secure only available in Production Mode).

	  'URL_3D_SECURE_TEST' => 'https://api.globalprimepay.com/v1/tokens/3d_secured',
    'URL_3D_SECURE_LIVE' => 'https://api.gbprimepay.com/v1/tokens/3d_secured',

    'URL_API_TEST' => 'https://api.globalprimepay.com/v1/tokens',
    'URL_API_LIVE' => 'https://api.gbprimepay.com/v1/tokens',

    'URL_CHARGE_TEST' => 'https://api.globalprimepay.com/v1/tokens/charge',
    'URL_CHARGE_LIVE' => 'https://api.gbprimepay.com/v1/tokens/charge',

    'URL_QRCODE_TEST' => 'https://api.globalprimepay.com/gbp/gateway/qrcode',
    'URL_QRCODE_LIVE' => 'https://api.gbprimepay.com/gbp/gateway/qrcode',


    'URL_BARCODE_TEST' => 'https://api.globalprimepay.com/gbp/gateway/barcode',
    'URL_BARCODE_LIVE' => 'https://api.gbprimepay.com/gbp/gateway/barcode',

    'URL_CHECKPUBLICKEY_TEST' => 'https://api.globalprimepay.com/checkPublicKey',
    'URL_CHECKPUBLICKEY_LIVE' => 'https://api.gbprimepay.com/checkPublicKey',

    'URL_CHECKPRIVATEKEY_TEST' => 'https://api.globalprimepay.com/checkPrivateKey',
    'URL_CHECKPRIVATEKEY_LIVE' => 'https://api.gbprimepay.com/checkPrivateKey',

    'URL_CHECKCUSTOMERKEY_TEST' => 'https://api.globalprimepay.com/checkCustomerKey',
    'URL_CHECKCUSTOMERKEY_LIVE' => 'https://api.gbprimepay.com/checkCustomerKey',

);
$inc_code = isset( $inc[$instances] ) ? $inc[$instances] : $instances;
return $inc_code;
}
