<?php  

/**
 * Helper functions that is redundant function used on multiple payment classes
 */
class WC_Midtrans_Utils
{
  /**
   * Convert 2 digits coundry code to 3 digit country code
   *
   * @param String $country_code Country code which will be converted
   */
  public static function convert_country_code( $country_code ) {

    // 3 digits country codes
    $cc_three = array( 'AF' => 'AFG', 'AX' => 'ALA', 'AL' => 'ALB', 'DZ' => 'DZA', 'AD' => 'AND', 'AO' => 'AGO', 'AI' => 'AIA', 'AQ' => 'ATA', 'AG' => 'ATG', 'AR' => 'ARG', 'AM' => 'ARM', 'AW' => 'ABW', 'AU' => 'AUS', 'AT' => 'AUT', 'AZ' => 'AZE', 'BS' => 'BHS', 'BH' => 'BHR', 'BD' => 'BGD', 'BB' => 'BRB', 'BY' => 'BLR', 'BE' => 'BEL', 'PW' => 'PLW', 'BZ' => 'BLZ', 'BJ' => 'BEN', 'BM' => 'BMU', 'BT' => 'BTN', 'BO' => 'BOL', 'BQ' => 'BES', 'BA' => 'BIH', 'BW' => 'BWA', 'BV' => 'BVT', 'BR' => 'BRA', 'IO' => 'IOT', 'VG' => 'VGB', 'BN' => 'BRN', 'BG' => 'BGR', 'BF' => 'BFA', 'BI' => 'BDI', 'KH' => 'KHM', 'CM' => 'CMR', 'CA' => 'CAN', 'CV' => 'CPV', 'KY' => 'CYM', 'CF' => 'CAF', 'TD' => 'TCD', 'CL' => 'CHL', 'CN' => 'CHN', 'CX' => 'CXR', 'CC' => 'CCK', 'CO' => 'COL', 'KM' => 'COM', 'CG' => 'COG', 'CD' => 'COD', 'CK' => 'COK', 'CR' => 'CRI', 'HR' => 'HRV', 'CU' => 'CUB', 'CW' => 'CUW', 'CY' => 'CYP', 'CZ' => 'CZE', 'DK' => 'DNK', 'DJ' => 'DJI', 'DM' => 'DMA', 'DO' => 'DOM', 'EC' => 'ECU', 'EG' => 'EGY', 'SV' => 'SLV', 'GQ' => 'GNQ', 'ER' => 'ERI', 'EE' => 'EST', 'ET' => 'ETH', 'FK' => 'FLK', 'FO' => 'FRO', 'FJ' => 'FJI', 'FI' => 'FIN', 'FR' => 'FRA', 'GF' => 'GUF', 'PF' => 'PYF', 'TF' => 'ATF', 'GA' => 'GAB', 'GM' => 'GMB', 'GE' => 'GEO', 'DE' => 'DEU', 'GH' => 'GHA', 'GI' => 'GIB', 'GR' => 'GRC', 'GL' => 'GRL', 'GD' => 'GRD', 'GP' => 'GLP', 'GT' => 'GTM', 'GG' => 'GGY', 'GN' => 'GIN', 'GW' => 'GNB', 'GY' => 'GUY', 'HT' => 'HTI', 'HM' => 'HMD', 'HN' => 'HND', 'HK' => 'HKG', 'HU' => 'HUN', 'IS' => 'ISL', 'IN' => 'IND', 'ID' => 'IDN', 'IR' => 'RIN', 'IQ' => 'IRQ', 'IE' => 'IRL', 'IM' => 'IMN', 'IL' => 'ISR', 'IT' => 'ITA', 'CI' => 'CIV', 'JM' => 'JAM', 'JP' => 'JPN', 'JE' => 'JEY', 'JO' => 'JOR', 'KZ' => 'KAZ', 'KE' => 'KEN', 'KI' => 'KIR', 'KW' => 'KWT', 'KG' => 'KGZ', 'LA' => 'LAO', 'LV' => 'LVA', 'LB' => 'LBN', 'LS' => 'LSO', 'LR' => 'LBR', 'LY' => 'LBY', 'LI' => 'LIE', 'LT' => 'LTU', 'LU' => 'LUX', 'MO' => 'MAC', 'MK' => 'MKD', 'MG' => 'MDG', 'MW' => 'MWI', 'MY' => 'MYS', 'MV' => 'MDV', 'ML' => 'MLI', 'MT' => 'MLT', 'MH' => 'MHL', 'MQ' => 'MTQ', 'MR' => 'MRT', 'MU' => 'MUS', 'YT' => 'MYT', 'MX' => 'MEX', 'FM' => 'FSM', 'MD' => 'MDA', 'MC' => 'MCO', 'MN' => 'MNG', 'ME' => 'MNE', 'MS' => 'MSR', 'MA' => 'MAR', 'MZ' => 'MOZ', 'MM' => 'MMR', 'NA' => 'NAM', 'NR' => 'NRU', 'NP' => 'NPL', 'NL' => 'NLD', 'AN' => 'ANT', 'NC' => 'NCL', 'NZ' => 'NZL', 'NI' => 'NIC', 'NE' => 'NER', 'NG' => 'NGA', 'NU' => 'NIU', 'NF' => 'NFK', 'KP' => 'MNP', 'NO' => 'NOR', 'OM' => 'OMN', 'PK' => 'PAK', 'PS' => 'PSE', 'PA' => 'PAN', 'PG' => 'PNG', 'PY' => 'PRY', 'PE' => 'PER', 'PH' => 'PHL', 'PN' => 'PCN', 'PL' => 'POL', 'PT' => 'PRT', 'QA' => 'QAT', 'RE' => 'REU', 'RO' => 'SHN', 'RU' => 'RUS', 'RW' => 'EWA', 'BL' => 'BLM', 'SH' => 'SHN', 'KN' => 'KNA', 'LC' => 'LCA', 'MF' => 'MAF', 'SX' => 'SXM', 'PM' => 'SPM', 'VC' => 'VCT', 'SM' => 'SMR', 'ST' => 'STP', 'SA' => 'SAU', 'SN' => 'SEN', 'RS' => 'SRB', 'SC' => 'SYC', 'SL' => 'SLE', 'SG' => 'SGP', 'SK' => 'SVK', 'SI' => 'SVN', 'SB' => 'SLB', 'SO' => 'SOM', 'ZA' => 'ZAF', 'GS' => 'SGS', 'KR' => 'KOR', 'SS' => 'SSD', 'ES' => 'ESP', 'LK' => 'LKA', 'SD' => 'SDN', 'SR' => 'SUR', 'SJ' => 'SJM', 'SZ' => 'SWZ', 'SE' => 'SWE', 'CH' => 'CHE', 'SY' => 'SYR', 'TW' => 'TWN', 'TJ' => 'TJK', 'TZ' => 'TZA', 'TH' => 'THA', 'TL' => 'TLS', 'TG' => 'TGO', 'TK' => 'TKL', 'TO' => 'TON', 'TT' => 'TTO', 'TN' => 'TUN', 'TR' => 'TUR', 'TM' => 'TKM', 'TC' => 'TCA', 'TV' => 'TUV', 'UG' => 'UGA', 'UA' => 'UKR', 'AE' => 'ARE', 'GB' => 'GBR', 'US' => 'USA', 'UY' => 'URY', 'UZ' => 'UZB', 'VU' => 'VUT', 'VA' => 'VAT', 'VE' => 'VEN', 'VN' => 'VNM', 'WF' => 'WLF', 'EH' => 'ESH', 'WS' => 'WSM', 'YE' => 'YEM', 'ZM' => 'ZMB', 'ZW' => 'ZWE' );

    // Check if country code exists
    if( isset( $cc_three[ $country_code ] ) && $cc_three[ $country_code ] != '' ) {
      $country_code = $cc_three[ $country_code ];
    }
    else{
     // $country_code = ''; 
    }

    return $country_code;
  }

  /**
   * Helper for backward compatibility WC v3 & v2 on getting Order Property
   * @param  [String] $order    Order Object
   * @param  [String] $property Target property
   * @return the property
   */
  public static function getOrderProperty($order, $property){
    $functionName = "get_".$property;
    if (method_exists($order, $functionName)){ // WC v3
      return (string)$order->{$functionName}();
    } else { // WC v2
      return (string)$order->{$property};
    }
  }

  /**
   * Helper to print error as expected by Woocommerce ajax call
   * On payment.
   * @param  [error] $e
   * @return [array] JSON encoded error messages.
   */
  public static function json_print_exception ( $e, $depedency ) {
    $errorObj = array(
      'result' => "failure", 
      'messages' => '<div class="woocommerce-error" role="alert"> Midtrans Exception: '.$e->getMessage().'. <br>Plugin Title: '.esc_html($depedency->method_title).'</div>',
      'refresh' => false, 
      'reload' => false
    );
    $errorJson = json_encode($errorObj);
    echo $errorJson;
  }

  /**
   * @param array      $array
   * @param int|string $position
   * @param mixed      $insert
   * @return array insert array to a specific index of an array
   */
  public static function array_insert( &$array, $position, $insert ) {
    $index = array_search( $position, array_keys( $array ) );
    $pos =  $index === false ? count( $array ) : $index + 1;

    $array = array_merge(
              array_slice($array, 0, $pos),
              $insert,
              array_slice($array, $pos)
            );
  }

  // `wp_get_script_tag` & `wp_get_inline_script_tag` is used to avoid future CSP issue
  // because future WP version might implement CSP, which means JS script tags without proper
  // nonce attribute (presumably auto-generated by those funcs) may not be executable.
  // Introduced in WP 5.7: https://make.wordpress.org/core/2021/02/23/introducing-script-attributes-related-functions-in-wordpress-5-7/

  // Backward compatibility technique for 'polyfill'-ing not yet exist func, according to 
  // https://developer.wordpress.org/plugins/plugin-basics/best-practices/#example

  /**
   * Declare global function `wp_get_script_tag`, if it not exist (WP version <5.7)
   * To ensure backward compatibility with WP version <5.7
   * based on https://wpseek.com/function/wp_get_script_tag/
   * and https://wpseek.com/function/wp_sanitize_script_attributes/
   * @return void function declared on global namespace
   */
  public static function polyfill_wp_get_script_tag(){
    if( !function_exists('wp_get_script_tag')){
      function wp_get_script_tag($attributes = array()){
        if ( !isset($attributes['type'])) {
          $attributes['type'] = 'text/javascript';
        }
        $attributes_string    = '';
        foreach ( $attributes as $attribute_name => $attribute_value ) {
          if ( is_bool( $attribute_value ) ) {
            if ( $attribute_value ) {
              $attributes_string .= ' ' . $attribute_name;
            }
          } else {
            $attributes_string .= sprintf( 
              ' %1$s="%2$s"', esc_attr( $attribute_name ), esc_attr( $attribute_value ) );
          }
        }
        $script_string = sprintf( "<script%s></script>\n", $attributes_string );
        return $script_string;
      }
    }
  }
  /**
   * Declare global function `wp_get_inline_script_tag`, if it not exist (WP version <5.7)
   * To ensure backward compatibility with WP version <5.7
   * based on https://wpseek.com/function/wp_get_inline_script_tag/
   * @return void function declared on global namespace
   */
  public static function polyfill_wp_get_inline_script_tag(){
    if( !function_exists('wp_get_inline_script_tag')){
      function wp_get_inline_script_tag($javascript, $attributes = array()){
        $script_string = wp_get_script_tag($attributes);
        // add the inline javascript before closing script tag
        $script_string = str_replace(
          "</script>", sprintf("%s</script>",$javascript), $script_string);
        return $script_string;
      }
    }
  }

  /**
   * In case Snap API return 406 duplicate order ID, this helper func will generate
   * new order id that is to prevent duplicate, by adding suffix on the order_id string
   * @TAG: order-suffix-separator
   * @param  string the original WC order_id
   * @return string the non duplicate order_id added with suffix
   */
  public static function generate_non_duplicate_order_id($order_id){
    $suffix_separator = '-wc-mdtrs-';
    $date = new DateTime();
    $unix_timestamp = $date->getTimestamp();

    $non_duplicate_order_id = $order_id.$suffix_separator.$unix_timestamp;
    return $non_duplicate_order_id;
  }

  /**
   * Retrieve original WC order_id from a non duplicate order_id produced by function above:
   * generate_non_duplicate_order_id. This will check if the suffix separator exist,
   * and split it to get the original order_id.
   * @TAG: order-suffix-separator
   * @param  string any order_id either original or non duplicate version
   * @return string the original WC order_id
   */
  public static function check_and_restore_original_order_id($non_duplicate_order_id){
    $suffix_separator = '-wc-mdtrs-';
    $original_order_id = $non_duplicate_order_id;
    if(strpos($non_duplicate_order_id, $suffix_separator) !== false){
      $splitted_order_id_strings = explode($suffix_separator, $non_duplicate_order_id);
      // only return the left-side of the separator, ignore the rest
      $original_order_id = $splitted_order_id_strings[0];
    }
    return $original_order_id;
  }

}
?>