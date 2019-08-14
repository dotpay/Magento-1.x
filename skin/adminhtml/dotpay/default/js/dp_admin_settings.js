// Avoid PrototypeJS conflicts, assign jQuery to $j instead of $
var $j = jQuery.noConflict();


$j(document).ready(function(){


$j("fieldset#payment_dotpay").prepend("<div id=\"dp_payment_info\"><br><hr style=\"height: 2px; background-color: #2eacce;\"><br><a href=\"https://dotpay.pl\" title=\"Dotpay\" target=\"_blank\"><img src='data:image/png;base64,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' width='65' height='19' alt='Dotpay' style='float: left; padding: 5px 20px 20px 0;'></a><b><h2> payment method:</h2></b></div>"); 



    $j("input#payment_dotpay_id").attr("pattern", "[0-9]{4,6}");
    $j("input#payment_dotpay_id").attr("maxlength", "6");


    $j("input#payment_dotpay_id").bind('keyup paste keydown', function(e) {
        if (/\D/g.test(this.value)) {
            // Filter non-digits from input value.
            this.value = this.value.replace(/\D/g, '');
        }
        });

    $j("input#payment_dotpay_pin").attr("maxlength", "32");
        
    $j("input#payment_dotpay_pin").bind('keyup paste keydown', function(e) {
            $j(this).val(function(_, v){
            return v.replace(/\s+/g, '');
        });
    });
    
		
});