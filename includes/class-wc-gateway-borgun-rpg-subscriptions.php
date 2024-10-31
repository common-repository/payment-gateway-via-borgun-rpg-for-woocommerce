<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Gateway_Borgun_RPG_Subscriptions' ) ) {
	class WC_Gateway_Borgun_RPG_Subscriptions extends WC_Gateway_Borgun_RPG {
    function __construct() {

			parent::__construct();

			add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 2 );

			// allow store managers to manually set eWAY as the payment method on a subscription*/
			add_filter( 'woocommerce_subscription_payment_meta', array( $this, 'add_subscription_payment_meta' ), 10, 2 );

		}

    public function add_subscription_payment_meta( $payment_meta, $subscription ) {
			$payment_meta[ $this->id ] = array(
				'post_meta' => array(
					'_borgun_rpg_token_transaction_id' => array(
						'value' => get_post_meta( $subscription->id, '_borgun_rpg_token_transaction_id', true ),
						'label' => __( 'Teya RPG Token Transaction ID', 'borgun_rpg' ),
					),
				),
			);

			return $payment_meta;
		}

    protected function set_token_transaction_id( $order, $token_transaction_id ) {
			$subscriptions = $this->get_subscriptions_from_order( $order );
			if ( $subscriptions ) {
				foreach ( $subscriptions as $subscription ) {
					parent::set_token_transaction_id( $subscription, $token_transaction_id );
				}
			}
			parent::set_token_transaction_id( $order, $token_transaction_id );

		}

    protected function get_token_transaction_id( $order ) {
			return parent::get_token_transaction_id( $order );

		}

    protected function get_subscriptions_from_order( $order ) {
			if ( $this->order_contains_subscription( $order ) ) {
				$subscriptions = wcs_get_subscriptions_for_order( $order );
				if ( $subscriptions ) {
					return $subscriptions;
				}
			}
			return false;

		}

    protected function order_contains_subscription( $order ) {
			return function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order ) );
		}

    function scheduled_subscription_payment( $amount_to_charge, $order ) {
		  $result = $this->process_subscription_payment( $order, $amount_to_charge );
      if(isset($result->error)){
        $order->add_order_note( sprintf( __( 'Teya subscription renewal failed - %s', 'borgun_rpg' ), $result->error) );
      }
      else{
        if(isset($result->TransactionId)){
          if($result->TransactionStatus != 'Accepted'){
            WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG - Subscription Payment error: %s', 'borgun_rpg' ), wc_print_r($result, true) ) );
            $order->add_order_note( sprintf( __( 'Teya subscription renewal failed. TransactionStatus - %s', 'borgun_rpg' ), $result->TransactionStatus ) );
          }
          else{
            $order->add_order_note( sprintf( __( 'Subscription renewed. Transaction Id: %s', 'borgun_rpg' ), esc_attr($result->TransactionId) ) );
          	$order->update_status( 'processing' );
          }

        }
      }
	}

	function process_subscription_payment( $order = '', $amount = 0 ) {
			$token_transaction_id = $this->get_token_transaction_id( $order );
			if ( ! $token_transaction_id ) {
				WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG - Subscription: %s', 'borgun_rpg' ), 'Token Transaction ID not found' ) );
				return new WP_Error( 'borgun_error', __( 'Token Transaction ID not found', 'borgun_rpg' ) );
			}

			// Charge the customer
			try {
				$api = new Borgun_RPG_Api();
				WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG - scheduled_subscription_payment', 'borgun_rpg' ) ) );
				$response = $api->create_payment($order, $token_transaction_id);
				WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG - scheduled_subscription_payment, response: %s', 'borgun_rpg' ), wc_print_r($response, true) ) );
				return $response;
			} catch ( Exception $e ) {
				WC_Gateway_Borgun_RPG::log( sprintf( __( 'Teya PRG - Subscription Payment error: %s', 'borgun_rpg' ), wc_print_r($e, true) ) );
				return new WP_Error( 'borgun_error', $e->getMessage() );
			}
		}
	}
}
