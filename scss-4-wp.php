<?php
/**
* Plugin Name: SCSS-4-WP
* Plugin URI: https://github.com/Field-Of-Code/scss-4-wp
* Description: Compiles scss files live on WordPress.
* Version: 1.0.1
* Author: Field Of Code
* Author URI: https://fieldofcode.com
* License: GPLv3
*/


namespace Scss4Wp;


require __DIR__  . '/vendor/autoload.php';

class Plugin {

    const ID = 'scss4wp';
    const NAME = 'SCSS-4-WP';
    const VERSION = '1.0.1';

    /**
     * The single instance of this class
     *
     * @since 1.0.0
     * @var   SCSS4WP
    */
    protected static $instance;

	/**
	 * Get main plugin instance.
	 *
	 * @since 1.0.0
	 * @see   instance()
	 *
	 * @return SCSS4WP
	*/
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Spin up plugin
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->define_vars();
		$this->init();
	}

	protected function define_vars() {
        /*
         * 1. PLUGIN GLOBAL VARIABLES
         */

        // Plugin Paths
        if (!defined('SCSS4WP_PLUGIN_NAME'))
          define('SCSS4WP_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__)), '/'));

        if (!defined('SCSS4WP_PLUGIN_DIR'))
          define('SCSS4WP_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . SCSS4WP_PLUGIN_NAME);

        if (!defined('SCSS4WP_PLUGIN_URL'))
          define('SCSS4WP_PLUGIN_URL', WP_PLUGIN_URL . '/' . SCSS4WP_PLUGIN_NAME);

        // Plugin Version
        if (!defined('SCSS4WP_VERSION_KEY'))
          define('SCSS4WP_VERSION_KEY', 'scss4wp_version');

        if (!defined('SCSS4WP_VERSION_NUM'))
          define('SCSS4WP_VERSION_NUM', '1.0.0');

        // Add version to options table
        if ( get_option( SCSS4WP_VERSION_KEY ) !== false ) {

          // The option already exists, so we just update it.
          update_option( SCSS4WP_VERSION_KEY, SCSS4WP_VERSION_NUM );

        }
    }

    private function init() {
        $core = new Core;
        if(is_admin()) {
            $admin = new Admin;
        }
    }

    public static function base_folder_name_to_path($name) {
        $possible_directories = array(
            'Uploads Directory' => wp_get_upload_dir()['basedir'],
        );
        if(get_stylesheet_directory() === get_template_directory()){
            $possible_directories['Current Theme'] = get_stylesheet_directory();
        } else{
            $possible_directories['Parent Theme'] = get_template_directory();
            $possible_directories['Child Theme'] = get_stylesheet_directory();
        }
        if(array_key_exists($name, $possible_directories)){
            return $possible_directories[$name];
        }
    }

    public function do_activate() {
		do_action( 'scss4wp_activate' );
	}
}

/**
 * Get instance of main plugin class
 *
 * @since 1.0.0
 *
 * @return Plugin
 */
function scss4wp_instance() {
	return Plugin::instance();
}

// Instantiate plugin wrapper class.
$scss4wp = scss4wp_instance();

// Register activation/deactivation stuff.
register_activation_hook( __FILE__, [ $scss4wp, 'do_activate' ] );
