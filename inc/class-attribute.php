<?php

class PVWC_Attribute {

    /**
     * Instance
     *
     * @since 1.0.0
     *
     * @access private
     * @static
     */
    private static $_instance = null;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @since 1.0.0
     *
     * @access public
     * @static
     *
     * @return ${ClassName} An instance of the class.
     */
    public static function instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;

    }

    public function __construct() {
        add_filter( 'product_attributes_type_selector', [ $this, 'add_custom_attribute_types' ] );
        add_action( 'woocommerce_product_option_terms', [ $this, 'add_custom_selector_in_admin'], 10, 3 );
    }

    /**
     * Adding custm attribute
     *
     * @param $types
     * @return array
     */
    function add_custom_attribute_types( $types ) {
        $types = array_merge( $types, $this->get_attribute_types() );
        return $types;
    }

    /**
     * @param $attribute_taxonomy
     * @param $i
     * @param $attribute
     */
    function add_custom_selector_in_admin ( $attribute_taxonomy, $i, $attribute ) {
        if ( !in_array( $attribute_taxonomy->attribute_type, array_keys( $this->get_attribute_types() ) ) ) return;
        $attribute_types = wc_get_attribute_types();

        if ( ! array_key_exists( $attribute_taxonomy->attribute_type, $attribute_types ) ) {
            $attribute_taxonomy->attribute_type = 'select';
        }

        if (  in_array( $attribute_taxonomy->attribute_type, array_keys( $this->get_attribute_types() ) ) ) {
            ?>
            <select multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select terms', 'woocommerce' ); ?>" class="multiselect attribute_values wc-enhanced-select" name="attribute_values[<?php echo esc_attr( $i ); ?>][]">
                <?php
                $args      = array(
                    'orderby'    => ! empty( $attribute_taxonomy->attribute_orderby ) ? $attribute_taxonomy->attribute_orderby : 'name',
                    'hide_empty' => 0,
                );
                $all_terms = get_terms( $attribute->get_taxonomy(), apply_filters( 'woocommerce_product_attribute_terms', $args ) );
                if ( $all_terms ) {
                    foreach ( $all_terms as $term ) {
                        $options = $attribute->get_options();
                        $options = ! empty( $options ) ? $options : array();
                        echo '<option value="' . esc_attr( $term->term_id ) . '"' . wc_selected( $term->term_id, $options ) . '>' . esc_attr( apply_filters( 'woocommerce_product_attribute_term_name', $term->name, $term ) ) . '</option>';
                    }
                }
                ?>
            </select>
            <button class="button plus select_all_attributes"><?php esc_html_e( 'Select all', 'woocommerce' ); ?></button>
            <button class="button minus select_no_attributes"><?php esc_html_e( 'Select none', 'woocommerce' ); ?></button>
            <button class="button fr plus add_new_attribute"><?php esc_html_e( 'Add new', 'woocommerce' ); ?></button>
            <?php
        }
    }

    /**
     * Custom attribute
     *
     * @return array
     */
    function get_attribute_types() {
        $types = [];
        //$types['image'] = __( 'Image', 'pvwc' );
        $types['button'] = __( 'Button', 'pvwc' );
        $types['color'] = __( 'Color', 'pvwc' );
        $types['advanced-select'] = __( 'Advanced Select', 'pvwc' );
        return $types;
    }

    /**
     * Get attribute by taxonomy
     *
     * @param $taxonomy
     * @return bool|null|stdClass
     */
    function get_attribute_by_tax( $taxonomy ) {
        $attribute_id = wc_attribute_taxonomy_id_by_name( substr( $taxonomy, 3 ) );
        $attribute = wc_get_attribute( $attribute_id );

        if( $attribute ) {
            return $attribute;
        }
        return false;
    }
}

function PVWC_Attribute() {
    return PVWC_Attribute::instance();
}

PVWC_Attribute();