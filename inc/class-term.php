<?php

class PVWC_Term {

    private $taxonomy = null;
    protected $display_type_meta = 'pvwc_display_type';

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
        add_action( 'delete_term', array( $this, 'delete_term' ), 5, 4 );

        // Add form
        add_action('admin_head',[ $this, 'init_hooks'] );

        add_action( "created_term", array( $this, 'save' ), 10, 3 );
        add_action( "edit_term", array( $this, 'save' ), 10, 3 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_footer', [ $this, 'admin_js' ] );
    }

    public function init_hooks() {
        if( get_current_screen()->post_type !== 'product' ) return;
        $this->taxonomy = get_current_screen()->taxonomy;
        add_action( "{$this->taxonomy}_add_form_fields", array( $this, 'add' ) );
        add_action( "{$this->taxonomy}_edit_form_fields", array( $this, 'edit' ), 10 );
    }

    public function add() {
        $this->edit();
    }

    /**
     * @param $term
     * @return mixed
     */
    public function get_display_type( $term ) {
        return get_term_meta( $term->term_id, $this->display_type_meta, true );
    }

    /**
     * @param null $term
     */
    public function edit( $term = null ) {
        if( !$this->taxonomy ) return;
        $attribute = PVWC_Attribute()->get_attribute_by_tax( $this->taxonomy );

        if( $attribute ) {
            echo '<tr>';
            $field = $this->get_fields()[$attribute->type];
            ?>
            <td>
                <label for="<?php echo $field['id'] ?>"><?php echo $field['label']; ?></label>
            </td>
            <?php
            switch ( $attribute->type ) {
                case 'color':
                    $field['value'] = $term ? get_term_meta( $term->term_id, $field[ 'id' ], true ) : '';
                    ?>
                    <td>
                        <input name="<?php echo $field[ 'id' ]; ?>" id="<?php echo $field[ 'id' ] ?>" type="text" class="pvwc-color-picker <?php echo $field['class']; ?>" value="<?php echo $field[ 'value' ] ?>" data-default-color="<?php echo $field[ 'value' ] ?>" size="<?php echo $field[ 'size' ] ?>" <?php echo $field[ 'required' ] . $field[ 'placeholder' ] ?>>
                    </td>
                    <?php
                    break;
                case 'image':
                    ?>
                    <td>
                        <div class="meta-image-field-wrapper">
                            <div class="image-preview">
                                <img data-placeholder="<?php echo esc_url( PVWC_Function()->get_img_src( 'placeholder.png' ) ); ?>" src="<?php echo esc_url( PVWC_Function()->get_img_src( $field[ 'value' ] ) ); ?>" width="60px" height="60px"/>
                            </div>
                            <div class="button-wrapper">
                                <input type="hidden" id="<?php echo $field[ 'id' ] ?>" name="<?php echo $field[ 'id' ] ?>" value="<?php echo esc_attr( $field[ 'value' ] ) ?>"/>
                                <button type="button" class="wvs_upload_image_button button button-primary button-small"><?php esc_html_e( 'Upload / Add image', 'woo-variation-swatches' ); ?></button>
                                <button type="button" style="<?php echo( empty( $field[ 'value' ] ) ? 'display:none' : '' ) ?>" class="wvs_remove_image_button button button-danger button-small"><?php esc_html_e( 'Remove image', 'woo-variation-swatches' ); ?></button>
                            </div>
                        </div>
                    </td>
                    <?php
                case 'advanced-select':
                    break;
                default:
                    break;
            }
            echo '</tr>';
        }
    }

    function get_fields() {
        return apply_filters( 'pvwc_attribute_term_field', [
            'color' => [
                'id' => 'pvwc_color',
                'class' => 'pvwc_color',
                'value' => '',
                'required' => true,
                'size' => '',
                'placeholder' => '',
                'label' => __( 'Color', 'pvwc' )
            ],
            'image' => [
                'id' => 'pvwc_image',
                'class' => 'pvwc_image',
                'value' => '',
                'required' => false,
                'size' => '',
                'placeholder' => '',
                'label' => __( 'Image', 'pvwc' )
            ],
            'advanced-select' => []
        ] );
    }

    /**
     * @param $term_id
     * @param string $tt_id
     * @param string $taxonomy
     */
    public function save( $term_id, $tt_id = '', $taxonomy = '' ) {
        $attribute = PVWC_Attribute()->get_attribute_by_tax( $taxonomy );

        if( $attribute && isset( $attribute->type ) ) {
            if( isset( $_POST[$this->display_type_meta] ) ) {
                $display_type_meta = sanitize_text_field( $_POST[$this->display_type_meta] );
                update_term_meta( $term_id, $this->display_type_meta, $display_type_meta );
            }

            if( isset( $_POST[$this->get_fields(){$attribute->type}['id']] ) ) {
                update_term_meta( $term_id, $this->get_fields(){$attribute->type}['id'], sanitize_text_field( $_POST[$this->get_fields(){$attribute->type}['id']] ) );
            }

        }
    }

    /**
     * @param $term_id
     * @param $type
     */
    function get_term_field_value( $term_id, $type ) {
        return get_term_meta( $term_id,$this->get_fields(){$type}['id'], true );
    }

    /**
     * @param $term_id
     * @param $tt_id
     * @param $taxonomy
     * @param $deleted_term
     */
    public function delete_term( $term_id, $tt_id, $taxonomy, $deleted_term ) {
        global $wpdb;

        $term_id = absint( $term_id );
        if ( $term_id and $taxonomy == $this->taxonomy ) {
            $wpdb->delete( $wpdb->termmeta, array( 'term_id' => $term_id ), array( '%d' ) );
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
    }

    public function admin_js() {
        global $pagenow;
        $screen = get_current_screen();
        if( !in_array( $pagenow, array( 'term.php','edit-tags.php' )) ) return;
        if( $screen->post_type !== 'product') return;
        if( isset( $screen->taxonomy ) ) {
            ?>
            <script>
                var $ = jQuery;
                $(document).ready(function () {
                    //color picker
                    $('.pvwc-color-picker').wpColorPicker();

                    //image uploader
                    $(document).on('click', 'button.wvs_upload_image_button', this.AddImage);
                    $(document).on('click', 'button.wvs_remove_image_button', this.RemoveImage);

                    var term_obj = {
                        AddImage: function (event) {
                            console.log(wp);
                            var _this = this;

                            event.preventDefault();
                            event.stopPropagation();

                            var file_frame = void 0;

                            if (typeof wp !== 'undefined' && wp.media && wp.media.editor) {

                                // If the media frame already exists, reopen it.
                                if (file_frame) {
                                    file_frame.open();
                                    return;
                                }

                                // Create the media frame.
                                file_frame = wp.media.frames.select_image = wp.media({
                                    title: WVSPluginObject.media_title,
                                    button: {
                                        text: WVSPluginObject.button_title
                                    },
                                    multiple: false
                                });

                                // When an image is selected, run a callback.
                                file_frame.on('select', function () {
                                    var attachment = file_frame.state().get('selection').first().toJSON();

                                    if ($.trim(attachment.id) !== '') {

                                        var url = typeof attachment.sizes.thumbnail === 'undefined' ? attachment.sizes.full.url : attachment.sizes.thumbnail.url;

                                        $(_this).prev().val(attachment.id);
                                        $(_this).closest('.meta-image-field-wrapper').find('img').attr('src', url);
                                        $(_this).next().show();
                                    }
                                    //file_frame.close();
                                });

                                // When open select selected
                                file_frame.on('open', function () {

                                    // Grab our attachment selection and construct a JSON representation of the model.
                                    var selection = file_frame.state().get('selection');
                                    var current = $(_this).prev().val();
                                    var attachment = wp.media.attachment(current);
                                    attachment.fetch();
                                    selection.add(attachment ? [attachment] : []);
                                });

                                // Finally, open the modal.
                                file_frame.open();
                            }
                        },
                        RemoveImage: function (event) {

                            event.preventDefault();
                            event.stopPropagation();

                            var placeholder = $(this).closest('.meta-image-field-wrapper').find('img').data('placeholder');
                            $(this).closest('.meta-image-field-wrapper').find('img').attr('src', placeholder);
                            $(this).prev().prev().val('');
                            $(this).hide();
                            return false;
                        }
                    }
                });
            </script>
            <?php
        }
    }
}

function PVWC_Term() {
    return PVWC_Term::instance();
}

PVWC_Term();