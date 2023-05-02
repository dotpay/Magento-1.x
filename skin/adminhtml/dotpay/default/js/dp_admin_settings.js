// Avoid PrototypeJS conflicts, assign jQuery to $j instead of $
var $j = jQuery.noConflict();


$j(document).ready(function(){


$j("fieldset#payment_dotpay").prepend("<div id=\"dp_payment_info\"><br><hr style=\"height: 2px; background-color: #2eacce;\"><br><a href=\"https://dotpay.pl\" title=\"Przelewy24\" target=\"_blank\"><img src=' data:image/png;base64,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' width='140px' height='19px' alt='Dotpay/Przelewy24' style='float: left; padding: 5px 20px 20px 0;'></a><b><h2> payment method:</h2></b></div>"); 



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