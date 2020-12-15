<?php

class PVWC_Function {

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

    }

    /**
     * @param null $id
     * @param null $meta_key
     * @return array|bool|mixed|void
     */
    function get_attribtue_meta( $id = null, $meta_key = null ) {
        global $pvwc_attr_meta;

        if( !is_array( $pvwc_attr_meta ) ) {
            $pvwc_attr_meta = get_option( 'pvwc_product_attr_meta' );
            !is_array( $pvwc_attr_meta ) ? $pvwc_attr_meta = [] : '';
        }

        if( !$id ) {
            return $pvwc_attr_meta;
        }

        if( isset( $pvwc_attr_meta[$id] ) ) {
            if( $meta_key ) {
                if( isset( $pvwc_attr_meta[$id][$meta_key] ) ) {
                    return $pvwc_attr_meta[$id][$meta_key];
                }
                return false;
            }
            return $pvwc_attr_meta[$id];
        }
    }

    /**
     * @param $filename
     * @return bool|string
     */
    function get_img_src( $filename ) {
        if( file_exists( PVWC_ASSET_URL . '/images/' . $filename ))
            return PVWC_ASSET_URL . '/images/' . $filename;
        return false;
    }

    /**
     * @param $id
     * @param $meta_key
     * @param $meta_value
     */
    function set_attribtue_meta( $id, $meta_key, $meta_value ) {
        $attr_meta = $this->get_attribtue_meta();
        $attr_meta[$id][$meta_key] = $meta_value;
        update_option( 'pvwc_product_attr_meta', $attr_meta );
    }
}

function PVWC_Function() {
    return PVWC_Function::instance();
}