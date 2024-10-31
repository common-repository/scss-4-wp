<?php
/**
 *
 * @package scss-4-wp
 * @link https://fieldofcode.com
 * @license restricted
 * @author Field Of Code
 * @copyright Field Of Code, 2022
 *
 */


namespace Scss4Wp;

use ScssPhp\ScssPhp\OutputStyle;

class Admin extends Plugin {

    public function __construct() {
        $this->set_vars();
        add_action( 'admin_init', array( $this, 'init' ) );
        add_action( 'admin_menu', array( $this, 'settings_page' ) );
        add_action('admin_notices', array($this, 'settings_error'));
    }

    private function set_vars() {
        $this->options = get_option('scss4wp_options');


    }

    public function settings_error() {
        $base_compiling_folder = isset($this->options['base_compiling_folder']) ? parent::base_folder_name_to_path($this->options['base_compiling_folder']) : '';
        $scss_dir_setting = isset($this->options['scss_dir']) ? $this->options['scss_dir'] : '';
        $css_dir_setting = isset($this->options['css_dir']) ? $this->options['css_dir'] : '';
        if( $scss_dir_setting == false || $css_dir_setting == false ) {
            echo '<div class="notice notice-error">
              <p><strong>SCSS-4-WP:</strong> requires both directories be specified. <a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=scss4wp_options">Please update your settings.</a></p>
              </div>';
        } elseif(empty($base_compiling_folder)) {
            echo '<div class="notice notice-error">
              <p><strong>SCSS-4-WP:</strong> requires valid base directory be specified. <a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=scss4wp_options">Please update your settings.</a></p>
              </div>';
        } elseif(!is_dir($base_compiling_folder . $scss_dir_setting)) {
            echo '<div class="notice notice-error">
              <p><strong>SCSS-4-WP:</strong> The SCSS directory does not exist (' . $base_compiling_folder . $scss_dir_setting . '). Please create the directory or <a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=scss4wp_options">update your settings.</a></p>
              </div>';
        } elseif(!is_dir($base_compiling_folder . $css_dir_setting)) {
            echo '<div class="notice notice-error">
              <p><strong>SCSS-4-WP:</strong> The CSS directory does not exist (' . $base_compiling_folder . $css_dir_setting . '). Please create the directory or <a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=scss4wp_options">update your settings.</a></p>
              </div>';
        }
    }

    public function init() {
        register_setting(
            'scss4wp_options_group',
            'scss4wp_options',
            array(
                'type' => 'string',
                'description' => 'Packaged settings for SCSS-4-WP',
                'show_in_rest' => false,
                'sanitize_callback' => array( $this, 'sanitize' )
            )
        );

        $base_folder_options = array(
            'Uploads Directory' => 'Uploads Directory',
        );
        if(get_stylesheet_directory() === get_template_directory()){
            $base_folder_options['Current Theme'] = 'Current Theme';
        }else{
            $base_folder_options['Parent Theme'] = 'Parent Theme';
            $base_folder_options['Child Theme'] = 'Child Theme';
        }

        //Paths
        add_settings_section( 'scss4wp_paths_section', 'Configure Paths', array( $this, 'paths_info' ), 'scss4wp_options');
        add_settings_field('scss4wp_base_folder', 'Base Location', array( $this, 'input_select_callback' ), 'scss4wp_options', 'scss4wp_paths_section', array(
                'name' => 'base_compiling_folder',
                'options' => apply_filters( 'scss4wp_base_folder_options', $base_folder_options),
            )
        );
        add_settings_field('scss4wp_scss_dir', 'SCSS Location', array( $this, 'input_text_callback' ), 'scss4wp_options', 'scss4wp_paths_section', array(                                 // args
                'name' => 'scss_dir',
            )
        );
        add_settings_field('scss4wp_css_dir', 'CSS Location', array( $this, 'input_text_callback' ), 'scss4wp_options', 'scss4wp_paths_section', array(                                 // args
                'name' => 'css_dir',
            )
        );

        //Configure
        add_settings_section( 'scss4wp_compile_section', 'Compiling Options', array( $this, 'compile_info' ), 'scss4wp_options');
        add_settings_field('scss4wp_compile_mode', 'Compiling Mode', array( $this, 'input_select_callback' ), 'scss4wp_options', 'scss4wp_compile_section', array(                                 // args
                'name' => 'compiling_options',
                'options' => apply_filters( 'scss4wp_compiling_modes',
                    array(
                        OutputStyle::COMPRESSED => ucfirst(OutputStyle::COMPRESSED),
                        OutputStyle::EXPANDED   => ucfirst(OutputStyle::EXPANDED),
                    )
                )
            )
        );
        add_settings_field('scss4wp_sourcemap_mode', 'Source Map Mode', array( $this, 'input_select_callback' ), 'scss4wp_options', 'scss4wp_compile_section', array(                                 // args
                'name' => 'sourcemap_options',
                'options' => apply_filters( 'scss4wp_sourcemap_modes',
                    array(
                        'SOURCE_MAP_NONE'   => 'None',
                        'SOURCE_MAP_INLINE' => 'Inline',
                        'SOURCE_MAP_FILE' => 'File'
                    )
                )
            )
        );
        add_settings_field('scss4wp_error_display', 'Error Display', array( $this, 'input_select_callback' ), 'scss4wp_options', 'scss4wp_compile_section', array(                                 // args
                'name' => 'errors',
                'options' => apply_filters( 'scss4wp_error_diplay',
                    array(
                        'show'           => 'Show in Header',
                        'show-logged-in' => 'Show to Logged In Users',
                        'hide'           => 'Print to Log',
                    )
                )
            )
        );
        //Enqueue
        add_settings_section( 'scss4wp_enqueue_section', 'Enqueuing Options', array( $this, 'enqueue_info' ), 'scss4wp_options');
        add_settings_field('Enqueue Stylesheets', 'Enqueue Stylesheets', array( $this, 'input_checkbox_callback' ), 'scss4wp_options', 'scss4wp_enqueue_section', array(                                     // args
                'name' => 'enqueue'
            )
        );
        //Developers
        add_settings_section( 'scss4wp_development_section', 'Development Settings', '', 'scss4wp_options');
        add_settings_field('scss4wp_scss_always_recompile', 'Always Recompile', array( $this, 'input_checkbox_callback' ), 'scss4wp_options', 'scss4wp_development_section', array(                                     // args
                'name' => 'always_recompile',
            )
        );
    }

    /**
    * Add options page
    */
    public function settings_page() {
        // This page will be under "Settings"
        add_options_page(
            'Settings',
            'SCSS-4-WP',
            'manage_options',
            'scss4wp_options',
            array( $this, 'create_admin_page' )
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h2>SCSS-4-WP Settings</h2>
            <p>
              <span class="version">Version <em><?php echo SCSS4WP_VERSION_NUM; ?></em>
              <br/>
              <span class="author">By: <a href="https://fieldofcode.com" target="_blank">Field Of Code</a></span>
              <br/>
              <span class="repo">Help & Issues: <a href="https://fieldofcode.com/contact" target="_blank">Contact Us</a></span>
            </p>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'scss4wp_options_group' );
                do_settings_sections( 'scss4wp_options' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }


    /**
     * Print the Section text
     */
    public function paths_info() {
        print 'Location of your SCSS/CSS folders. Folders must be nested under your <b>Base Location</b> and start with <code>/</code>.' .
            '</br>Examples: <code>/custom-scss/</code> and <code>/custom-css/</code>' .
            '</br><b>Caution</b> updating some themes or plugins will delete the custom SCSS-4-WP Base Location when nested.';
    }
    public function compile_info() {
        print 'Choose how you would like SCSS and source maps to be compiled and how you would like the plugin to handle errors';
    }
    public function enqueue_info() {
        print 'SCSS-4-WP can enqueue your css stylesheets in the header automatically.';
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {
      foreach( ['scss_dir', 'css_dir'] as $dir ){
        if( !empty( $input[$dir] ) ) {
          $input[$dir] = sanitize_text_field( $input[$dir] );

          // Add a trailing slash if not already present
          if(substr($input[$dir], -1) != '/'){
            $input[$dir] .= '/';
          }
        }
      }

      return $input;
    }

    /**
    * Text Fields' Callback
    */
    public function input_text_callback( $args ) {
        printf(
            '<input type="text" id="%s" name="scss4wp_options[%s]" value="%s" />',
            esc_attr( $args['name'] ), esc_attr( $args['name'] ), esc_attr( isset($this->options[$args['name']]) ? $this->options[$args['name']] : '' )
        );
    }

    /**
    * Select Boxes' Callbacks
    */
    public function input_select_callback( $args ) {

        $html = sprintf( '<select id="%s" name="scss4wp_options[%s]">', esc_attr( $args['name'] ), esc_attr( $args['name'] ) );
        foreach( $args['options'] as $value => $title ) {
            $html .= '<option value="' . esc_attr( $value ) . '"' . selected( isset($this->options[esc_attr( $args['name'] )]) ? $this->options[esc_attr( $args['name'] )] : '', esc_attr( $value ), false) . '>' . esc_attr( $title ) . '</option>';
        }
        $html .= '</select>';

        echo wp_kses($html, array( 'select' => array('id' => array(), 'name' => array()), 'option' => array('value' => array(), 'selected' => array())));
    }

    /**
    * Checkboxes' Callbacks
    */
    public function input_checkbox_callback( $args ) {
        $html = "";
        $option_name = esc_attr( $args['name']);
        if($option_name == 'always_recompile' && defined('WP_SCSS_ALWAYS_RECOMPILE') && WP_SCSS_ALWAYS_RECOMPILE){
            $html .= '<input type="checkbox" id="' . $option_name . '" name="scss4wp_options[' . $option_name . ']" value="1"' . checked( 1, isset( $this->options[$option_name] ) ? $this->options[$option_name] : 1, false ) . ' disabled=disabled/>';
            $html .= '<label for="' . $option_name . '">Currently overwritten by constant <code>WP_SCSS_ALWAYS_RECOMPILE</code></label>';
        }else{
            $html .= '<input type="checkbox" id="' . $option_name . '" name="scss4wp_options[' . $option_name . ']" value="1"' . checked( 1, isset( $this->options[$option_name] ) ? $this->options[$option_name] : 0, false ) . '/>';
            $html .= '<label for="' . $option_name . '"></label>';
        }
        echo wp_kses($html, array('input' => array('type' => array(), 'id' => array(), 'name' => array(), 'value' => array(), 'checked' => array(), 'disabled' => array()), 'label' => array('for' => array()) ));
    }

}
