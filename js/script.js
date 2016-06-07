(function($){

"use strict";

var wc_veritrans = {
  el: {
    $form: undefined,
    $payment_box: undefined
  },
  order_id: '',

  /**
   * Event Binding
   */
  event_binding: function() {
    this.el.$form.on('checkout_place_order_veritrans', $.proxy( this.submit_form, this ));
    $('body').on('updated_checkout', $.proxy( this.update_form_value, this ));
  },

  /**
   * Update form value
   *
   * Because we are using separate div for showing payment method,
   * Every change on cloned payment method div have to be reflected on 
   * original payment method div
   */
  update_form_value: function(e) {
    var $this = $(e.currentTarget);

    // When triggered by update_checkout event
    if( $this.is('body') ) {
      $('.payment_methods_list .payment_method_veritrans > p').each(function(){
        var name = $(this).find('input, select').attr('name'),
            value = $(this).find('input, select').val();

        if( $('#payment .payment_methods [name="'+name+'"]').length > 0 ) {
          $('#payment .payment_methods [name="'+name+'"]').val( value );
        }
      });
    } 

    // Triggered by input or select change
    else {
      var name = $this.attr('name'),
          value = $this.val();

      $('#payment .payment_methods [name="'+name+'"]').val( value );
    }
  },

  /**
   * Credit Card Information needed for request token from Veritrans
   */
  _cardset: function() {
    return {
      card_number: $('.veritrans_credit_card').val(),
      card_exp_month: $('.veritrans_card_exp_month').val(),
      card_exp_year: $('.veritrans_card_exp_year').val(),
      card_cvv: $('.veritrans_security').val()
    }
  },

  /**
   * Validate checkout form
   */
  validate_checkout_form: function( veritrans_error ) {
    var $form = this.el.$form,
        validate_message = '';

    // Remove old errors
    $('.woocommerce-error, .woocommerce-message').remove();

    // Show validate message
    $form.find('.woocommerce-invalid').each(function(){
      var $this = $(this),
          label = $this.find('label').text().replace('*', '');

      if( !$this.parent().hasClass('payment_method_veritrans') ) {
        validate_message += '<li><strong>'+ label +'</strong> is a required field.</li>';
      }
    });
    
    if( veritrans_error ) {
      if( veritrans_error.status == 'failure' ) {
        veritrans_error.message = veritrans_error.message.replace('[','').replace(']','');
        validate_message += '<li><strong>Veritrans Error: </strong> '+ veritrans_error.message +'</li>';
      }
    }

    // Show error message if form is not valid
    if( $form.find('.woocommerce-invalid').length > 0 || veritrans_error ) {
      $('<ul class="woocommerce-error">'+ validate_message +'</ul>').prependTo( $form );
    }
  },

  /**
   * Callback when response from Veritrans is success
   */
  _success: function(res) {
    var token_id = res.data.token_id,
        order_id = this.order_id,
        $form = this.el.$form;
  
    // Fill the token id field
    $('[name="veritrans_token_id"]').val( res.data.token_id );

    // Validate checkout form
    this.validate_checkout_form();

    // Request token succeed, process the form submission
    this.submit_form_ajax();
  },

  /** 
   * Callback when response from Veritrans is error
   */
  _error: function(res) {
    var message = res.message.replace('[','').replace(']',''),
        $form = this.el.$form;

    // Remove old errors
    $('.woocommerce-error, .woocommerce-message').remove();

    // Add new errors
    if ( res.message ) $form.prepend( res.messages );

    // Cancel processing
    $form.removeClass('processing').unblock();

    // Lose focus for all fields
    $form.find( '.input-text, select' ).blur();

    this.validate_checkout_form( res );

    // Scroll to top
    $('html, body').animate({
        scrollTop: ($('form.checkout').offset().top - 100)
    }, 1000);
  },

  /**
   * Submit Checkout Form
   */
  submit_form: function( e ) {
    var _this = this,
        $form = this.el.$form,
        form_data = $form.data();
    
    $form.addClass('processing');

    if ( form_data["blockUI.isBlocked"] != 1 )
      $form.block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.ajax_loader_url + ') no-repeat center', backgroundSize: '16px 16px', opacity: 0.6}});

    // Get Veritrans Token
    Veritrans.tokenGet( _this._cardset, $.proxy(_this._success, _this), $.proxy(_this._error, _this) );

    return false;
  },

  /**
   * Submit Form Ajax
   */
  submit_form_ajax: function() {
    var _this = this,
        $form = this.el.$form,
        form_data = $form.data(),
        serialized_data = $form.serialize();
        
    // Get credit cart data
    serialized_data += '&veritrans_credit_card=' + $('.veritrans_credit_card').val();
    serialized_data += '&veritrans_card_exp_month=' + $('.veritrans_card_exp_month').val();
    serialized_data += '&veritrans_card_exp_year=' + $('.veritrans_card_exp_year').val();
    serialized_data += '&veritrans_security_field=' + $('.veritrans_security_field').val();

    $.ajax({
      type:     'POST',
      url:      wc_checkout_params.checkout_url,
      data:     serialized_data,
      success:  function( code ) {
        var result = '';
        
        try {
          // Get the valid JSON only from the returned string
          if ( code.indexOf("<!--WC_START-->") >= 0 )
            code = code.split("<!--WC_START-->")[1]; // Strip off before after WC_START

          if ( code.indexOf("<!--WC_END-->") >= 0 )
            code = code.split("<!--WC_END-->")[0]; // Strip off anything after WC_END
          debugger;
          // Parse
          result = $.parseJSON( code );

          if ( result.result == 'success' ) {

            window.location = decodeURI(result.redirect);

          } else if ( result.result == 'failure' ) {
            throw "Result failure";
          } else {
            throw "Invalid response";
          }
        }
        catch( err ) {
          // Remove old errors
          $('.woocommerce-error, .woocommerce-message').remove();

          // Add new errors
          if ( result.messages )
            $form.prepend( result.messages );
          else
            $form.prepend( code );

          // Cancel processing
          $form.removeClass('processing').unblock();

          // Lose focus for all fields
          $form.find( '.input-text, select' ).blur();

          // Scroll to top
          $('html, body').animate({
              scrollTop: ($('form.checkout').offset().top - 100)
          }, 1000);

          // Trigger update in case we need a fresh nonce
          if ( result.refresh == 'true' )
            $('body').trigger('update_checkout');
        }
      },
    });
  },

  /**
   * Initialize function
   */
  init: function() {
    Veritrans.client_key = wc_veritrans_client_key;

    this.el.$form = $('form.checkout');
    this.el.$payment_box = $('.payment_methods_list .payment_method_veritrans');

    this.event_binding();
  }
};

$(document).ready(function(){
  wc_veritrans.init();
});

})(jQuery);