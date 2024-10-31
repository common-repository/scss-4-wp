=== SCSS-4-WP ===
Contributors: fieldofcode
Tags: sass, scss, css, ScssPhp
Plugin URI: https://fieldofcode.com
Requires at least: 3.0.1
Tested up to: 6.1
Requires PHP: 5.6
Stable tag: 1.0.0
License: GPLv3 or later
License URI: http://www.gnu.org/copyleft/gpl.html

== Description ==

Use [ScssPhp](https://github.com/scssphp/scssphp/). to compile scss files on your wordpress install into a single lightweight CSS file.  There is an included settings page for configuring directories, error reporting, compiling options, and auto enqueuing.

To keep the page load time to a minimum this plugin only runs the compiler when the scss files have been changed. All compiled files create or alter a matching css file in the chosen directory which remains even if this plugin is disabled so that your site never loses its styles and is always ready for user interaction.

== Installation ==

1. Upload plugin to plugins directory
2. Active plugin through the 'Plugins' menu in Wordpress
3. Configure plugin options through settings page `Settings -> SCSS-4-WP`.

== Frequently Asked Questions ==

= Can I use a child theme? =

Yes.

= What version of PHP is required? =

PHP 5.6 is required to run SCSS-4-WP


= How do I @import subfiles =

You can import other scss files into parent files and compile them into a single css file. To do this, use @import as normal in your scss file. All imported file names *must* start with an underscore. Otherwise they will be compiled into their own css file.

When importing in your scss file, you can leave off the underscore.

> `@import 'subfile';`


= Can I use .sass syntax with this Plugin? =

This plugin will only work with .scss format.


= I'm having other issues and need help =

If you are having issues with the plugin, contact us at https://fieldofcode.com
