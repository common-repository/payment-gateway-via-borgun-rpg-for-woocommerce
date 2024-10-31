<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Gateway_Borgun_RPG extends WC_Payment_Gateway_CC {

  /**
   * Whether or not logging is enabled
   *
   * @var bool
   */
  public static $log_enabled = false;

  /**
   * Logger instance
   *
   * @var WC_Logger
   */
  public static $log = false;

  /**
   * Gateway testmode
   *
   * @var string
   */
  private $testmode;

  /**
   * Enable payments logs
   *
   * @var string
   */
  private $debug;

  /**
   * Teya RPG API class
   *
   * @var Borgun_RPG_Api
   */
  private $api;

  public function __construct(){
    $this->id                 = 'borgun_rpg';
    $this->icon               = BORGUN_RPG_URL . '/cards.png';
    $this->has_fields         = false;
    $this->method_title       = 'Teya RPG';
    $this->method_description = 'Teya RPG';
    // Load the form fields
    $this->init_form_fields();
    $this->init_settings();
    $this->enabled            = $this->get_option( 'enabled' );
    $this->title              = $this->get_option( 'title' );
    $this->description        = $this->get_option( 'description' );
    $this->testmode           = $this->get_option( 'testmode' );

    $this->debug              = 'yes' === $this->get_option( 'debug', 'no' );
    self::$log_enabled        = $this->debug;
    $this->api = new Borgun_RPG_Api();

    add_action( 'woocommerce_update_options_payment_gateways_borgun_rpg', array( $this, 'process_admin_options' ) );
    add_action( 'wp_enqueue_scripts', array($this, 'add_borgun_payment_library_script'));
    add_action( 'before_woocommerce_pay', array( $this, 'before_borgun_pay' ) );
    add_action( 'woocommerce_receipt_borgun_rpg', array( $this, 'receipt_page_button' ) );

    $this->supports           = array(
      'products',
      'subscriptions',
      'refunds',
      'subscription_cancellation',
      'subscription_suspension',
      'subscription_reactivation',
      'subscription_amount_changes',
      'subscription_date_changes',
      'subscription_payment_method_change',
      'subscription_payment_method_change_customer',
      'subscription_payment_method_change_admin',
    );
  }

  public function init_form_fields() {
    $this->form_fields = array(
      'enabled'            => array(
        'title'       => __( 'Enable/Disable', 'borgun_rpg' ),
        'label'       => __( 'Enable Teya RPG', 'borgun_rpg' ),
        'type'        => 'checkbox',
        'description' => '',
        'default'     => 'no'
      ),
      'title'              => array(
        'title'       => __( 'Title', 'borgun_rpg' ),
        'type'        => 'text',
        'description' => __( 'This controls the title which the user sees during checkout.', 'borgun_rpg' ),
        'default'     => __( 'Teya RPG', 'borgun_rpg' ),
      ),
      'description'        => array(
        'title'       => __( 'Description', 'borgun_rpg' ),
        'type'        => 'textarea',
        'description' => __( 'This controls the description which the user sees during checkout.', 'borgun_rpg' ),
        'default'     => __( 'Pay with your credit card via Teya.', 'borgun_rpg' ),
      ),
      'testmode'           => array(
        'title'       => __( 'Test Mode', 'borgun_rpg' ),
        'label'       => __( 'Enable Test Mode', 'borgun_rpg' ),
        'type'        => 'checkbox',
        'description' => __( 'Place the payment gateway in development mode.', 'borgun_rpg' ),
        'default'     => 'no'
      ),
      'enabled_3d_secure' => array(
        'title'       => __( 'Enable/Disable 3D secure', 'borgun_rpg' ),
        'label'       => __( 'Enable 3D secure', 'borgun_rpg' ),
        'type'        => 'checkbox',
        'description' => '',
        'default'     => 'no'
      ),
      'merchantid'         => array(
        'title'       => __( 'Merchant ID', 'borgun_rpg' ),
        'type'        => 'text',
        'description' => __( 'This is the ID supplied by Teya.', 'borgun_rpg' ),
        'default'     => ''
      ),
      'publickey'          => array(
        'title'       => __( 'Public Key', 'borgun_rpg' ),
        'type'        => 'text',
        'description' => __( 'This is the Public Key supplied by Teya.', 'borgun_rpg' ),
        'default'     => ''
      ),
      'privatekey'          => array(
        'title'       => __( 'Private Key', 'borgun_rpg' ),
        'type'        => 'text',
        'description' =>  __( 'This is the Private Key supplied by Teya.', 'borgun_rpg' ),
        'default'     => ''
      ),
      'debug' => array(
        'title'       => __( 'Debug', 'borgun_rpg' ),
        'label'       => __( 'Enable Debug Mode', 'borgun_rpg' ),
        'type'        => 'checkbox',
        'default'     => 'no',
        'desc_tip'    => true,
      ),
    );
  }

  public function admin_options() {
    if ( $this->is_valid_for_use() )
      echo '<table class="form-table">' . $this->generate_settings_html( $this->get_form_fields(), false ) . '</table>'; // WPCS: XSS ok.
    else
      echo sprintf(
        '<div class="inline error"><p><strong>%s</strong>: %s</p></div>',
        __( 'Gateway Disabled', 'woocommerce' ),
        __( 'Current Store currency is not supported by Teya RPG. Allowed values are GBP, USD, EUR, DKK, NOK, SEK, CHF, CAD, HUF, BHD, AUD, RUB, PLN, RON, HRK, CZK and ISK.', 'borgun_rpg' )
      );
  }

  //Check if this gateway is enabled and available in the user's country
  function is_valid_for_use() {
    if ( ! in_array( get_woocommerce_currency(), array(
      'ISK',
      'GBP',
      'USD',
      'EUR',
      'DKK',
      'NOK',
      'SEK',
      'CHF',
      'CAD',
      'HUF',
      'BHD',
      'AUD',
      'RUB',
      'PLN',
      'RON',
      'HRK',
      'CZK',
    ) )
    ) {
      return false;
    }

    return true;
  }

  /**
  * Processes and saves options.
  * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
  *
  * @return bool was anything saved?
  */
  public function process_admin_options() {
    $saved = parent::process_admin_options();

    // Maybe clear logs.
    if(!$this->debug){
      if ( empty( self::$log ) ) {
        self::$log = wc_get_logger();
      }
      self::$log->clear( 'borgun_rpg' );
    }

    return $saved;
  }

  /**
   * Logging method.
   *
   * @param string $message Log message.
   * @param string $level Optional. Default 'info'. Possible values:
   *                      emergency|alert|critical|error|warning|notice|info|debug.
   */
  public static function log( $message, $level = 'info' ) {
    if ( self::$log_enabled ) {
      if ( empty( self::$log ) ) {
        self::$log = wc_get_logger();
      }
      self::$log->log( $level, $message, array( 'source' => 'borgun_rpg' ) );
    }
  }

  public function add_borgun_payment_library_script(){
    global $wp;
    if($this->testmode == 'yes'){
      wp_enqueue_script( 'borgun_payment_js', 'https://test.borgun.is/resources/js/borgunpayment-js/borgunpayment.v1.min.js');
    } else {
      wp_enqueue_script( 'borgun_payment_js', 'https://ecommerce.borgun.is/resources/js/borgunpayment-js/borgunpayment.v1.min.js');
    }
    $order_id = ( !empty( $wp->query_vars['order-pay']) ) ? (int) $wp->query_vars['order-pay'] : '';
    $borgun_args = [];
    $borgun_args['key'] = $this->get_option( 'publickey' );
    $borgun_args['ajax_url'] = admin_url( 'admin-ajax.php' );
    $borgun_args['nonce'] = wp_create_nonce( 'borgun_ajax' );
    $borgun_args['order_id'] = $order_id;
    $borgun_args['ajax_delay'] = 5000;
    wp_enqueue_script( 'borgun_rpg_js', BORGUN_RPG_URL.'assets/js/borgun_rpg.js');
    wp_localize_script( 'borgun_rpg_js','borgun_data', $borgun_args );
  }

  public function payment_fields(){
    if($this->testmode == 'yes'){
      _e( 'In test mode, you can use the card number 4741520000000003 with any CVC and a valid expiration date', 'borgun_rpg' );
    }
    $this->form();
    print '<input type="hidden" id="borgun-rpg-card-token" name="borgun-rpg-card-token">';
    echo '<div class="error"><ul class="error-message"></ul></div>';
  }

  public function process_payment( $order_id ) {
    $order = wc_get_order( $order_id );
    $card_token = sanitize_text_field( $_POST['borgun-rpg-card-token'] );
    $multitoken_response = $this->api->create_multitoken($card_token);
    $multitoken = (isset($multitoken_response->Token) && $multitoken_response->Token ) ? sanitize_text_field($multitoken_response->Token) : '' ;
    $this->set_token_transaction_id( $order, $multitoken );
    if($this->api->is_use_3d_secure()){
      $redirect_to_payment = true;

      $response = $this->api->mpiEnrollment($order, $multitoken);
      WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG - Enrollment response: %s', 'borgun_rpg' ), wc_print_r($response, true) ) );

      if( isset($response->MdStatus) && $response->MdStatus == 5 ) {
        // 5 - Authentication unavailable, Issuer is unable to process 3DSecure request. Merchant can decide to continue with transaction if merchant considers risk as low.
        // Additional request with another Exponent value
        $response = $this->api->mpiEnrollment($order, $multitoken, true);
        WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG - Additional Enrollment response: %s', 'borgun_rpg' ), wc_print_r($response, true) ) );
      }

      $md_status = ( isset($response->MdStatus) && $response->MdStatus ) ? (int)$response->MdStatus : null;
      if($md_status || $md_status === 0){
        update_post_meta( $order_id, 'borgun_mpi_md_status', $md_status );
        if( isset($response->MPIToken) && $response->MPIToken ){
          update_post_meta( $order_id, 'borgun_mpi_token', sanitize_text_field($response->MPIToken) );
        }
        if( isset($response->XId) && $response->XId ){
          update_post_meta( $order_id, 'borgun_mpi_xid', sanitize_text_field($response->XId) );
        }

        switch ($md_status) {
          case 0:
          case 8:
            //0 - Not Authenticated, Note: Cardholder did not finish the 3DSecure procedure successfully, Action: Do not continue transaction 
            //8 - Fraud score block, Note: 3DS attempt was blocked by MPI, Action: Do not continue transaction
            $redirect_to_payment = false;
            break;
          case 50:
            //50 - In 3DS, Note: Method An extra authentication step is required before 3DSecure procedure is started.
            if( isset($response->TDSMethodContent) && !empty($response->TDSMethodContent) ) {
              $enrollment = [];
              $enrollment['XId'] = sanitize_text_field($response->XId);
              $enrollment['TxId'] = sanitize_text_field($response->TxId);
              $enrollment['TDSMethodContent'] = $response->TDSMethodContent;
              update_post_meta( $order_id, 'borgun_tds_enrollment', $enrollment);
            }
            break;
          case 9:
            // 9 - Pending, Note:MdStatus in enrollment response when merchant should start 3DSecure procedure.
            if( isset( $response->RedirectToACSForm ) && !empty($response->RedirectToACSForm) ) 
              update_post_meta( $order_id, 'borgun_secure_form', $response->RedirectToACSForm);
            break;
        }
      }else{
        $redirect_to_payment = false;
      }

      if($redirect_to_payment){
        return array(
          'result'   => 'success',
          'redirect' => $order->get_checkout_payment_url( true ),
        );
      }else{
        $error_message = '';
        if( empty($response) ){
          $error_message = __('3DSecure procedure failed(error:1)', 'borgun_rpg');
        }elseif( isset($response->Message) && $response->Message ){
          $error_message = $response->Message;
        }elseif( isset($response->MdErrorMessage) && $response->MdErrorMessage ){
           $error_message = $response->MdErrorMessage;
        }

        if( empty($error_message) )  $error_message = __('3DSecure procedure failed(error:2)', 'borgun_rpg');
        wc_add_notice( $error_message, 'error');
        return array(
          'result'   => 'fail',
          'messages' => $error_message
        );
      }
    }
    else{
      $response = $this->api->create_payment($order, $multitoken);
      if(isset($response->error)){
        WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG - Payment error: %s', 'borgun_rpg' ), wc_print_r($response, true) ) );
        wc_add_notice( __('Payment error: ', 'borgun_rpg') . $response->error, 'error' );
        $order->add_order_note( __('Payment error:', 'borgun_rpg') . $response->error );
        /* translators: error message */
        $order->update_status( 'failed' );
        return array(
          'result'   => 'fail',
          'messages' => __('Payment error:', 'borgun_rpg') . $response->error,

        );
      }
      elseif(!isset($response->TransactionId)){
        WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG - Payment error: %s', 'borgun_rpg' ), wc_print_r($response, true) ) );
        wc_add_notice( __('Payment error:', 'borgun_rpg') . $response->Message, 'error' );
        $order->add_order_note( __('Payment error:', 'borgun_rpg') . $response->Message );
        /* translators: error message */
        $order->update_status( 'failed' );
        return array(
          'result'   => 'fail',
          'messages' => __('Payment error:', 'borgun_rpg') . $response->Message,
        );
      }
      else{
        if($response->TransactionStatus != 'Accepted'){
          WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG - Transaction Status: %s', 'borgun_rpg' ), $response->TransactionStatus ) );
          wc_add_notice( __('Transaction Status: ', 'borgun_rpg') . $response->TransactionStatus, 'error' );
          return array(
            'result'   => 'fail',
            'messages' => __('Transaction Status:', 'borgun_rpg') . $response->TransactionStatus,
          );
        }
        else{
          $order->payment_complete($response->TransactionId);
          // Remove cart
          WC()->cart->empty_cart();
        }
      }

      return array(
          'result'    => 'success',
          'redirect'  => $this->get_return_url( $order )
      );
    }

  }

  protected function set_token_transaction_id( $order, $token_transaction_id ) {
    update_post_meta( $order->get_id(), '_borgun_rpg_token_transaction_id', $token_transaction_id );
	}

  protected function get_token_transaction_id( $order ) {
     return get_post_meta( $order->get_id(), '_borgun_rpg_token_transaction_id', true );
  }

  public function process_refund( $order_id, $amount = NULL, $reason = '' ) {
    $transaction_id = get_post_meta( $order_id , '_transaction_id', true );
    $order = wc_get_order( $order_id );
    if(empty($transaction_id)){
      $transaction_id = $order->get_transaction_id();
    }
    $response = $this->api->refund_payment( $transaction_id, $amount, $reason );
    WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG - Refund response: %s', 'borgun_rpg' ), wc_print_r($response, true) ) );
    if($response->response['code'] != 200){
      if(isset($response->Message)){
        return new WP_Error( 'borgun_rpg_refund_error', $response->Message);
      }
      if(isset($response->error)){
        return new WP_Error( 'borgun_rpg_refund_error', $response->error );
      }
    }
    else{
      $order->update_status('refunded', __('Refund success', 'borgun_rpg'));
    }

    return true;
  }

  public function receipt_page($order_id) {
    $order = wc_get_order($order_id);
    //WC_Gateway_Borgun_RPG::log( sprintf( __( '(ignore)Receipt_page, request: %s', 'borgun_rpg' ), wc_print_r($_REQUEST, true) ) );

    $payment_args = [];
    $payment_args['token'] = get_post_meta( $order_id, '_borgun_rpg_token_transaction_id', true );
    $payment_args['XId'] = get_post_meta( $order_id, 'borgun_mpi_xid', true );
    $mpi_token = get_post_meta( $order_id, 'borgun_mpi_token', true );

    $mpi_md_status = get_post_meta( $order_id, 'borgun_mpi_md_status', true );
    if( in_array($mpi_md_status,[1,2,3,4]) ){
      /* MDStatus  Recommended action  Notes
        1 - Authenticated Continue transaction  Cardholder successfully authenticated.
        2,3 - Not participating Continue transaction  Cardholder not enrolled in 3DSecure or issuer of the card is not participating in 3DSecure
        4 - Attempt Continue transaction  3DSecure attempt recognized by card issuer.
      */
      $payment_args['mpi_token'] = $mpi_token;
      WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG - payment mpi token atttempt', 'borgun_rpg' ) ) );
      $response = $this->api->create_payment_with_3d_secure($order, $payment_args);
      WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG -create_payment_with_3d_secure, response: %s', 'borgun_rpg' ), wc_print_r($response, true) ) );

      if($response->TransactionStatus == 'Accepted'){
        $message = sprintf( __( 'Payment %s(%s)', 'borgun_rpg' ), $response->TransactionStatus, $response->TransactionId);
        $order->add_order_note( $message );
        $order->payment_complete($response->TransactionId);
        WC()->cart->empty_cart();
        wp_safe_redirect( $this->get_return_url( $order ));
        exit;
      }else{
        $error_message = '';
        if(isset($response->Message) && !empty($response->Message) )
          $error_message = __('Payment error: ', 'borgun_rpg') . sanitize_text_field($response->Message);
        if(empty($error_message) && isset($response->error) && !empty($response->error) )
          $error_message = __('Payment error: ', 'borgun_rpg') . sanitize_text_field($response->error);
        if(empty($error_message))
          $error_message = sanitize_text_field($response->TransactionStatus);

        $order->add_order_note( $error_message );
        $order->update_status( 'failed' );
        wc_add_notice( $error_message, 'error' );
      }
    }else{
      $borgun_tds_method = get_post_meta( $order_id, 'borgun_tds_enrollment', true );
      if(!empty($borgun_tds_method)){
        //$iframe = stripcslashes($borgun_tds_method['TDSMethodContent']);
        //$iframe = htmlspecialchars_decode($iframe, ENT_NOQUOTES);
        if(isset($borgun_tds_method['TDSMethodContent']) && !empty($borgun_tds_method['TDSMethodContent'])){
          echo '<div class="borgun-rpg-tds">' . $borgun_tds_method['TDSMethodContent'] . '</div>';
          unset($borgun_tds_method['TDSMethodContent']);
          update_post_meta( $order_id, 'borgun_tds_enrollment', $borgun_tds_method);
        }
      }

      $borgun_secure_form = get_post_meta( $order_id, 'borgun_secure_form', true );
      if(!empty($borgun_secure_form)){
        delete_post_meta($order_id, 'borgun_secure_form');
        // delete form css
        $borgun_secure_form = str_replace('<link href="https://mpi.borgun.is/mdpaympi/static/mpi.css" rel="stylesheet" type="text/css">','', $borgun_secure_form);
        echo '<div style="display:none;">' . $borgun_secure_form .'</div>';
        die();
      }

      // Read external mpi enrollment response
      $mpi_validation_attempt = false;
      if( isset($_POST['PaRes']) || isset($_POST['cres']) || isset($_POST['MD']) || isset($_POST['MPIToken']) ) {
        $mpi_validation_attempt = true;
        $secure = [];
        if( isset($_POST['PaRes']) && !empty($_POST['PaRes']) )
          $secure['PARes'] = sanitize_text_field($_POST['PaRes']);
        if( isset($_POST['cres']) && !empty($_POST['cres']) )
            $secure['cres'] = sanitize_text_field($_POST['cres']);
        if( isset($_POST['MD']) && !empty($_POST['MD']) )
            $secure['MD'] = sanitize_text_field($_POST['MD']);

        $response = $this->api->mpiValidation($secure);
        WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG - mpiValidation response: %s', 'borgun_rpg' ), wc_print_r($response, true) ) );
        if(isset($response->AuthenticationStatus) && $response->AuthenticationStatus == 'Y'){
          if( (isset($response->CAVV) && $response->CAVV) )
            $payment_args['CAVV'] = sanitize_text_field($response->CAVV);
          if( (isset($response->XId) && $response->XId) )
            $payment_args['XId'] = sanitize_text_field($response->XId);
        }elseif( !empty($mpi_token) && in_array($response->MdStatus,[1,2,3,4,5,6,91,92,93,94,95,96,97,99]) ){
          /* MDStatus  Recommended action  Notes
          1 - Authenticated Continue transaction  Cardholder successfully authenticated.
          2,3 - Not participating Continue transaction  Cardholder not enrolled in 3DSecure or issuer of the card is not participating in 3DSecure
          4 - Attempt Continue transaction  3DSecure attempt recognized by card issuer.
          5 - Authentication unavailable  Continue transaction if risk manageable or retry 3DSecure procedure Issuer is unable to process 3DSecure request. Merchant can decide to continue with transaction if merchant considers risk as low. Please see Notes on ISK for a special case when processing ISK.
          6 - 3DSecure error  Continue transaction if risk manageable or retry 3DSecure procedure Invalid field in 3-D Secure message generation, error message received or directory server fails to validate the merchant.
          91 - Network error  Continue transaction if risk manageable or retry 3DSecure procedure Network error, connection to directory server times out.
          92 - Directory error  Continue transaction if risk manageable or retry 3DSecure procedure Directory response read timeout or other failure.
          93 - Configuration error  Continue transaction if risk manageable or retry 3DSecure procedure Service is disabled, invalid configuration, etc.
          94 - Input error  Continue transaction if risk manageable or retry 3DSecure procedure Merchant request had errors
          95 - No directory error Continue transaction if risk manageable No directory server found configured for PAN/card type.
          97 - Unable to locate live transaction  Continue transaction if risk manageable or retry 3DSecure procedure Unable to locate live transaction, too late or already processed.
          96 - No directory error Continue transaction if risk manageable No version 2 directory server found configured for PAN/card type and flow requires version 2 processing.
          99 - System error Continue transaction if risk manageable or retry 3DSecure procedure System error
          */
          $payment_args['mpi_token'] = (isset($response->MPIToken) &&  $response->MPIToken) ? sanitize_text_field($response->MPIToken) : $mpi_token;
          WC_Gateway_Borgun_RPG::log( sprintf( __( 'mpiValidation response with MdStatus %s. Attempt to сontinue transaction with mpiToken', 'borgun_rpg' ), $response->MdStatus ) );
        }else{
          $error_message = '';
          $response_details = [];
          if( $response->MdStatus == 0 ){
            $response_details []= __('Cardholder did not finish the 3DSecure procedure successfully(MdStatus:0)','borgun_rpg');
          }elseif($response->MdStatus == 8){
            $response_details[] = __('3DS attempt was blocked by MPI(MdStatus:8)','borgun_rpg');
          }elseif($response->MdStatus == 7){
            $response_details[] = __('MPI/Our error(MdStatus:7)','borgun_rpg');
          }
          if( isset($response->MdErrorMessage) && $response->MdErrorMessage ){
            $error_message =  sprintf( __( 'MdErrorMessage: %s ', 'borgun_rpg' ), $response->MdErrorMessage );
            $response_details[] = sprintf( __( 'MdErrorMessage: %s ', 'borgun_rpg' ), $response->MdErrorMessage );
          }
          if( isset($response->EnrollmentStatus) && $response->EnrollmentStatus )
            $response_details[] = sprintf( __( 'EnrollmentStatus: %s ', 'borgun_rpg' ), $response->EnrollmentStatus );
          if( isset($response->AuthenticationStatus) && $response->AuthenticationStatus )
            $response_details[] = sprintf( __( 'AuthenticationStatus: %s ', 'borgun_rpg' ), $response->AuthenticationStatus );

          if(empty($error_message))
            $error_message = __('3DSecure procedure failed(error:3)', 'borgun_rpg');

          if( !empty($response_details) ) $order->add_order_note( implode("\n", $response_details) );
          wc_print_notice( $error_message, 'error' );
        }

        if( (isset($payment_args['CAVV']) && !empty($payment_args['CAVV']) ) ||  (isset($payment_args['mpi_token']) && !empty($payment_args['mpi_token']) ) ){
          $response = $this->api->create_payment_with_3d_secure($order, $payment_args);
          WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG -create_payment_with_3d_secure, response: %s', 'borgun_rpg' ), wc_print_r($response, true) ) );

          if($response->TransactionStatus == 'Accepted'){
            $message = sprintf( __( 'Payment %s(%s)', 'borgun_rpg' ), $response->TransactionStatus, $response->TransactionId);
            $order->add_order_note( $message );
            $order->payment_complete($response->TransactionId);
            WC()->cart->empty_cart();
            wp_safe_redirect( $this->get_return_url( $order ));
            exit;
          }else{
            $error_message = '';
            if(isset($response->Message) && !empty($response->Message) )
              $error_message = __('Payment error: ', 'borgun_rpg') . sanitize_text_field($response->Message);
            if(empty($error_message) && isset($response->error) && !empty($response->error) )
              $error_message = __('Payment error: ', 'borgun_rpg') . sanitize_text_field($response->error);
            if(empty($error_message))
              $error_message = sanitize_text_field($response->TransactionStatus);

            $order->add_order_note( $error_message );
            $order->update_status( 'failed' );
            wc_add_notice( $error_message, 'error' );
          }
        }
      }
    }
  }

  public function before_borgun_pay(){
    if ( !isset( $_GET['pay_for_order'], $_GET['key'] ) ) { // WPCS: input var ok, CSRF ok.
      global $wp;
      $order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // WPCS: input var ok, CSRF ok.
      $order_pay = absint($wp->query_vars['order-pay']);
      $order_id = wc_get_order_id_by_order_key( $order_key );
      try {
        $order     = wc_get_order( $order_pay );
        if(!$order){
          throw new Exception( __( 'Sorry, this order is invalid and cannot be paid for.', 'woocommerce' ) );
        }
        if( ! hash_equals( $order->get_order_key(), $order_key ) ){
          throw new Exception( __( 'Sorry, this order is invalid and cannot be paid for.', 'woocommerce' ) );
        }
      } catch ( Exception $e ) {
        wc_print_notice( $e->getMessage(), 'error' );
      }

      if($order->get_payment_method() == $this->id ){
        $this->receipt_page($order_id);
      }
    }
  }

  public function receipt_page_button($order_id){
    if ( !isset( $_GET['pay_for_order'], $_GET['key'] ) ) { // WPCS: input var ok, CSRF ok.
      $order = wc_get_order( $order_id );
      if($order && $order->needs_payment()){
        $order_button_text = apply_filters( 'woocommerce_pay_order_button_text', __( 'Pay for order', 'woocommerce' ) );
        echo apply_filters( 'borgun_pay_order_html', '<a class="button alt" href="' . esc_attr( $order->get_checkout_payment_url( false ) ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</a>' ); // @codingStandardsIgnoreLine
      }
    }
  }
}
