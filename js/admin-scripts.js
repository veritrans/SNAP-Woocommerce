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

jQuery(function($){
	$('.gosend_origin').select2({
  		ajax: {
			url: ajaxurl,
			type: 'post',
            dataType: 'json',
			delay: 250,
			cache: true,
            data: function (params) {
                return {
                    q: params.term,
                    action: 'gosend_origin_location',
                };
            },
            processResults: function( data ) {
                var options = [];
                if ( data ) {
					$.each( data, function( index, text ) {
                        options.push( { id: text.address, text: text.address  } );
                    });
                }
                return {
                    results: options
                };
			}
		},
		initSelection: function( element, callback ){
			var selected = [];
			selected.push( { id: midtrans_params.location_selected, text: midtrans_params.location_selected } );
			return callback( selected );
		},
		minimumInputLength: 3
	});
});
