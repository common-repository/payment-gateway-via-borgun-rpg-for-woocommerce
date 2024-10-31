<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'class-borgun-rpg-settings.php';

class Borgun_RPG_Api{

  private $settings;


  public function __construct(){
    $this->settings = new Borgun_RPG_Helper();
  }


  public function create_payment($order, $multitoken){
    $payment_data = (object)[
      "TransactionType" => "Sale",
      "Amount" => $order->get_total()*100,
      "Currency" => $this->settings->getCurrencyCode($order->get_currency()),
      "TransactionDate"  => $order->get_date_created()->date('Y-m-dTH:i:s'),
      "OrderId"  =>  $this->settings->getOrderId($order->get_order_number()),
      "PaymentMethod" =>(object)[
        "PaymentType" => "TokenMulti",
        "Token" => $multitoken,
      ],
      "Metadata" => (object)["Payload" => $this->settings->getPayload() ],
    ];

    return $this->request_post($payment_data,'payment');
  }

  public function create_payment_with_3d_secure($order, $args){
    $payment_data = (object)[
      "TransactionType" => "Sale",
      "Amount" => $order->get_total()*100,
      "Currency" => $this->settings->getCurrencyCode($order->get_currency()),
      "TransactionDate"  => $order->get_date_created()->date('Y-m-dTH:i:s'),
      "OrderId"  =>  $this->settings->getOrderId($order->get_order_number()),
      "PaymentMethod" =>(object)[
        "PaymentType" => "TokenMulti",
        "Token" => $args['token'],
      ],
      "Metadata" => (object)["Payload" => $this->settings->getPayload() ],
    ];

    if(isset($args['CAVV']) && !empty($args['CAVV'])){
      $payment_data->ThreeDSecure = (object)[
        "DataType" => "Manual",
        "SecurityLevelInd" => "2",
        "CAVV" => $args['CAVV'],
        "Xid" => $args['XId'],
      ];
    }else{
      $payment_data->ThreeDSecure = (object)[
        "DataType" => "Token",
        "MpiToken" => $args['mpi_token'],
        "Xid" => $args['XId'],
      ];
    }

    $response = $this->request_post($payment_data,'payment');
    return $response;
  }

  public function cancel_payment($id){

  }

  public function refund_payment($transaction_id,$amount,$reason = ''){
    $payment_data = (object)[
      'PartialAmount' => $amount*100,
    ];
    $response = $this->request_put($transaction_id, $payment_data, 'refund_payment');
    return $response;
  }

  public function capture_payment($id){

  }

  private function request_post($data,$type){
    $response = wp_safe_remote_post(
			$this->settings->getEndpoint($type),
			array(
				'method'  => 'POST',
				'headers' => array('Authorization'=> 'Basic ' . base64_encode( $this->settings->getPrivateKey() . ':' ),'Content-Type' =>'application/json'),
				'body'    => json_encode($data),
				'timeout' => 70,
			)
		);

    return json_decode( $response['body'] );
  }

  private function request_put($transaction_id,$data,$type){
    $response = wp_remote_request(
			$this->settings->getEndpoint($type,$transaction_id),
			array(
				'method'  => 'PUT',
				'headers' => array('Authorization'=> 'Basic ' . base64_encode( $this->settings->getPrivateKey() . ':' ),'Content-Type' =>'application/json'),
				'body'    => json_encode($data),
				'timeout' => 70,
			)
		);

    return json_decode( $response['body'] );
  }

  private function request_get($payment_data){

  }

  public function create_multitoken($card_token){
    $token_data = (object)[
      "TokenSingle" => $card_token,
      "Metadata" => (object)["Payload" => $this->settings->getPayload() ],
    ];
    $response = $this->request_post($token_data,'multitoken');
    return $response;
  }

  public function mpiEnrollment($order, $multitoken, $override_exponent = false) {
    $multiplier = 100;
    $exponent = 2;

    if( $override_exponent == true ) {
      $multiplier = 1;
      $exponent = 0;
    }

    // Set default exponent 0 if ISK
    $currency = $order->get_currency();
    if( $currency == 'ISK') {
      $multiplier = 1;
      $exponent = 0;

      if( $override_exponent == true ) {
        $multiplier = 100;
        $exponent = 2;
      }
    }

    $payment_data = (object)[
      'CardDetails' => (object)[
        "PaymentType" => "TokenMulti",
        "Token" => $multitoken,
      ],
      "PurchAmount" => $order->get_total()*$multiplier,
      "Exponent" => $exponent,
      "Currency" => $this->settings->getCurrencyCode( $order->get_currency() ),
      "TermUrl" => $order->get_checkout_payment_url( true ),
      "TDS2ThreeDSMethodNotificationURL" => $order->get_checkout_payment_url( true )
    ];
    $response = $this->request_post($payment_data,'mpi_enrollment');
    return $response;
  }

  public function secondMpiEnrollment($args){
    $api_args = (object)[
      'XId'=>$args['XId'],
      'TxId'=>$args['TxId'],
      'TDS2ThreeDSCompInd'=>"Y"
    ];
    $response = $this->request_post($api_args,'mpi_enrollment');
    return $response;
  }

  public function mpiValidation($args){
    $api_args = [];
    if(isset($args['PaRes']))
      $api_args['PARes'] = $args['PaRes'];

    if(isset($args['cres']))
      $api_args['cres'] = $args['cres'];

    if(isset($args['MD']))
        $api_args['MD'] = $args['MD'];

    $api_args = (object)$api_args;
    $response = $this->request_post($api_args, 'mpi_validation');
    return $response;
  }

  public function is_use_3d_secure(){
    return $this->settings->is_use_3d_secure();
  }
}
