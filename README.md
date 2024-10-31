# scss-4-wp

#### Another SCSS compiler for Wordpress.

Use [scssphp](https://github.com/scssphp/scssphp) to compile scss files on your wordpress install into a single lightweight CSS file.  There is an included settings page for configuring directories, error reporting, compiling options, and auto enqueuing.

To keep the page load time to a minimum this plugin only runs the compiler when the scss files have been changed. All compiled files create or alter a matching css file in the chosen directory which remains even if this plugin is disabled so that your site never loses its styles and is always ready for user interaction.



#### Compiling Mode

There are two compiling modes inline with css standards:

- Compressed - More compressed css. Entire rule block on one line. No indentation.
- Expanded - Full open css. One line per property. Brackets close on their own line.

See examples of each in [ScssPHP's documentation](http://scssphp.github.io/scssphp)

- Current version of ScssPHP is 1.10.0

#### Source Map Mode

There are three source map modes for documentation purposes:

- None - No source map will be generated.
- Inline - A source map will be generated in the compiled CSS file.
- File - A source map will be generated as a standalone file in the compiled CSS directory.

#### Error Display

There are two error display modes, and error log or a message displayed on the front end.  If you’re working on a live site you can send errors to a log.  If there currently is no log file one will be created in your scss directory and errors will be printed to the file as they occur.  Compiled css will not update until the errors have been resolved so that user experience isn’t affected.  If you select to “Show in Header” a message will be posted on the front end of the site when errors occur so that you can more easily debug your scss.

#### Enqueuing

This plugin can automatically add all files found within your css directory (including ones not compiled by this plugin) for you to the header.  There is no need to [enqueue](http://codex.wordpress.org/Function_Reference/wp_enqueue_style) them separately if you have selected this option.

N.B. If you disable this plugin it will no longer be able to enqueue these files for you.


## Directions

This plugin requires at least php 5.6 to work and has been tested up to php 8.0

#### Importing Subfiles

Following standard scss practice you can import scss files into parent files using @import and compile them into a single css file.  Any files you wish to import must have file names that start with an underscore so as to not be compiled into an independent css file.

N.B. while imported file names must start with an underscore when they are imported you can leave off the underscore

Example: Importing a file names _responsive.scss into custom.css using

    @import ‘responsive.scss’;   

is a great way to keep complete control of where in the waterfall your responsive styles are added.

#### Setting Variables via PHP

You can set SCSS variables in your theme or plugin by using the scss4wp_variables filter.

    function scss4wp_set_variables(){
        $variables = array(
            'black' => '#000',
            'white' => '#fff'
        );
        return $variables;
    }
    add_filter('scss4wp_variables','scss4wp_set_variables');

#### Always Recompile

The option is available to tell the plugin to always recompile in the plugin options page or by adding the following constant to your wp-config.php or functions.php file.

    define('SCSS4WP_ALWAYS_RECOMPILE', true);

While sites are in a development/staging environment it can be helpful to force the compiler to run on every page load, especially on hosts where filemtime() is not updating consistently.  We do not recommend using this setting on live sites.

#### .sass Support

This plugin will only work with .scss format.

#### Maintainers

- [@fieldofcode](https://fieldofcode.com)


## License

This plugin was developed by [Field of Code](https://fieldofcode.com/).

[GPL V3](http://www.gnu.org/copyleft/gpl.html)
