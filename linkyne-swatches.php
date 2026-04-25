<?php
/**
 * Plugin Name: Linkyne Variation Swatches
 * Description: Custom image and button swatches for WooCommerce variations.
 * Version: 2.2.0
 * Author: Linkyne
 * Author URL: linkyne.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 0. Include backend fixes
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/admin/linkyne-attribute-values-fix.php';
}

// 1. Register Custom Attribute Types (Image & Button)
add_filter( 'product_attributes_type_selector', 'linkyne_add_custom_attribute_types' );
function linkyne_add_custom_attribute_types( $types ) {
    $types['image']  = __( 'Image', 'linkyne' );
    $types['button'] = __( 'Button', 'linkyne' );
    return $types;
}

// 2. Add Image Upload Field to Taxonomy Term Screens
add_action( 'admin_init', 'linkyne_init_taxonomy_fields' );
function linkyne_init_taxonomy_fields() {
    $attribute_taxonomies = wc_get_attribute_taxonomies();
    if ( empty( $attribute_taxonomies ) ) return;

    foreach ( $attribute_taxonomies as $tax ) {
        $taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );
        
        // Only add image field if the type is 'image'
        if ( $tax->attribute_type === 'image' ) {
            add_action( "{$taxonomy_name}_add_form_fields", 'linkyne_add_term_image_field' );
            add_action( "{$taxonomy_name}_edit_form_fields", 'linkyne_edit_term_image_field', 10, 2 );
        }
    }
}

function linkyne_add_term_image_field( $taxonomy ) {
    wp_enqueue_media();
    ?>
    <div class="form-field term-group">
        <label for="linkyne_term_image"><?php _e( 'Attribute Image', 'linkyne' ); ?></label>
        <input type="hidden" id="linkyne_term_image" name="linkyne_term_image" class="custom_media_url" value="">
        <div id="category-image-wrapper"></div>
        <p>
            <input type="button" class="button button-secondary linkyne_media_button" id="linkyne_media_button" name="linkyne_media_button" value="<?php _e( 'Add Image', 'linkyne' ); ?>" />
            <input type="button" class="button button-secondary linkyne_media_remove" id="linkyne_media_remove" name="linkyne_media_remove" value="<?php _e( 'Remove Image', 'linkyne' ); ?>" style="display:none;" />
        </p>
    </div>
    <?php
    linkyne_media_uploader_script();
}

function linkyne_edit_term_image_field( $term, $taxonomy ) {
    wp_enqueue_media();
    $image_id = get_term_meta( $term->term_id, 'linkyne_term_image', true );
    $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="linkyne_term_image"><?php _e( 'Attribute Image', 'linkyne' ); ?></label></th>
        <td>
            <input type="hidden" id="linkyne_term_image" name="linkyne_term_image" value="<?php echo esc_attr( $image_id ); ?>">
            <div id="category-image-wrapper">
                <?php if ( $image_url ) : ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" style="max-width:100px; height:auto; margin-bottom:10px;" />
                <?php endif; ?>
            </div>
            <p>
                <input type="button" class="button button-secondary linkyne_media_button" id="linkyne_media_button" name="linkyne_media_button" value="<?php _e( 'Add/Change Image', 'linkyne' ); ?>" />
                <input type="button" class="button button-secondary linkyne_media_remove" id="linkyne_media_remove" name="linkyne_media_remove" value="<?php _e( 'Remove Image', 'linkyne' ); ?>" <?php echo ! $image_id ? 'style="display:none;"' : ''; ?> />
            </p>
        </td>
    </tr>
    <?php
    linkyne_media_uploader_script();
}

function linkyne_media_uploader_script() {
    ?>
    <script>
    jQuery(document).ready(function($){
        var custom_uploader;
        $('.linkyne_media_button').click(function(e) {
            e.preventDefault();
            if (custom_uploader) { custom_uploader.open(); return; }
            custom_uploader = wp.media.frames.file_frame = wp.media({
                title: 'Choose Image',
                button: { text: 'Choose Image' },
                multiple: false
            });
            custom_uploader.on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                $('#linkyne_term_image').val(attachment.id);
                $('#category-image-wrapper').html('<img src="' + attachment.url + '" style="max-width:100px; height:auto; margin-bottom:10px;" />');
                $('.linkyne_media_remove').show();
            });
            custom_uploader.open();
        });
        $('.linkyne_media_remove').click(function(e){
            e.preventDefault();
            $('#linkyne_term_image').val('');
            $('#category-image-wrapper').html('');
            $(this).hide();
        });
    });
    </script>
    <?php
}

add_action( 'created_term', 'linkyne_save_term_image', 10, 3 );
add_action( 'edit_term', 'linkyne_save_term_image', 10, 3 );
function linkyne_save_term_image( $term_id, $tt_id, $taxonomy ) {
    if ( isset( $_POST['linkyne_term_image'] ) ) {
        update_term_meta( $term_id, 'linkyne_term_image', sanitize_text_field( $_POST['linkyne_term_image'] ) );
    }
}

// 3. Frontend: Replace Dropdown with Swatches
add_filter( 'woocommerce_dropdown_variation_attribute_options_html', 'linkyne_render_swatches_html', 10, 2 );
function linkyne_render_swatches_html( $html, $args ) {
    $options   = $args['options'];
    $product   = $args['product'];
    $attribute = $args['attribute'];
    $name      = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
    $id        = $args['id'] ? $args['id'] : sanitize_title( $attribute );

    if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
        $attributes = $product->get_variation_attributes();
        $options    = $attributes[ $attribute ];
    }

    $tax = wc_get_attribute_taxonomies();
    $attr_type = 'select';
    foreach ( $tax as $t ) {
        if ( wc_attribute_taxonomy_name( $t->attribute_name ) === $attribute ) {
            $attr_type = $t->attribute_type;
            break;
        }
    }

    if ( in_array( $attr_type, ['image', 'button'] ) ) {
        // Hide standard select
        $html = '<select id="' . esc_attr( $id ) . '" class="" name="' . esc_attr( $name ) . '" data-attribute_name="' . esc_attr( wc_variation_attribute_name( $attribute ) ) . '" style="display:none;">';
        $html .= '<option value="">' . __( 'Choose an option', 'woocommerce' ) . '</option>';

        $swatches_html = '<div class="linkyne-swatches-wrapper type-' . esc_attr( $attr_type ) . '">';

        if ( ! empty( $options ) ) {
            if ( $product && taxonomy_exists( $attribute ) ) {
                $terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );
                foreach ( $terms as $term ) {
                    if ( in_array( $term->slug, $options, true ) ) {
                        // Populate hidden select
                        $html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
                        
                        // Generate Swatch
                        $selected_class = ( sanitize_title( $args['selected'] ) === $term->slug ) ? 'selected' : '';
                        
                        if ( $attr_type === 'image' ) {
                            $image_id = get_term_meta( $term->term_id, 'linkyne_term_image', true );
                            $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src();
                            $swatches_html .= '<div class="linkyne-swatch swatch-image ' . $selected_class . '" data-value="' . esc_attr( $term->slug ) . '" title="' . esc_attr( $term->name ) . '">';
                            $swatches_html .= '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $term->name ) . '">';
                            $swatches_html .= '<span class="swatch-label">' . esc_html( $term->name ) . '</span>';
                            $swatches_html .= '</div>';
                        } else {
                            $swatches_html .= '<div class="linkyne-swatch swatch-button ' . $selected_class . '" data-value="' . esc_attr( $term->slug ) . '">';
                            $swatches_html .= '<span>' . esc_html( $term->name ) . '</span>';
                            $swatches_html .= '</div>';
                        }
                    }
                }
            }
        }
        $html .= '</select>';
        $swatches_html .= '</div>';
        $html = $swatches_html . $html;
    }

    return $html;
}

// 4. Enqueue Frontend Scripts and Styles
add_action( 'wp_enqueue_scripts', 'linkyne_swatches_assets' );
function linkyne_swatches_assets() {
    if ( is_product() ) {
        wp_enqueue_style( 'linkyne-swatches-css', plugin_dir_url( __FILE__ ) . 'assets/swatches.css', array(), '1.0.0' );
        wp_enqueue_script( 'linkyne-swatches-js', plugin_dir_url( __FILE__ ) . 'assets/swatches.js', array( 'jquery', 'wc-add-to-cart-variation' ), '1.0.0', true );
    }
}
