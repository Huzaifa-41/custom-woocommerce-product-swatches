jQuery(document).ready(function($) {

    // Handle Swatch Click
    $(document).on('click', '.linkyne-swatch', function() {
        var $this = $(this);
        
        if ($this.hasClass('disabled')) {
            return; // Do nothing if out of stock
        }

        var val = $this.data('value');
        var $wrapper = $this.closest('.linkyne-swatches-wrapper');
        var $select = $wrapper.next('select'); // The hidden Woo select

        // Update visual selection
        $wrapper.find('.linkyne-swatch').removeClass('selected');
        $this.addClass('selected');

        // Update hidden select and trigger Woo core JS
        $select.val(val).trigger('change');
    });

    // Listen to WooCommerce Core to handle available/out-of-stock options dynamically
    $(document).on('woocommerce_update_variation_values', function() {
        $('.linkyne-swatches-wrapper').each(function() {
            var $wrapper = $(this);
            var $select = $wrapper.next('select');

            // Temporarily mark all as disabled
            $wrapper.find('.linkyne-swatch').addClass('disabled');

            // Read available options from the hidden select and enable them
            $select.find('option').each(function() {
                var optionVal = $(this).val();
                if (optionVal !== '') {
                    $wrapper.find('.linkyne-swatch[data-value="' + optionVal + '"]').removeClass('disabled');
                }
            });
        });
    });

    // Handle Clear Selection button natively injected by Woo
    $(document).on('click', '.reset_variations', function() {
        $('.linkyne-swatch').removeClass('selected');
    });

});
