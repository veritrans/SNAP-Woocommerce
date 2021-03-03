// polyfill for '.closest'
if (!Element.prototype.matches) {
  Element.prototype.matches =
    Element.prototype.msMatchesSelector ||
    Element.prototype.webkitMatchesSelector;
}
if (!Element.prototype.closest) {
  Element.prototype.closest = function(s) {
    var el = this;

    do {
      if (Element.prototype.matches.call(el, s)) return el;
      el = el.parentElement || el.parentNode;
    } while (el !== null && el.nodeType === 1);
    return null;
  };
}

function midtransCheckAndToggleEnvFieldDisplay(trOriginalDisplayValue) {
    // needed to properly restore element visibility after hiding via style.display
    var trOriginalDisplayValue = trOriginalDisplayValue || 'table-row';

    // hide all (sandbox & production) env fields
    var allMidtransEnvFieldEl = document.querySelectorAll('.toggle-midtrans');
    for (var i = allMidtransEnvFieldEl.length - 1; i >= 0; i--) {
        allMidtransEnvFieldEl[i]
            .closest('tr')
            .style.display = 'none'; // hide
    }

    // then show only active env fields
    var midtransActiveEnv = (document.querySelector("select[name*='midtrans_environment']")).value;
    var midtransActiveEnvElClass = midtransActiveEnv + '_settings';
    var allMidtransActiveEnvFieldEl = document.querySelectorAll('.' + midtransActiveEnvElClass);
    for (var i = allMidtransActiveEnvFieldEl.length - 1; i >= 0; i--) {
        allMidtransActiveEnvFieldEl[i]
            .closest('tr')
            .style.display = trOriginalDisplayValue; // show
    }
}

function midtransHide3dsField(){
    document.querySelector('#woocommerce_midtrans_subscription_enable_3d_secure')
        .closest('tr')
        .style.display = 'none'; // hide
}
function midtransHideSavecardField(){
    document.querySelector('#woocommerce_midtrans_subscription_enable_savecard')
        .closest('tr')
        .style.display = 'none'; // hide
}

// Main script that will be executed on page load
document.addEventListener("DOMContentLoaded", function(event) {
	console.log('custom script loaded');
    // execute only if element detected
    var midtransEnvFieldEl = document.querySelector("select[name*='midtrans_environment']");
    if(midtransEnvFieldEl){
        // get `tr` element CSS display original value
        var trOriginalDisplayValue = midtransEnvFieldEl.closest('tr').style.display;
        midtransCheckAndToggleEnvFieldDisplay(trOriginalDisplayValue);
        midtransEnvFieldEl.addEventListener('change', function() {
            midtransCheckAndToggleEnvFieldDisplay(trOriginalDisplayValue);
        });

        // Hide 3ds and save card field on midtrans subscription admin settings
        if ( document.querySelectorAll('[id*=woocommerce_midtrans_subscription]').length > 0) {
            midtransHideSavecardField();
            midtransHide3dsField();
        }
    }
});