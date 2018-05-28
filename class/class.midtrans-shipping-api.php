<?php

if (!defined('MT_PLUGIN_DIR')) {
  exit;
}

if (!class_exists('MT_SHIPPR_API')) {
  
  class MT_SHIPPR_API {
    
    private $base_url = 'https://api.cekongkir.id/v2/';
    private $token = 'lsS2GUT2L4XHVWOfgI0eW7BzetMfJjIC1xN3KIJkOO4N4qVCX1';
    private $timeout = 10;
    
    function request_params( $body = array() ) {
      global $woocommerce;
      global $wp_version;

      $headers['Content-Type'] = 'application/json';
      $headers['Authorization'] = 'Bearer '.$this->token;
      $headers['app'] = 'Midtrans SNAP Woocommerce v'.MT_PLUGIN_VERSION;
      $headers['platform'] = 'Wordpress v'.$wp_version.' ::: Woocommerce v'.$woocommerce->version;
      $headers['siteurl'] = get_bloginfo('url');

      return array(
        'headers' => $headers,
        'timeout' => $this->timeout,
        'body' => json_encode($body)
      );
    }

    public function geocoder( $params = array() ) {
      $body['address'] = $params['address'];
      $this->response = wp_remote_post(
        $this->base_url.'geocoder',
        $this->request_params($body)
      );
      $this->process_result();
      return $this->result;
    }

    public function rates( $params = array() ) {
      $body = array(
        'origin' => $params['origin'],
        'destination' => $params['destination'],
        'weight_in_grams' => $params['weight_in_grams'],
        'providers' => array('gosend')
      );
      $this->response = wp_remote_post(
        $this->base_url.'rates',
        $this->request_params($body)
      );
      $this->process_result();
      return $this->result;
    }
      
    public function process_result(){
      if ( ! is_wp_error( $this->response ) ) {
        $body = json_decode( $this->response['body'], TRUE );
        if ($body['message'] === 'success'){
          $result['status'] = 'success';
          $result['data'] = $body['data'];
        }
        else {
          $result['status'] = 'error';
          $result['message'] = $body['message'];
        }
      }
      else {
        $result['status'] = 'error';
        $result['message'] = __('Gagal', 'Midtrans');
      }
      $this->result = $result;  
    }
  }
}
