<?php
/*
 Plugin Name: Slideshow
 Plugin URI: http://wordpress.org/extend/plugins/slideshow-jquery-image-gallery/
 Description: The slideshow plugin is easily deployable on your website. Add any image that has already been uploaded to add to your slideshow, add text slides, or even add a video. Options and styles are customizable for every single slideshow on your website.
 Version: 2.2.22
 Requires at least: 3.5
 Author: StefanBoonstra
 Author URI: http://stefanboonstra.com/
 License: GPLv2
 Text Domain: slideshow-plugin
*/

/**
 * Class SlideshowPluginMain fires up the application on plugin load and provides some
 * methods for the other classes to use like the auto-includer and the
 * base path/url returning method.
 *
 * @since 1.0.0
 * @author Stefan Boonstra
 */
class SlideshowPluginMain
{
	/** @var string $version */
	static $version = '2.2.22';

	/**
	 * Bootstraps the application by assigning the right functions to
	 * the right action hooks.
	 *
	 * @since 1.0.0
	 */
	static function bootstrap()
	{
		self::autoInclude();

		// Initialize localization on init
		add_action('init', array(__CLASS__, 'localize'));

		// Include backend scripts and styles
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueueBackendScripts'));

		// Ajax requests
		SlideshowPluginAJAX::init();

		// Register slideshow post type
		SlideshowPluginPostType::init();

		SlideshowPluginSettingsProfile::init();

		SlideshowPluginStyle::init();

		// Add general settings page
		SlideshowPluginGeneralSettings::init();

		// Initialize stylesheet builder
		SlideshowPluginSlideshowStylesheet::init();

		// Deploy slideshow on do_action('slideshow_deploy'); hook.
		add_action('slideshow_deploy', array('SlideshowPlugin', 'deploy'));

		// Initialize shortcode
		SlideshowPluginShortcode::init();

		// Register widget
		add_action('widgets_init', array('SlideshowPluginWidget', 'registerWidget'));

		// Initialize plugin updater
		SlideshowPluginInstaller::init();
	}

	/**
	 * Includes backend script.
	 *
	 * Should always be called on the admin_enqueue_scrips hook.
	 *
	 * @since 2.2.12
	 */
	static function enqueueBackendScripts()
	{
		// Function get_current_screen() should be defined, as this method is expected to fire at 'admin_enqueue_scripts'
		if (!function_exists('get_current_screen'))
		{
			return;
		}

		$currentScreen = get_current_screen();

		// Enqueue 3.5 uploader
		if ($currentScreen->post_type === 'slideshow' &&
			function_exists('wp_enqueue_media'))
		{
			wp_enqueue_media();
		}

		wp_enqueue_script(
			'slideshow-jquery-image-gallery-backend-script',
			self::getPluginUrl() . '/js/min/all.backend.min.js',
			array(
				'jquery',
				'jquery-ui-sortable',
				'wp-color-picker'
			),
			SlideshowPluginMain::$version
		);

		wp_enqueue_style(
			'slideshow-jquery-image-gallery-backend-style',
			self::getPluginUrl() . '/css/all.backend.css',
			array(
				'wp-color-picker'
			),
			SlideshowPluginMain::$version
		);
	}

	/**
	 * Translates the plugin
	 *
	 * @since 1.0.0
	 */
	static function localize()
	{
		load_plugin_textdomain(
			'slideshow-plugin',
			false,
			dirname(plugin_basename(__FILE__)) . '/languages/'
		);
	}

	/**
	 * Returns url to the base directory of this plugin.
	 *
	 * @since 1.0.0
	 * @return string pluginUrl
	 */
	static function getPluginUrl()
	{
		return plugins_url('', __FILE__);
	}

	/**
	 * Returns path to the base directory of this plugin
	 *
	 * @since 1.0.0
	 * @return string pluginPath
	 */
	static function getPluginPath()
	{
		return dirname(__FILE__);
	}

	/**
	 * Outputs the passed view. It's good practice to pass an object like an stdClass to the $data variable, as it can
	 * be easily checked for validity in the view itself using "instanceof".
	 *
	 * @since 1.0.0
	 * @param string   $view
	 * @param stdClass $data (Optional, defaults to null)
	 */
	static function outputView($view, $data = null)
	{
		include self::getPluginPath() . '/views/' . $view;
	}

	/**
	 * Uses self::outputView to render the passed view. Returns the rendered view instead of outputting it.
	 *
	 * @since 1.0.0
	 * @param string   $view
	 * @param stdClass $data (Optional, defaults to null)
	 * @return string
	 */
	static function getView($view, $data = null)
	{
		ob_start();

		self::outputView($view, $data);

		return ob_get_clean();
	}

	/**
	 * Tries to retrieve the current post type from either the current $post, $typenow, or $current_screen global
	 * variable. Returns null when no post type was found.
	 *
	 * @since 2.3.0
	 * @return null|string
	 */
	static function getCurrentPostType()
	{
		global $post;

		if ($post &&
			$post->post_type)
		{
			return $post->post_type;
		}

		global $typenow;

		if ($typenow)
		{
			return $typenow;
		}

		global $current_screen;

		if ($current_screen &&
			$current_screen->post_type)
		{
			return $current_screen->post_type;
		}

		return filter_input(INPUT_GET, 'post_type', FILTER_SANITIZE_STRING);
	}

	/**
	 * This function will load classes automatically on-call.
	 *
	 * @since 1.0.0
	 */
	static function autoInclude()
	{
		if (!function_exists('spl_autoload_register'))
		{
			return;
		}

		function slideshowPluginAutoLoader($name)
		{
			$name = str_replace('\\', DIRECTORY_SEPARATOR, $name);
			$file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . $name . '.php';

			if (is_file($file))
			{
				require_once $file;
			}
		}

		spl_autoload_register('slideshowPluginAutoLoader');
	}
}

/**
 * Activate plugin
 */
SlideShowPluginMain::bootstrap();
