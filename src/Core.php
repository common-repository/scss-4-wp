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

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\ValueConverter;

class Core extends Plugin {

    public function __construct() {
        $this->set_vars();
        if(is_array($this->settings)) {
            add_action('wp_loaded', array($this, 'maybe_needs_compiling'));
            if($this->settings['enqueue'] == '1') {
                add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'), 50);
            }
        }
    }

    private function set_vars() {
        $this->options = get_option('scss4wp_options');
        $base_compiling_folder = isset($this->options['base_compiling_folder']) ? parent::base_folder_name_to_path($this->options['base_compiling_folder']) : '';
        $scss_dir_setting = isset($this->options['scss_dir']) ? $this->options['scss_dir'] : '';
        $css_dir_setting = isset($this->options['css_dir']) ? $this->options['css_dir'] : '';
        if(!empty($base_compiling_folder) && !empty($scss_dir_setting) && !empty($css_dir_setting) && is_dir($base_compiling_folder . $scss_dir_setting) && is_dir($base_compiling_folder . $css_dir_setting)) {
            $this->settings = array(
                'scss_dir'         => $base_compiling_folder . $scss_dir_setting,
                'css_dir'          => $base_compiling_folder . $css_dir_setting,
                'css_dir_setting'  => $css_dir_setting,
                'compiling'        => isset($this->options['compiling_options']) ? $this->options['compiling_options'] : 'compressed',
                'always_recompile' => isset($this->options['always_recompile'])  ? $this->options['always_recompile']  : false,
                'errors'           => isset($this->options['errors'])            ? $this->options['errors']            : 'show',
                'sourcemaps'       => isset($this->options['sourcemap_options']) ? $this->options['sourcemap_options'] : 'SOURCE_MAP_NONE',
                'enqueue'          => isset($this->options['enqueue'])           ? $this->options['enqueue']           : 0
            );
            $this->base_compiling_folder = $base_compiling_folder;
        } else {
            $this->settings = false;
        }
    }

    public function maybe_needs_compiling() {
        $needs = false;
        if(is_array($this->settings)) {
            if((defined('SCSS4WP_ALWAYS_RECOMPILE') && SCSS4WP_ALWAYS_RECOMPILE) || $this->settings['always_recompile'] == '1') {
                $needs = true;
            } else {
                $this->scss_dir = $this->settings['scss_dir'];
                $this->css_dir = $this->settings['css_dir'];

                $latest_scss = 0;
                $latest_css = 0;

                foreach ( new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->scss_dir), \RecursiveDirectoryIterator::SKIP_DOTS) as $sfile ) {
                    if (pathinfo($sfile->getFilename(), PATHINFO_EXTENSION) == 'scss') {
                        $file_time = $sfile->getMTime();

                        if ( (int) $file_time > $latest_scss) {
                            $latest_scss = $file_time;
                        }
                    }
                }

                foreach ( new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->css_dir), \RecursiveDirectoryIterator::SKIP_DOTS) as $cfile ) {
                    if (pathinfo($cfile->getFilename(), PATHINFO_EXTENSION) == 'css') {
                        $file_time = $cfile->getMTime();

                        if ( (int) $file_time > $latest_css) {
                            $latest_css = $file_time;
                        }
                    }
                }

                if ($latest_scss > $latest_css) {
                    $needs = true;
                }
            }
            if($needs === true) {
                $this->run();
            }
        }
    }

    public function run() {
        $this->scss_dir = $this->settings['scss_dir'];
        $this->css_dir = $this->settings['css_dir'];
        $this->compile_errors   = array();
        $this->scssc            = new Compiler();

        $this->cache = SCSS4WP_PLUGIN_DIR . '/cache/';

        $variables = apply_filters('scss4wp_variables', array());
        foreach ($variables as $key => $value) {
            if (strlen(trim($value)) == 0) {
                unset($variables[$key]);
            }
        }
        if(!empty($variables)) {
            $this->scssc->addVariables($variables);
        }

        $this->scssc->setOutputStyle( $this->settings['compiling'] );
        $this->scssc->setImportPaths( $this->scss_dir );

        $this->sourcemaps = $this->settings['sourcemaps'];
        $input_files = array();

        // Loop through directory and get .scss file that do not start with '_'
        foreach(new \DirectoryIterator($this->scss_dir) as $file) {
            if (substr($file, 0, 1) != "_" && pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'scss') {
                array_push($input_files, $file->getFilename());
            }
        }

        // For each input file, find matching css file and compile
        foreach ($input_files as $scss_file) {
            $input = $this->scss_dir . $scss_file;
            $outputName = preg_replace("/\.[^$]*/", ".css", $scss_file);
            $output = $this->css_dir . $outputName;

            $this->compiler($input, $output);
        }

        if (count($this->compile_errors) < 1) {
            if  ( is_writable($this->css_dir) ) {
                foreach (new \DirectoryIterator($this->cache) as $this->cache_file) {
                    if ( pathinfo($this->cache_file->getFilename(), PATHINFO_EXTENSION) == 'css') {
                        file_put_contents($this->css_dir . $this->cache_file, file_get_contents($this->cache . $this->cache_file));
                        unlink($this->cache . $this->cache_file->getFilename()); // Delete file on successful write
                    }
                }
            } else {
                $errors = array(
                    'file' => 'CSS Directory',
                    'message' => "File Permissions Error, permission denied. Please make your CSS directory writable."
                );
                array_push($this->compile_errors, $errors);
            }
        }
    }

    /**
    * METHOD COMPILER
    * Takes scss $in and writes compiled css to $out file
    * catches errors and puts them the object's compiled_errors property
    *
    * @function compiler - passes input content through scssphp,
    *                      puts compiled css into cache file
    *
    * @var array input_files - array of .scss files with no '_' in front
    * @var array sdir_arr - an array of all the files in the scss directory
    *
    * @return nothing - Puts successfully compiled css into appropriate location
    *                   Puts error in 'compile_errors' property
    * @access public
    */
    private function compiler($in, $out) {

        if (!file_exists($this->cache)) {
            mkdir($this->cache, 0644);
        }

        if (is_writable($this->cache)) {
            try {
                $map = basename($out) . '.map';
                $this->scssc->setSourceMap(constant('ScssPhp\ScssPhp\Compiler::' . $this->sourcemaps));
                $this->scssc->setSourceMapOptions(array(
                'sourceMapWriteTo' => $this->css_dir . $map, // absolute path to a file to write the map to
                'sourceMapURL' => $map, // url of the map
                'sourceMapBasepath' => rtrim(ABSPATH, '/'), // base path for filename normalization
                'sourceRoot' => home_url('/'), // This value is prepended to the individual entries in the 'source' field.
                ));

                $compilationResult = $this->scssc->compileString(file_get_contents($in), $in);
                $css = $compilationResult->getCss();
                file_put_contents($this->cache . basename($out), $css);
            } catch (\Exception $e) {
                $errors = array (
                'file' => basename($in),
                'message' => $e->getMessage(),
                );
                array_push($this->compile_errors, $errors);
            }
        } else {
            $errors = array (
            'file' => $this->cache,
            'message' => "File Permission Error, permission denied. Please make the cache directory writable."
            );
            array_push($this->compile_errors, $errors);
        }
    }

    public function style_url_enqueued($url){
        global $wp_styles;
        foreach($wp_styles->queue as $wps_name){
            $wps = $wp_styles->registered[$wps_name];
            if($wps->src == $url){
                return $wps;
            }
        }
        return false;
    }

    public function enqueue_styles() {
        $base_folder_path = $this->base_compiling_folder;
        $css_folder = $this->settings['css_dir_setting'];
        if($base_folder_path === wp_get_upload_dir()['basedir']){
            $enqueue_base_url = wp_get_upload_dir()['baseurl'];
        }
        else if($base_folder_path === SCSS4WP_PLUGIN_DIR){
            $enqueue_base_url = plugins_url();
        }
        else if($base_folder_path === get_template_directory()){
            $enqueue_base_url = get_template_directory_uri();
        }
        else{ // assume default of get_stylesheet_directory()
            $enqueue_base_url = get_stylesheet_directory_uri();
        }

        foreach( new \DirectoryIterator($this->css_dir) as $stylesheet ) {
            if ( pathinfo($stylesheet->getFilename(), PATHINFO_EXTENSION) == 'css' ) {
                $name = $stylesheet->getBasename('.css') . '-style';
                $uri = $enqueue_base_url . $css_folder . $stylesheet->getFilename();
                $ver = $stylesheet->getMTime();
                wp_register_style(
                    $name,
                    $uri,
                    array(),
                    $ver,
                    $media = 'all'
                );

                if(!$this->style_url_enqueued($uri)){
                    wp_enqueue_style($name);
                }
            }
        }
    }

}
