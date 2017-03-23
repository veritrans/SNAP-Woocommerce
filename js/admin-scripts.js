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
		
        function hideSavecard(hideSavecard = true){
            if (hideSavecard)
                $('.toggle-advanced').closest('tr').hide();
        } hideSavecard(false);

	});
})(jQuery);