<?php

/**
 * Helper functions that is redundant function used on multiple payment classes
 */
class WC_Midtrans_Logger {
    /**
     * Utilize WC logger class
     */
    public static function log( $message, $log_file_name, $plugin_id = 'midtrans', $start_time = null, $end_time = null ) {
        if ( ! class_exists( 'WC_Logger' ) ) {
          return;
        }
  
        if ( apply_filters( 'wc_midtrans_logging', true, $message ) ) {
          $logger = new WC_Logger();
          $options = get_option( 'woocommerce_' . $plugin_id . '_settings' );
          if ( ! isset($options['logging'] ) || $options['logging'] != 'yes' ) return;
          // if ( ! $options['logging'] == 'yes' ) return;
  
          if ( ! is_null( $start_time ) ) {
            $formatted_start_time = date_i18n( 'r', $start_time );
            $end_time             = is_null( $end_time ) ? current_time( 'timestamp' ) : $end_time;
            $formatted_end_time   = date_i18n( 'r', $end_time );
            $elapsed_time         = round( abs( $end_time - $start_time ) / 60, 2 );
  
            $log_entry  = "\n" . '====Midtrans Plugin Version: ' . MIDTRANS_PLUGIN_VERSION . '====' . "\n";
            $log_entry .= '====Start Log ' . $formatted_start_time . '====' . "\n" . $message . "\n";
            $log_entry .= '====End Log ' . $formatted_end_time . ' (' . $elapsed_time . ')====' . "\n\n";
  
          } else {
            $log_entry  = "\n" . '====Midtrans Plugin Version: ' . MIDTRANS_PLUGIN_VERSION . '====' . "\n";
            $log_entry .= '====Start Log====' . "\n" . $message . "\n" . '====End Log====' . "\n\n";
  
          }
          
          $logger->debug( $log_entry, array( 'source' => $log_file_name ) );
        }
      }
}

