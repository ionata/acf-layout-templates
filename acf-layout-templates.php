<?php
/**
 * This file includes a few helpers to print Advanced Custom Fields (ACF)
 * flexible layouts in a modular way.
 *
 * @author Evo Stamatov <evo@ionata.com.au>
 * @version 1.0.1
 *
 * ## Available methods
 *
 *   - `add_acf_layout_template_search_path($path, $key = null);`
 *   - `add_acf_layout_template_search_path_with_priority($path, $key, $priority);`
 *   - `$paths_array  = get_layout_template_search_paths($key, $include_default = true);`
 *   - `$file_path    = load_acf_layout_template($layout_name, $layout_base = 'layout');`
 *   - `$output_array = print_acf_layouts($layouts_array, $layout_base = null, $capture = false);`
 *   - `$file_path    = extended_locate_template($template_names, $load = false, $require_once = true, $layout_name = null, $layout_base = null);`
 *   - `extended_load_template($_template_file, $require_once = true, $layout_name = null, $layout_base = null);`
 *
 * ## Usage
 *
 * ```
 * <?php
 *
 *   add_filter('lalt_include_base_as_subdir', '__return_true');
 *   add_filter('lalt_exclude_base_as_root_fallback', '__return_false');
 *
 *   $layout_base = 'widget'; // defaults to 'acf-flex-layout'
 *   add_acf_layout_template_search_path(__DIR__ . '/templates', $layout_base);
 *
 *   $my_field_with_flexible_layouts = get_field('some_widgets', $post_id);
 *   // $post_id could be 'options', of course
 *   print_acf_layouts($my_field_with_flexible_layouts, $layout_base);
 *
 * ?>
 * ```
 *
 * The stylesheet and template path portions in __DIR__ will be stripped
 * automatically, thus __DIR__ should be within the STYLESHEETPATH or
 * TEMPLATEPATH.
 *
 * This will print the flex layouts using a template, located at one of the
 * following locations and names:
 *   - `template-root/__STRIPPED__DIR__/widget/$name.php` (since the first filter above
 *     was set to return true)
 *   - `template-root/__STRIPPED__DIR__/widget-$name.php`
 *   - `template-root/__STRIPPED__DIR__/widget.php`
 *   - `template-root/widget/$name.php` (since the first filter above was set
 *     to return true)
 *   - `template-root/widget-$name.php`
 *   - `template-root/widget.php` (since the second filter above was set to
 *     return false)
 *
 * ATTENTION: The second filter above could lead to unwanted behaviour, thus it
 *            is disabled by default. If, say, your $layout_base is 'page', then
 *            `template-root/page.php` will be loaded, or even
 *            `template-root/page-$name.php` which is even more unpredictable.
 *
 * All the above could have a global string prefixed, if you set the
 * `ACF_LAYOUT_TEMPLATE_PREFIX` constant.
 * ```
 * <?php
 *   define('ACF_LAYOUT_TEMPLATE_PREFIX', 'prefix-');
 *   add_filter('lalt_locate', function($template_names) {
 *     echo '<pre>'; print_r($template_names); echo '</pre>';
 *     return $template_names;
 *   });
 * ?>
 * ```
 *
 * ## Filters
 *
 * ```
 * $layout_name = (string)apply_filters('lalt_layout_name', $layout_name, $layout_base);
 * if (apply_filters('lalt_include_base_as_subdir', false, $layout_base, $layout_name)) {}
 * $template_names = (array)apply_filters('lalt_base_names', $template_names, $layout_name, $layout_base);
 * if (apply_filters('lalt_exclude_base_as_root_fallback', true, $layout_base, $layout_name)) {}
 * $template_names = (array)apply_filters('lalt_locate', $template_names);
 * ```
 *
 */

/**
 * Add a path to the search paths.
 *
 * Same as below method, but sets a default priority of 10.
 *
 * This is the preferred method, unless you want to explicitly set
 * specific priority.
 *
 * The stylesheet and template path portions will be stripped automatically,
 * but the $path should be within the STYLESHEETPATH or TEMPLATEPATH.
 *
 * So '/path/to/wordpress/wp-content/themes/themename/additional/path'
 * and '/path/to/wordpress/wp-content/themes/sub-themename/additional/path'
 * and '/additional/path'
 * will all become '/additional/path'.
 *
 * If key is empty or '_default' these paths will be added to the end of the
 * result when getting all search paths using get_layout_template_search_paths()
 *
 * @return void
 */
function add_acf_layout_template_search_path($path, $key = null) {
	add_acf_layout_template_search_path_with_priority($path, $key, 10);
}

/**
 * Add a path to the search paths.
 *
 * @see add_acf_layout_template_search_path
 *
 * @return void
 */
function add_acf_layout_template_search_path_with_priority($path, $key, $priority) {
	global $layout_search_paths;

	$path = str_replace(STYLESHEETPATH, '', $path);
	$path = str_replace(TEMPLATEPATH, '', $path);

	if (empty($path)) {
		return;
	}

	if (! isset($layout_search_paths)) {
		$layout_search_paths = array();
	}

	$key = (string)$key;
	if ('' === $key) {
		$key = '_default';
	}

	if (! isset($layout_search_paths[$key])) {
		$layout_search_paths[$key] = array();
	}

	if (! isset($layout_search_paths[$key][$priority])) {
		$layout_search_paths[$key][$priority] = array($path);
	} else {
		$layout_search_paths[$key][$priority][] = $path;
	}
}

/**
 * Helper function to get all search paths for a key as an array of arrays.
 *
 * @return array [$priority => [$path, ...], ...]
 */
function _get_layout_template_search_paths_for_key($key) {
	global $layout_search_paths;

	if (empty($layout_search_paths) || empty($layout_search_paths[$key])) {
		return array();
	}

	return $layout_search_paths[$key];
}

/**
 * Helper function to get all search paths for a given key as a flattened
 * and sorted array of paths.
 *
 * @return array [$path, ...]
 */
function _get_layout_template_search_paths_for_all_priorities($key) {
	$arr = _get_layout_template_search_paths_for_key($key);

	if (empty($arr)) {
		return array();
	}

	$priorities = array_keys($arr);
	natsort($priorities);

	$prioritized_paths = array();
	foreach ($priorities as $priority) {
		$prioritized_paths = array_merge($prioritized_paths, $arr[$priority]);
	}

	return $prioritized_paths;
}

/**
 * Get all search paths for a given key with an optional inclusion of the
 * default paths.
 *
 * @return array [$path, ...]
 */
function get_layout_template_search_paths($key, $include_default = true) {
	$paths = _get_layout_template_search_paths_for_all_priorities($key);
	
	if ($include_default) {
		$default_paths = _get_layout_template_search_paths_for_all_priorities('_default');
		$paths = array_merge($paths, $default_paths);
	}

	return $paths;
}

/**
 * Finds and loads a template file for a given acf layout.
 *
 * The lookup names list will be:
 *   - if $layout_base is not empty
 *     + {$layout_base}/{$layout_name}.php (if 'lalt_include_base_as_subdir' filter returns true)
 *     + {$layout_base}-{$layout_name}.php
 *     + {$layout_base}.php
 *
 * By default it looks in the following locations:
 *   - stylesheet-directory/
 *   - template-directory/
 *
 * If additional search paths were added using
 * add_acf_layout_template_search_path(), they will be looked-up first, then
 * the stylesheet, and last - the template directory.
 *
 * To check the templates that will be searched, hook into
 * 'lalt_locate' filter and inspect the array.
 *
 * !!! ATTENTION: Be careful how you name your $layout_base. A file, named
 * {$layout_base}.php is a fallback, but not by default. You can enable this
 * behaviour per $layout_base and $layout_name by returning false for the filter
 * 'lalt_exclude_base_as_root_fallback'. The most common names that
 * WordPress templates use within the root of the template are:
 *   - 404
 *   - archive
 *   - author
 *   - category
 *   - comments
 *   - content
 *   - footer
 *   - functions
 *   - header
 *   - footer
 *   - image
 *   - index
 *   - loop
 *   - page
 *   - search
 *   - sidebar
 *   - single
 *   - tag
 *   - taxonomy
 *
 * A global prefix to all base names could be set using the constant
 * ACF_LAYOUT_TEMPLATE_PREFIX. It will be prefixed to the name of $layout_base.
 * The prefix is set internally ONLY ONCE per the request.
 *
 * @return string The located template pathname.
 */
function load_acf_layout_template($raw_layout_name, $layout_base = null) {
	static $prefix = null;

	if ($prefix === null && defined('ACF_LAYOUT_TEMPLATE_PREFIX')) {
		$prefix = (string)ACF_LAYOUT_TEMPLATE_PREFIX;
	}

	$layout_base = (string)$layout_base;
	if ('' === $layout_base) {
		$layout_base = 'acf-flex-layout';
	}

	// Allow for the layout name to be adjusted, based on the layout base.
	// Sometimes layout names are already set and not unified.
	$layout_name = (string)apply_filters('lalt_layout_name_' . $layout_base, $raw_layout_name);
	$layout_name = (string)apply_filters('lalt_layout_name',                 $raw_layout_name, $layout_base);

	$template_names = array();

	$use_base_as_subdir = false;
	$use_base_as_subdir = apply_filters('lalt_include_base_as_subdir_' . $layout_base, $use_base_as_subdir, $layout_name);
	$use_base_as_subdir = apply_filters('lalt_include_base_as_subdir',                 $use_base_as_subdir, $layout_base, $layout_name);
	if ($use_base_as_subdir) {
		$template_names[] = $prefix . $layout_base . DIRECTORY_SEPARATOR . $layout_name . '.php';
	}

	$template_names[] = $prefix . $layout_base . '-' . $layout_name . '.php';
	$template_names[] = $to_exclude = $prefix . $layout_base . '.php'; // will be excluded for root below

	$template_names = (array)apply_filters('lalt_base_names_' . $layout_base . '_' . $layout_name, $template_names);
	$template_names = (array)apply_filters('lalt_base_names_' . $layout_base,                      $template_names, $layout_name);
	$template_names = (array)apply_filters('lalt_base_names',                                      $template_names, $layout_name, $layout_base);

	$search_paths = get_layout_template_search_paths($layout_base);
	if (! empty($search_paths)) {
		$template_paths = array();

		foreach ($search_paths as $path) {
			foreach ($template_names as $name) {
				$template_paths[] = $path . DIRECTORY_SEPARATOR . $name;
			}
		}

		if ((null === $prefix || '' === $prefix) &&
			'acf-flex-layout' !== $layout_base)
		{
			$exclude = true;
			$exclude = apply_filters('lalt_exclude_base_as_root_fallback_' . $layout_base, $exclude, $layout_name);
			$exclude = apply_filters('lalt_exclude_base_as_root_fallback',                 $exclude, $layout_base, $layout_name);

			$to_exclude = $exclude ? $layout_base . '.php' : '';

			foreach ($template_names as $name) {
				if ($name !== $to_exclude) {
					$template_paths[] = $name;
				}
			}
		}

		$template_names = $template_paths;
	}

	$template_names = (array)apply_filters('lalt_locate_' . $layout_base . '_' . $layout_name, $template_names);
	$template_names = (array)apply_filters('lalt_locate_' . $layout_base,                      $template_names, $layout_name);
	$template_names = (array)apply_filters('lalt_locate',                                      $template_names, $layout_name, $layout_base);

	return extended_locate_template($template_names, $load = true, $require_once = false, $layout_name, $layout_base);
}

/**
 * Runs a list over load_acf_layout_template() using the acf_fc_layout key,
 * which is set for the flexible layout field.
 *
 * Setting $capture to true will capture the output buffers of
 * all load_acf_layout_template() calls into the resulting array, thus not
 * outputing anything at the end of the layouts.
 *
 * Within each layout template you can inspect the global $acf_layout for the
 * layout options.
 *
 * @return array The HTML output of the templates or empty if $capture is false.
 */
function print_acf_layouts($layouts_array, $layout_base = null, $capture = false) {
	global $acf_layout;

	$result = array();

	foreach ($layouts_array as $acf_layout) {
		if (! isset($acf_layout['acf_fc_layout'])) {
			continue;
		}

		if ($capture) {
			ob_start();
		}

		load_acf_layout_template($key = $acf_layout['acf_fc_layout'], $layout_base);

		if ($capture) {
			while (isset($result[$key])) {
				$key .= '_';
			}
			$result[$key] = ob_get_clean();
		}
	}

	return $result;
}

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
 * inherit from a parent theme can just overload one file.
 *
 * It will pass over the layout name and layout base.
 *
 * If a custom extended_load_template_[{$layout_name}_]{$layout_base} function
 * exists, it will be called instead of the default extended_load_template.
 *
 * !!!ATTENTION: Make sure to honour $require_once param within your function.
 *
 * @since 2.7.0
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool $load If true the template file will be loaded if it is found.
 * @param bool $require_once Whether to require_once or require. Default true. Has no effect if $load is false.
 * @param string $layout_name Holds the layout name
 * @param string $layout_base Holds the layout base for the layout
 * @return string The template filename if one is located.
 */
function extended_locate_template($template_names, $load = false, $require_once = true, $layout_name = null, $layout_base = null) {
	$located = '';
	foreach ((array)$template_names as $template_name) {
		if (! $template_name) {
			continue;
		}
		if (file_exists(STYLESHEETPATH . '/' . $template_name)) {
			$located = STYLESHEETPATH . '/' . $template_name;
			break;
		} elseif (file_exists(TEMPLATEPATH . '/' . $template_name)) {
			$located = TEMPLATEPATH . '/' . $template_name;
			break;
		}
	}

	if ($load && '' != $located) {
		if (! empty($layout_base) && ! empty($layout_base) && function_exists($func_name = "extended_load_template_{$layout_name}_{$layout_base}")) {
			call_user_func($func_name, $located, $require_once, $layout_name, $layout_base);
		} elseif (! empty($layout_base) && function_exists($func_name = "extended_load_template_{$layout_base}")) {
			call_user_func($func_name, $located, $require_once, $layout_name, $layout_base);
		} else {
			extended_load_template($located, $require_once, $layout_name, $layout_base);
		}
	}

	return $located;
}

/**
 * Require the template file with WordPress environment.
 *
 * The globals are set up for the template file to ensure that the WordPress
 * environment is available from within the function. The query variables are
 * also available.
 *
 * ACF layout variables are available as well. Be aware that the following
 * keys are reserved and will not be set:
 *   - _template_file
 *   - require_once
 *   - layout_name
 *   - layout_base
 *   - acf_layout
 *   - posts
 *   - post
 *   - wp_did_header
 *   - wp_query
 *   - wp_rewrite
 *   - wpdb
 *   - wp_version
 *   - wp
 *   - id
 *   - comment
 *   - user_ID
 *
 * You can still access them by using $acf_layout[].
 *
 * Or simply implement your own extend_load_template and 
 *
 * @since 1.5.0
 *
 * @param string $_template_file Path to template file.
 * @param bool $require_once Whether to require_once or require. Default true.
 * @param string $layout_name Holds the layout name
 * @param string $layout_base Holds the layout base for the layout
 */
if (! function_exists('extended_load_template')) {
function extended_load_template($_template_file, $require_once = true, $layout_name = null, $layout_base = null) {
	global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID, $acf_layout;

	if (is_array($acf_layout)) {
		extract($acf_layout, EXTR_SKIP);
	}

	if (is_array($wp_query->query_vars)) {
		extract($wp_query->query_vars, EXTR_SKIP);
	}

	if ($require_once) {
		require_once($_template_file);
	} else {
		require($_template_file);
	}
}
}
