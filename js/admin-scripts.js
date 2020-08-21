(function($){
	function sensitiveOptionsMidtrans() {
        var environment_type = $("select[name*='midtrans_environment']").val();
        
        var api_environment_string = environment_type + '_settings';

        $('.toggle-midtrans').closest('tr').hide();
        $('.' + api_environment_string).closest('tr').show();
    }

	$(document).ready(function(){
		
        $("select[name*='midtrans_environment']").on('change', function(e, data) {
            sensitiveOptionsMidtrans();
        });

        sensitiveOptionsMidtrans();
		
        // Hide 3ds and save card field on midtrans subscription admin settings
        if ( $('[id*=woocommerce_midtrans_subscription]').length > 0) {
            hideSavecard();
            hide3ds();
        }
        function hide3ds(hide3ds = true){
            if (hide3ds)
                $('#woocommerce_midtrans_subscription_enable_3d_secure').closest('tr').hide();
        }
        function hideSavecard(hideSavecard = true){
            if (hideSavecard)
                $('#woocommerce_midtrans_subscription_enable_savecard').closest('tr').hide();
        }

	});
})(jQuery);