# ACF Layout Templates

A few helpers to print ACF flexible layouts in a modular way.

## Requirements

  * ACF 5+

## Usage

```
<?php
  require_once('path/to/acf-layout-templates.php'); // this line should be defined only once, somewhere generic, like in functions.php

  // To include any $layout_base as subdir:
  add_filter('lalt_include_base_as_subdir', '__return_true'); // optional, explanation below

  // To NOT exclude any $layout_base as fallback:
  add_filter('lalt_exclude_base_as_root_fallback', '__return_false'); // optional, explanation below

  $layout_base = 'widget'; // defaults to 'acf-flex-layout'
  add_acf_layout_template_search_path(__DIR__ . '/templates', $layout_base);

  $my_field_with_flexible_layouts = get_field('some_widgets', $post_id);
  // $post_id could be 'options', of course

  print_acf_layouts($my_field_with_flexible_layouts, $layout_base);

  // or
  //$captured = print_acf_layouts($my_field_with_flexible_layouts, $layout_base, true);

?>
```

The stylesheet and template path portions in __DIR__ will be stripped
automatically, thus __DIR__ should be within the STYLESHEETPATH or
TEMPLATEPATH.

This will print the flex layouts using a template, located at one of the
following locations and names:

  - `template-root/__STRIPPED__DIR__/templates/widget/$name.php` (since the first filter above
    was set to return true)
  - `template-root/__STRIPPED__DIR__/templates/widget-$name.php`
  - `template-root/__STRIPPED__DIR__/templates/widget.php`
  - `template-root/widget/$name.php` (since the first filter above was set
    to return true)
  - `template-root/widget-$name.php`
  - `template-root/widget.php` (since the second filter above was set to
    return false. __read note below__)

Within the template a variable `$acf_layout` will be available, holding the layout options.
For convenience all options will be extracted and available as local variables (see notes below).

__ATTENTION:__ The second filter above could lead to unwanted behaviour, thus it
           is disabled by default. If, say, your $layout_base is 'page', then
           `template-root/page.php` will be loaded, or even
           `template-root/page-$name.php` which is even more unpredictable.

For more info about templates lookup see below.

All the above could have a global string prefixed, if you set the
`ACF_LAYOUT_TEMPLATE_PREFIX` constant.

```
<?php
  define('ACF_LAYOUT_TEMPLATE_PREFIX', 'prefix-');
  add_filter('lalt_locate', function($template_names) {
    echo '<pre>'; print_r($template_names); echo '</pre>';
    return $template_names;
  });
?>
```

__NOTES:__ Beware that when the `$acf_layout` options are extracted a set of
local variables will not be overwritten, but will still be accessible using `$acf_layout`:

  - `_template_file`
  - `require_once`
  - `layout_name`
  - `layout_base`
  - `acf_layout`
  - `posts`
  - `post`
  - `wp_did_header`
  - `wp_query`
  - `wp_rewrite`
  - `wpdb`
  - `wp_version`
  - `wp`
  - `id`
  - `comment`
  - `user_ID`

## Available methods

  - `add_acf_layout_template_search_path($path, $key = null);`
  - `add_acf_layout_template_search_path_with_priority($path, $key, $priority);`
  - `$paths_array  = get_layout_template_search_paths($key, $include_default = true);`
  - `$file_path    = load_acf_layout_template($layout_name, $layout_base = 'layout');`
  - `$output_array = print_acf_layouts($layouts_array, $layout_base = null, $capture = false);`
  - `$file_path    = extended_locate_template($template_names, $load = false, $require_once = true, $layout_name = null, $layout_base = null);`
  - `extended_load_template($_template_file, $require_once = true, $layout_name = null, $layout_base = null);` __(pluggable)__

## Available Filters

```
$layout_name = (string)apply_filters('lalt_layout_name', $layout_name, $layout_base);

if (apply_filters('lalt_include_base_as_subdir', false, $layout_base, $layout_name)) {}

$template_names = (array)apply_filters('lalt_base_names', $template_names, $layout_name, $layout_base);

if (apply_filters('lalt_exclude_base_as_root_fallback', true, $layout_base, $layout_name)) {}

$template_names = (array)apply_filters('lalt_locate', $template_names);
```

## Templates lookup

The lookup names list will be:

  - `{$layout_base}/{$layout_name}.php` (if `lalt_include_base_as_subdir` filter returns true)
  - `{$layout_base}-{$layout_name}.php`
  - `{$layout_base}.php`

If `$layout_base` is empty it defaults to `'acf-flex-layout'`.

By default `load_acf_layout_template()` looks in the following locations:
  - `stylesheet-directory/`
  - `template-directory/`

If additional search paths were added using
`add_acf_layout_template_search_path()`, they will be looked-up first, then
the stylesheet, and last - the template directory.

Please, note that the paths added using `add_acf_layout_template_search_path()`
will be considered relative to the stylesheet or template directory.

```
So '/path/to/wordpress/wp-content/themes/themename/additional/path'
and '/path/to/wordpress/wp-content/themes/sub-themename/additional/path'
and '/additional/path'
will all become `'/additional/path'.
```

To check the templates that will be searched, hook into
`lalt_locate` filter and inspect the array.

__ATTENTION__: Be careful how you name your `$layout_base`. A file, named
`{$layout_base}.php` is a fallback, but not by default. You can enable this
behaviour per `$layout_base` and `$layout_name` by returning false for the filter
`lalt_exclude_base_as_root_fallback`. The most common names that
WordPress templates use within the root of the template are:

  - 404
  - archive
  - author
  - category
  - comments
  - content
  - footer
  - functions
  - header
  - footer
  - image
  - index
  - loop
  - page
  - search
  - sidebar
  - single
  - tag
  - taxonomy

A global prefix to all base names could be set using the constant
`ACF_LAYOUT_TEMPLATE_PREFIX`. It will be prefixed to the name of `$layout_base`.
The prefix is set internally ONLY ONCE per the request.
