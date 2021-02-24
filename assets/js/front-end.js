(function ($) {


    $(document).on('wc_variation_form', function (e) {

        var $form = $(e.target);

        if ($(document).find('.dynamic-pricing-table-variation').length) {
            if ($form.data('has_dynamic_pricing_table') !== 1) {
                $form.data('has_dynamic_pricing_table', 1);


                $form.on('show_variation', function (e, variation) {

                    $form.find('table.dynamic-pricing-table-variation').hide();
                    $form.find('table.dynamic-pricing-table-variation-' + variation.variation_id).show();


                });

                $form.on('reset_data', function() {
                    $form.find('table.dynamic-pricing-table-variation').hide();
                });

                setTimeout(function () {
                    $form.trigger('check_variations');
                }, 50);
            }
        }


    });


})(jQuery);
