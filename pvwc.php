<?php
/**
 * Plugin Name:       Advanced Product Variation Swatches for WooCommerce
 * Description:       Display product attributes as color swatch, button, advanced dropdown or image etc.
 * Author:            CyberCraft
 * Author URI:
 * Version:           1.0.1
 * Text Domain:       pvwc
 * Domain Path:       /languages/
 * License:           GPLv2 or later (license.txt)
 */

define('PVWC_NAME', 'Backend and Waitlist for WooCommerce');
define('PVWC_ROOT', dirname(__FILE__));
define('PVWC_PLUGIN_FILE', __FILE__);
define('PVWC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('PVWC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PVWC_PLUGIN_URL', plugins_url('/', __FILE__));
define('PVWC_ASSET_PATH', PVWC_PLUGIN_PATH . '/assets');
define('PVWC_ASSET_URL', PVWC_PLUGIN_URL . '/assets');

if( !function_exists( 'pri' ) ) {
    function pri( $data ) {
        echo '<pre>';print_r( $data ); echo '</pre>';
    }
}

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

class PVWC_Init {

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
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );
        $this->includes();
    }

    public function wp_enqueue_scripts() {
        wp_enqueue_style( 'pvwc-style', PVWC_ASSET_URL . '/css/app.css' );
    }

    public function includes() {
        foreach ( glob( PVWC_ROOT . '/inc/*.php' ) as $k => $filename ) {
            include_once $filename;
        }
    }
}

PVWC_Init::instance();