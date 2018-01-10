(function ($) {


    $(document).on('wc_variation_form', function (e) {

        var $form = $(e.target);

        if ($form.data('has_dynamic_pricing_table') !== 1) {
            $form.data('has_dynamic_pricing_table', 1);


            $form.on('show_variation', function (e, variation) {

                $form.find('table.dynamic-pricing-table').hide();
                $form.find('table.dynamic-pricing-table-variation-' + variation.variation_id).show();


            });

            setTimeout( function() {
                $form.trigger( 'check_variations' );
            }, 50 );
        }


    });


})(jQuery);