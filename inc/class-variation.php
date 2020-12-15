<?php

class PVWC_Variation {

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
        add_action( 'woocommerce_dropdown_variation_attribute_options_html', [ $this, 'variation_attribute_options' ], 10, 2 );
        add_action( 'wp_footer', [ $this, 'footer_js' ], 10000, 2 );
    }

    /**
     * Modify the default dropdown html
     * for variation in frontend
     *
     * @param $html
     * @param $args
     */
    function variation_attribute_options( $html, $args ) {
        $args = wp_parse_args(
            apply_filters( 'pvwc_dropdown_variation_attribute_options_args', $args ),
            array(
                'options'          => false,
                'attribute'        => false,
                'product'          => false,
                'selected'         => false,
                'name'             => '',
                'id'               => '',
                'class'            => '',
                'show_option_none' => __( 'Choose an option', 'woocommerce' ),
            )
        );

        //get product attribute type
        $attribute = PVWC_Attribute()->get_attribute_by_tax( $args['attribute'] );
        if( $attribute ) {
            $args['type'] = $attribute->type;
        }
        if( !isset( PVWC_Attribute()->get_attribute_types()[$args['type']] ) ) return $html;

        // Get selected value.
        if ( false === $args['selected'] && $args['attribute'] && $args['product'] instanceof WC_Product ) {
            $selected_key = 'attribute_' . sanitize_title( $args['attribute'] );
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            $args['selected'] = isset( $_REQUEST[ $selected_key ] ) ? wc_clean( wp_unslash( $_REQUEST[ $selected_key ] ) ) : $args['product']->get_variation_default_attribute( $args['attribute'] );
            // phpcs:enable WordPress.Security.NonceVerification.Recommended
        }

        $options               = $args['options'];
        $product               = $args['product'];
        $attribute             = $args['attribute'];
        $name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
        $id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
        $class                 = $args['class'];
        $show_option_none      = (bool) $args['show_option_none'];
        $show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __( 'Choose an option', 'woocommerce' ); // We'll do our best to hide the placeholder, but we'll need to show something when resetting options.

        if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
            $attributes = $product->get_variation_attributes();
            $options    = $attributes[ $attribute ];
        }

        $html  = '<select style="display: none;" id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';
        $html .= '<option value="">' . esc_html( $show_option_none_text ) . '</option>';

        if ( ! empty( $options ) ) {
            if ( $product && taxonomy_exists( $attribute ) ) {
                // Get terms if this is a taxonomy - ordered. We need the names too.
                $terms = wc_get_product_terms(
                    $product->get_id(),
                    $attribute,
                    array(
                        'fields' => 'all',
                    )
                );

                foreach ( $terms as $term ) {
                    if ( in_array( $term->slug, $options, true ) ) {
                        $html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ) ) . '</option>';
                    }
                }
            } else {
                foreach ( $options as $option ) {
                    // This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
                    $selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
                    $html    .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ) ) . '</option>';
                }
            }
        }

        $html .= '</select>';

        $html .= $this->render_attribute_options( $args, isset( $terms ) ? $terms : null, isset( $options ) ? $options : null );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo apply_filters( 'pvwc_dropdown_variation_attribute_options_html', $html, $args );
    }

    /**
     * Rendering custom attribute options
     * in frontend
     *
     * @param $args
     * @param null $terms
     * @param null $options
     * @return string
     */
    public function render_attribute_options( $args, $terms = null, $options = null ) {
        $attribute             = $args['attribute'];
        $id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
        ob_start();
        switch ( $args['type'] ) {
            case 'color':
                ?>
                <ul data-target_attribute="<?php echo $id; ?>" data-display_type="<?php echo $args['type']; ?>">
                    <?php
                    if( $terms ) {
                        foreach ( $terms as $term ) {
                            if ( in_array( $term->slug, $options, true ) ) {
                                ?>
                                <li data-value="<?php echo esc_attr( $term->slug ); ?>" <?php echo selected( sanitize_title( $args['selected'] ), $term->slug, false ); ?>>
                                    <a style="background: <?php echo PVWC_Term()->get_term_field_value( $term->term_id, $args['type'] ) ; ?>" href="javascript:" data-variation_name="<?php echo esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $args['attribute'], $args['product'] ) ); ?>">
                                    </a>
                                </li>
                                <?php
                            }
                        }
                    }
                    ?>
                </ul>
                <?php
                break;
            case 'button':
                ?>
                <ul data-target_attribute="<?php echo $id; ?>" data-display_type="<?php echo $args['type']; ?>">
                    <?php
                    if( $terms ) {
                        foreach ( $terms as $term ) {
                            if ( in_array( $term->slug, $options, true ) ) {
                                ?>
                                <li data-value="<?php echo esc_attr( $term->slug ); ?>" <?php echo selected( sanitize_title( $args['selected'] ), $term->slug, false ); ?>>
                                    <a href="javascript:" data-variation_name="<?php echo esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $args['attribute'], $args['product'] ) ); ?>">
                                        <?php echo esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $args['attribute'], $args['product'] ) ); ?>
                                    </a>
                                </li>
                                <?php
                            }
                        }
                    }
                    ?>
                </ul>
                <?php
                break;
            case 'advanced-select':
                ?>
                <div class="custom-select" data-target_attribute="<?php echo $id; ?>" data-display_type="<?php echo $args['type']; ?>">
                    <div class="select-selected" data-value=""><?php _e( 'Choose an option', 'pvwc' ); ?></div>
                    <div class="select-items select-hide">
                        <div data-value="">
                            <?php echo esc_html( apply_filters( 'pvwc-advanced_dropdown-select_default_label', 'Choose an option' ) ); ?>
                            <span class="stock_info"></span>
                        </div>
                        <?php
                        if( $terms ) {
                            foreach ( $terms as $term ) {
                                if ( in_array( $term->slug, $options, true ) ) {
                                    ?>
                                    <div data-value="<?php echo esc_attr( $term->slug ); ?>" <?php echo selected( sanitize_title( $args['selected'] ), $term->slug, false ); ?>>
                                        <?php echo esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $args['attribute'], $args['product'] ) ); ?>
                                        <span class="stock_info"></span>
                                    </div>
                                    <?php
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php
                break;
        }
        return ob_get_clean();
    }

    public function footer_js() {
        ?>
        <script>
            var $ = jQuery;
            $(document).ready(function () {
                setTimeout(function () {
                    pvwc_object.populate_custom_field_options();
                    pvwc_object.populate_custom_field_value();
                },100);
                //swatch,button
                $('[data-target_attribute] li[data-value]').click(function () {
                    //toggle active class
                    $(this).siblings().removeClass('active');
                    $(this).toggleClass('active');

                    pvwc_object.populate_dropdown_value( this );
                    pvwc_object.populate_custom_field_options();
                });

                //advanced-dropdown
                $('[data-target_attribute] div[data-value]').click(function (e) {
                    var parent = $(this).closest('[data-target_attribute]');

                    if( !$('.select-hide').length && !$(this).hasClass('select-selected') ) {
                        //toggle active class
                        $(this).siblings().removeClass('active');
                        $(this).toggleClass('active');
                        var default_val = $('[data-value=""]',parent).data('value');
                        var default_text = $('[data-value=""]:first',parent).text();

                        $('.select-selected',parent)
                            .attr('data-value', $('.active',parent).length ? $('.active',parent).data('value') : default_val )
                            .text( $('.active',parent).length ? $('.active',parent).text() : default_text );
                        //trigger target_attr
                        pvwc_object.populate_dropdown_value(this);
                        pvwc_object.populate_custom_field_options();
                    }
                    $('.select-items',parent).toggleClass('select-hide');

                });

                $('.reset_variations').click(function () {
                    pvwc_object.reset_all_custom_fields();
                });

                var pvwc_object = {
                    reset_all_custom_fields: function() {
                        $('[data-target_attribute]').each(function () {
                            $('li[data-value].active').trigger('click');
                            $('div[data-value].active').trigger('click');

                        });
                    },
                    populate_dropdown_value: function( elem ) {
                        var target_attr = $(elem).closest('[data-target_attribute]').data('target_attribute');
                        var val = $(elem).hasClass('active') ? $(elem).data('value') : '';
                        $('#' + target_attr).val(val).trigger('change');
                    },
                    populate_custom_field_options: function () {
                        //for color
                        $('[data-target_attribute]').each(function () {
                            var _this = this;
                            var target_attr = $(_this).data('target_attribute');

                            $('[data-value]',_this).hide();
                            $('#' + target_attr ).children('option').each(function () {
                                $('[data-value="' + $(this).val() + '"]',_this).show();
                            })
                        })
                    },
                    populate_custom_field_value: function () {
                        $('[data-target_attribute]').each(function () {
                            var _this = this;
                            var target_attr = $(_this).data('target_attribute');
                            var val = $('#' + target_attr).val();
                            $('[data-value="' + val + '"]:last',_this).addClass('active');
                            $('.select-selected[data-value]', _this).attr('data-value',val).text( $('#' + target_attr + ' option[value="' + val + '"]').text());
                        })
                    }
                }
            });
        </script>
    <?php
    }

}

function PVWC_Variation() {
    return PVWC_Variation::instance();
}

PVWC_Variation();