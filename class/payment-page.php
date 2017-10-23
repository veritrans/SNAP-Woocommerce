<?php
          // $order_items = array();
          // $cart = $woocommerce->cart;
          $snapToken = $_GET['snap_token'];

          // TODO evaluate whether finish & error url need to be hardcoded
          $wp_base_url = home_url( '/' );
          $finish_url = $wp_base_url."?wc-api=WC_Gateway_Midtrans";
          $error_url = $wp_base_url."?wc-api=WC_Gateway_Midtrans";
          $snap_script_url = ($this->environment == 'production') ? "https://app.midtrans.com/snap/snap.js" : "https://app.sandbox.midtrans.com/snap/snap.js";

          // ## Print HTML
          ?>
          <script data-cfasync="false" id="snap_script" src="<?php echo $snap_script_url;?>" data-client-key="<?php echo $this->client_key; ?>"></script>
          <a id="pay-button" title="Do Payment!" class="button alt">
            Loading Payment...
          </a>
          
          <div id="payment-instruction" style="display:none;">
            <h3 class="alert alert-info"> Awaiting Your Payment </h3>
            <!-- <br> -->
            <p> Please complete your payment as instructed </p>
            <!-- <br> -->
            <a target="_blank" href="#" id="payment-instruction-btn" title="Do Payment!" class="button alt" >
              Payment Instruction
            </a>
          </div>

          <script data-cfasync="false" type="text/javascript">
          document.addEventListener("DOMContentLoaded", function(event) { 
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

            // Continously retry to execute SNAP popup if fail, with 1000ms delay between retry
            function execSnapCont(){
              var callbackTimer = setInterval(function() {
                var snapExecuted = false;
                try{
                  snap.pay("<?php echo $snapToken; ?>", 
                  {
                    skipOrderSummary : true,
                    onSuccess: function(result){
                      // console.log(result); // debug
                      window.location = "<?php echo $finish_url;?>&order_id="+result.order_id+"&status_code="+result.status_code+"&transaction_status="+result.transaction_status;
                    },
                    onPending: function(result){ // on pending, instead of redirection, show PDF instruction link
                      // console.log(result); // debug
                      
                      if (result.fraud_status == 'challenge'){ // if challenge redirect to finish
                        window.location = "<?php echo $finish_url;?>&order_id="+result.order_id+"&status_code="+result.status_code+"&transaction_status="+result.transaction_status;
                      }

                      // Show payment instruction and hide payment button
                      document.getElementById('payment-instruction-btn').href = result.pdf_url;
                      document.getElementById('pay-button').style.display = "none";
                      document.getElementById('payment-instruction').style.display = "block";
                      // if no pdf instruction, hide the btn
                      if(!result.hasOwnProperty("pdf_url")){
                        document.getElementById('payment-instruction-btn').style.display = "none";
                      }
                    },
                      onError: function(result){
                      // console.log(result); // debug
                      window.location = "<?php echo $error_url;?>&order_id="+result.order_id+"&status_code="+result.status_code+"&transaction_status="+result.transaction_status;
                    }
                  });
                  snapExecuted = true; // if SNAP popup executed, change flag to stop the retry.
                } catch (e){ 
                  console.log(e);
                  console.log("Snap s.goHome not ready yet... Retrying in 1000ms!");
                }
                if (snapExecuted) {
                  clearInterval(callbackTimer);
                }
              }, 1000);
            };

            console.log("Loading snap JS library now!");
            // Loading SNAP JS Library to the page    
            loadExtScript("<?php echo $snap_script_url;?>");
            console.log("Snap library is loaded now");

            var clickCount = 0;
            var payButton = document.getElementById("pay-button");

            payButton.onclick = function(){
              if(clickCount >= 2){
                location.reload();
                payButton.innerHTML = "Loading...";
                return;
              }
              execSnapCont();
              clickCount++;
            };

            // Call execSnapCont() 
            execSnapCont();
            payButton.innerHTML = "Proceed To Payment";
          });
          </script>
          
          <?php
          // ## End of print HTML