<?php
  /**
   * HTML to display Snap Payment page
   */
  
  $mixpanel_key_production = "17253088ed3a39b1e2bd2cbcfeca939a";
  $mixpanel_key_sandbox = "9dcba9b440c831d517e8ff1beff40bd9";
  // $order_items = array();
  // $cart = $woocommerce->cart;
  $isProduction = $this->environment == 'production';
  $snapToken = $_GET['snap_token'];
  // $snapToken = preg_match("/^[a-zA-Z0-9_-]*$/",$snapToken) ? $snapToken : '';
  $snapToken = htmlspecialchars($snapToken, ENT_COMPAT,'ISO-8859-1', true);
  $mixpanel_key = $isProduction ? $mixpanel_key_production : $mixpanel_key_sandbox;

  // TODO evaluate whether finish & error url need to be hardcoded
  $wp_base_url = home_url( '/' );
  $finish_url = $wp_base_url."?wc-api=WC_Gateway_Midtrans";
  $finish_url = '"'.$finish_url.'&order_id="+result.order_id+"&status_code="+result.status_code+"&transaction_status="+result.transaction_status';
  if(isset($this->enable_map_finish_url) && $this->enable_map_finish_url == 'yes'){
    $finish_url = 'result.finish_redirect_url';
  }
  $error_url = $wp_base_url."?wc-api=WC_Gateway_Midtrans";
  $snap_api_base_url = $isProduction ? "https://app.midtrans.com" : "https://app.sandbox.midtrans.com";
  $snap_script_url = $snap_api_base_url."/snap/snap.js";

  // ## Print HTML
  ?>
  <!-- start Mixpanel -->
  <script data-cfasync="false" type="text/javascript">(function(e,a){if(!a.__SV){var b=window;try{var c,l,i,j=b.location,g=j.hash;c=function(a,b){return(l=a.match(RegExp(b+"=([^&]*)")))?l[1]:null};g&&c(g,"state")&&(i=JSON.parse(decodeURIComponent(c(g,"state"))),"mpeditor"===i.action&&(b.sessionStorage.setItem("_mpcehash",g),history.replaceState(i.desiredHash||"",e.title,j.pathname+j.search)))}catch(m){}var k,h;window.mixpanel=a;a._i=[];a.init=function(b,c,f){function e(b,a){var c=a.split(".");2==c.length&&(b=b[c[0]],a=c[1]);b[a]=function(){b.push([a].concat(Array.prototype.slice.call(arguments,0)))}}var d=a;"undefined"!==typeof f?d=a[f]=[]:f="mixpanel";d.people=d.people||[];d.toString=function(b){var a="mixpanel";"mixpanel"!==f&&(a+="."+f);b||(a+=" (stub)");return a};d.people.toString=function(){return d.toString(1)+".people (stub)"};k="disable time_event track track_pageview track_links track_forms register register_once alias unregister identify name_tag set_config reset people.set people.set_once people.increment people.append people.union people.track_charge people.clear_charges people.delete_user".split(" ");for(h=0;h<k.length;h++)e(d,k[h]);a._i.push([b,c,f])};a.__SV=1.2;b=e.createElement("script");b.type="text/javascript";b.async=!0;b.src="undefined"!==typeof MIXPANEL_CUSTOM_LIB_URL?MIXPANEL_CUSTOM_LIB_URL:"file:"===e.location.protocol&&"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js".match(/^\/\//)?"https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js":"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js";c=e.getElementsByTagName("script")[0];c.parentNode.insertBefore(b,c)}})(document,window.mixpanel||[]);mixpanel.init("<?php echo $mixpanel_key ?>");</script>
  <!-- end Mixpanel -->

  <?php if(property_exists($this,'ganalytics_id') && strlen($this->ganalytics_id)>0){ ?>
  <!-- start Google Analytics -->
  <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', '<?php echo $this->ganalytics_id ?>', 'auto');
  ga('send', 'pageview');
  </script>
  <!-- end Google Analytics -->
  <?php } ?>

  <script data-cfasync="false" id="snap_script" src="<?php echo $snap_script_url;?>" data-client-key="<?php echo $this->client_key;?>"></script>
  <a id="pay-button" title="Do Payment!" class="button alt">Loading Payment...</a>
  
  <div id="payment-instruction" style="display:none;">
    <!-- <h3 class="alert alert-info"> Awaiting Your Payment </h3> -->
    <!-- <br> -->
    <p> Please complete your payment as instructed. If you have done your payment, please check your email or my "Order" menu for order status. </p>
    <!-- <br> -->
    <a target="_blank" href="#" id="payment-instruction-btn" title="Do Payment!" class="button alt" >
      Payment Instruction
    </a>
  </div>

  <script data-cfasync="false" type="text/javascript">
  var mixpanel = mixpanel ? mixpanel : { init : function(){}, track : function(){} };
  var payButton = document.getElementById("pay-button");

  document.addEventListener("DOMContentLoaded", function(event) { 
    function MixpanelTrackResult(token, merchant_id, cms_name, cms_version, plugin_name, plugin_version, status, result) {
      var eventNames = {
        pay: 'pg-pay',
        success: 'pg-success',
        pending: 'pg-pending',
        error: 'pg-error',
        close: 'pg-close'
      };
      mixpanel.track(
        eventNames[status], 
        {
          merchant_id: merchant_id,
          cms_name: cms_name,
          cms_version: cms_version,
          plugin_name: plugin_name,
          plugin_version: plugin_version,
          snap_token: token,
          payment_type: result ? result.payment_type: null,
          order_id: result ? result.order_id: null,
          status_code: result ? result.status_code: null,
          gross_amount: result && result.gross_amount ? Number(result.gross_amount) : null,
        }
      );
    }
    var SNAP_TOKEN = "<?php echo $snapToken;?>";
    var MERCHANT_ID = "<?php echo $this->get_option('merchant_id');?>";
    var CMS_NAME = "woocommerce";
    var CMS_VERSION = "<?php echo WC_VERSION;?>";
    var PLUGIN_NAME = "<?php echo $pluginName;?>";
    var PLUGIN_VERSION = "<?php echo MT_PLUGIN_VERSION;?>";
    // Safely load the snap.js
    function loadExtScript(src) {
      // if snap.js is loaded from html script tag, don't load again
      if (document.getElementById('snap_script'))
        return;
      // Append script to doc
      var s = document.createElement("script");
      s.src = src;
      a = document.body.appendChild(s);
      a.setAttribute('data-client-key','<?php echo $this->client_key; ?>');
      a.setAttribute('data-cfasync','false');
    }

    var retryCount = 0;
    var snapExecuted = false;
    var intervalFunction = 0;
    // Continously retry to execute SNAP popup if fail, with 1000ms delay between retry
    function execSnapCont(ccDetails){
      intervalFunction = setInterval(function() {
        try{
          snap.pay(SNAP_TOKEN, 
          {
            creditCardNumber: ccDetails ? ccDetails.creditCardNumber : '',
            creditCardCvv: ccDetails ? ccDetails.creditCardCvv : '',
            creditCardExpiry: ccDetails ? ccDetails.creditCardExpiry : '',
            customerEmail: ccDetails ? ccDetails.customerEmail : '',
            customerPhone: ccDetails ? ccDetails.customerPhone : '',
            skipOrderSummary : true,
            onSuccess: function(result){
              MixpanelTrackResult(SNAP_TOKEN, MERCHANT_ID, CMS_NAME, CMS_VERSION, PLUGIN_NAME, PLUGIN_VERSION, 'success', result);
              // console.log(result?result:'no result');
              payButton.innerHTML = "Loading...";
              window.location = <?php echo $finish_url;?>;
            },
            onPending: function(result){ // on pending, instead of redirection, show PDF instruction link
              MixpanelTrackResult(SNAP_TOKEN, MERCHANT_ID, CMS_NAME, CMS_VERSION, PLUGIN_NAME, PLUGIN_VERSION, 'pending', result);
              // console.log(result?result:'no result');
              if (result.fraud_status == 'challenge'){ // if challenge redirect to finish
                payButton.innerHTML = "Loading...";
                window.location = <?php echo $finish_url;?>;
              }
              // redirect to thank you page
              window.location = <?php echo $finish_url;?>; 
              return;
              // Below code is UNUSED
              // Show payment instruction and hide payment button
              document.getElementById('payment-instruction-btn').href = result.pdf_url;
              document.getElementById('pay-button').style.display = "none";
              document.getElementById('payment-instruction').style.display = "block";
              // if no pdf instruction, hide the btn
              if(!result.hasOwnProperty("pdf_url")){
                document.getElementById('payment-instruction-btn').style.display = "none";
              }
              // Update order with PDF url to backend
              try{
                result['pdf_url_update'] = true;
                result['snap_token_id'] = SNAP_TOKEN;
                fetch('<?php echo $wp_base_url."?wc-api=WC_Gateway_Midtrans";?>', {
                  method: 'POST',
                  headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                  },
                  body: JSON.stringify(result)
                });
              }catch(e){ console.log(e); }
            },
            onError: function(result){
              MixpanelTrackResult(SNAP_TOKEN, MERCHANT_ID, CMS_NAME, CMS_VERSION, PLUGIN_NAME, PLUGIN_VERSION, 'error', result);
              // console.log(result?result:'no result');
              payButton.innerHTML = "Loading...";
              window.location = "<?php echo $error_url;?>&order_id="+result.order_id+"&status_code="+result.status_code+"&transaction_status="+result.transaction_status;
            },
            onClose: function(){
              MixpanelTrackResult(SNAP_TOKEN, MERCHANT_ID, CMS_NAME, CMS_VERSION, PLUGIN_NAME, PLUGIN_VERSION, 'close', null);
              // console.log(result?result:'no result');
            }
          });
          snapExecuted = true; // if SNAP popup executed, change flag to stop the retry.
        } catch (e){ 
          retryCount++;
          if(retryCount >= 10){
            location.reload(); payButton.innerHTML = "Loading..."; return;
          }
          console.log(e);
          console.log("Snap not ready yet... Retrying in 1000ms!");
        } finally {
          if (snapExecuted) {
            clearInterval(intervalFunction);
            // record 'pay' event to Mixpanel
            MixpanelTrackResult(SNAP_TOKEN, MERCHANT_ID, CMS_NAME, CMS_VERSION, PLUGIN_NAME, PLUGIN_VERSION, 'pay', null);
          }
        }
      }, 1000);
    };

    var createPaymentRequest = function(){
      var supportedPaymentMethods = [
        {
          supportedMethods: 'basic-card',
          data: {
            supportedNetworks: ['visa', 'mastercard', 'jcb', 'amex'],
          }
        }
      ];
      var paymentDetails = {
        total: {
          label: 'Total',
          amount:{
            currency: 'IDR',
            value: <?php echo $gross_amount;?>
          }
        }
      };
      // Options isn't required.
      var options = {  
        requestPayerName: false,
        requestPayerPhone: true,
        requestPayerEmail: true,
      };

      return  new PaymentRequest(
        supportedPaymentMethods,
        paymentDetails,
        options
      );
    };

    var clickCount = 0;
    // ENTRY POINT
    function handlePayAction() {
      if(clickCount >= 2){
        location.reload();
        payButton.innerHTML = "Loading...";
        return;
      }
      var ccDetails = null;
      // Check if paymentRequest is supported
      if(window.PaymentRequest){
          var payRequest = createPaymentRequest();
          payRequest
            .show()
            .then(function(result){
              result.complete('success');
              ccDetails = {
                creditCardNumber: result.details.cardNumber,
                creditCardCvv: result.details.cardSecurityCode,
                creditCardExpiry: 
                  result.details.expiryMonth+'/'+result.details.expiryYear.slice(-2),
                customerEmail: result.payerEmail,
                customerPhone: result.payerPhone,
              };
              console.log('Browser Payment Request Completed!, passing to Snap');
              execSnapCont(ccDetails);
            })
            .catch(function(err){
              console.log('- Failed Browser Payment Request!, fallback to regular Snap');
              execSnapCont(ccDetails);
            })
      } else {
        console.log("No Browser Payment Request support, fallback to regular Snap");
        execSnapCont(ccDetails);
      }
      clickCount++;
    };

    console.log("Loading snap JS library now!");
    // Loading SNAP JS Library to the page    
    loadExtScript("<?php echo $snap_script_url;?>");
    console.log("Snap library is loaded now");

    payButton.onclick = handlePayAction;

    handlePayAction();
    payButton.innerHTML = "Proceed To Payment";
  });
  </script>
  
<?php
// ## End of print HTML