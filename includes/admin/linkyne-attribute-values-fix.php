<?php
/**
 * Linkyne Swatches Fix: Completes the backend 'Values' field rendering for custom attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Hook into WooCommerce's specific action for custom attribute types
add_action( 'woocommerce_product_option_terms', 'linkyne_render_custom_attribute_terms', 10, 3 );

function linkyne_render_custom_attribute_terms( $attribute_taxonomy, $i, $attribute ) {
    
    // Ensure we are only modifying our specific custom types ('image' and 'button')
    if ( ! in_array( $attribute_taxonomy->attribute_type, ['image', 'button'] ) ) {
        return;
    }

    // CORRECTED: Get the exact taxonomy slug (e.g., 'pa_size', 'pa_frame_type')
    $taxonomy_slug = $attribute->get_name();
    $name          = 'attribute_values[' . $i . '][]';
    $values        = $attribute->get_options(); // Array of currently selected term IDs
    
    // Ensure $values is always an array to prevent errors
    if ( empty( $values ) || ! is_array( $values ) ) {
        $values = array();
    }

    $options = '';

    // Retrieve ONLY terms for this specific attribute taxonomy
    $all_terms = get_terms( array(
        'taxonomy'   => $taxonomy_slug,
        'hide_empty' => false,
    ) );

    // Loop through all terms and mark the saved ones as 'selected'
    if ( ! is_wp_error( $all_terms ) && ! empty( $all_terms ) ) {
        foreach ( $all_terms as $term ) {
            $is_selected = in_array( $term->term_id, $values ) ? 'selected="selected"' : '';
            $options .= '<option value="' . esc_attr( $term->term_id ) . '" ' . $is_selected . '>' . esc_html( $term->name ) . '</option>';
        }
    }

    // Output the standard WooCommerce multi-select box
    echo '<select multiple="multiple" data-placeholder="' . esc_attr__( 'Select terms', 'woocommerce' ) . '" class="wc-product-attribute-value-select wc-enhanced-select" name="' . esc_attr( $name ) . '" data-taxonomy="' . esc_attr( $taxonomy_slug ) . '">';
        echo $options;
    echo '</select>';

    // Output the standard "Add new" / "Select all" / "Select none" buttons
    echo '<button class="button plus add_new_attribute_value" data-taxonomy="' . esc_attr( $taxonomy_slug ) . '">' . esc_html__( 'Add new', 'woocommerce' ) . '</button>';
    echo '<button class="button select_all_attributes">' . esc_html__( 'Select all', 'woocommerce' ) . '</button>';
    echo '<button class="button select_no_attributes">' . esc_html__( 'Select none', 'woocommerce' ) . '</button>';
}
