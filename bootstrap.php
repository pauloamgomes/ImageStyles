<?php

/**
 * @file
 * Cockpit module bootstrap implementation.
 */

/**
 * Create a custom storage (#styles) for storing the image styles.
 * This is required in way to make use of core cockpit thumbnail() function.
 */
$this->on('cockpit.filestorages.init', function(&$storages) use ($app) {
  $storages['styles'] = [
    'adapter' => 'League\Flysystem\Adapter\Local',
    'args' => [COCKPIT_PUBLIC_STORAGE_FOLDER . '/styles'],
    'mount' => TRUE,
    'url' => $app->pathToUrl('#storage:', TRUE) . 'styles',
  ];
});

/**
 * Image Style module functions.
 */
$this->module('imagestyles')->extend([

  'getImageUrlStyles' => function ($path, array $styles = [], $settings = []) : array {
    $uploads_path = ltrim(str_replace(COCKPIT_DIR, '', $this->app->path("#uploads:")), '/');
    $results = [];
    $path = ltrim($path, '/');
    if (strpos($path, $uploads_path) !== 0) {
      $path = $uploads_path . $path;
    }

    foreach ($styles as $style) {
      if ($url = $this->applyStyle($style, $path, $settings)) {
        $results[] = [
          'style' => $style,
          'path' => $url,
        ];
      }
    }

    return $results;
  },

  'getAssetUrlStyles' => function ($asset, array $styles = [], $settings = []) : array {
    if (!empty($asset['fp'])) {
      $settings['fp'] = TRUE;
    }

    foreach ($styles as $style) {
      if ($url = $this->applyStyle($style, $asset['_id'], $settings)) {
        $results[] = [
          'style' => $style,
          'path' => $url,
        ];
      }
    }

    return $results;
  },

  'deleteEntryStyles' => function ($name, $entry) : array {
    $styles_path = '#storage:styles/' . $name . '/' . $entry['_id'];

    if ($this->app->path($styles_path)) {
      $this->app->helper('fs')->delete($styles_path);
    }

    return ['status' => 1];
  },

  'updateEntryStyles' => function ($collection, $entry) : array {
    $cimgt = FALSE;

    // Get all available styles.
    $styles = array_map(function($style) {
      return $style['name'];
    }, $this->app->module('imagestyles')->styles());

    if (empty($styles)) {
      return $entry;
    }

    // Prepare the collection styles folder.
    $styles_path = '#storage:styles/' . $collection['name'] . '/' . $entry['_id'];

    if (!$this->app->path($styles_path)) {
      if (!$this->app->helper('fs')->mkdir($styles_path)) {
        return $entry;
      }
      $cimgt = time();
    }

    if (!file_exists($this->app->path($styles_path) . '/index.html')) {
      $this->app->helper('fs')->write($this->app->path($styles_path) .'/index.html', '');
    }

    $settings['stylesfolder'] = 'styles://' . $collection['name'] . '/' . $entry['_id'];

    // Get the collection fields' definitions.
    $fields = [];
    foreach ($collection['fields'] as $field) {
      // Only fields that can have an image are processed.
      switch ($field['type']) {
        case 'asset':
        case 'image':
        case 'set':
        case 'repeater':
        case 'gallery':
        case 'layout':
          $fields[$field['name']] = $field;
      }
    }

    // Get all image fields from layout components.
    $content = '{}';
    if ($file = $this->app->path('#storage:components.json')) {
      $content = @file_get_contents($file);
    }
    $json = json_decode($content, true);
    if (!$json) {
      $json = [];
    }
    $components = new \ArrayObject($json);
    foreach ($components as $name => $component) {
      foreach ($component ['fields'] as $idx => $field) {
        switch ($field['type']) {
          case 'asset':
          case 'image':
          case 'set':
          case 'repeater':
          case 'gallery':
          case 'layout':
            $fields['_components'][$name][$field['name']] = $field;
        }
      }
    }

    $dot_fields = array_dot($fields);

    // Get all paths inside a collection entry.
    $paths = array_dot($entry);

    foreach ($paths as $key => $value) {
      if (!preg_match('/\.path$/', $key)) {
        continue;
      }
      if (!preg_match('/\.(png|jpg|jpeg|gif|svg)$/i', $value)) {
        continue;
      }
      $parent_path = str_replace('.path', '', $key);
      $parent = array_get($entry, $parent_path);

      if (!isset($parent['path'])) {
        continue;
      }

      $segments = explode('.', $parent_path);
      $field_name = end($segments);

      $field_styles = _get_field_styles($dot_fields, $field_name, $fields);

      // If no field styles are found (e.g. core layout components like image or gallery) we apply to all.
      if (empty($field_styles)) {
        $field_styles = $styles;
      }

      // Set a query string token to force update of images (to avoid caching).
      if ($cimgt) {
        $parent['cimgt'] = $cimgt;
      }
      else {
        if (empty($parent['cimgt'])) {
          $parent['cimgt'] = time();
        }
      }

      $settings['token'] = $parent['cimgt'];

      // If is an asset use _id instead of path so focal point can be used.
      if (!empty($parent['_id'])
        && $asset = $this->app->storage->findOne('cockpit/assets', ['_id' => $parent['_id']])) {
        $parent['styles'] = $this->app->module('imagestyles')->getAssetUrlStyles($asset, $field_styles, $settings);
      }
      else {
        $parent['styles'] = $this->app->module('imagestyles')->getImageUrlStyles($value, $field_styles, $settings);
      }


      array_set($entry, $parent_path, $parent);
    }

    return $entry;
  },

  'createStyle' => function ($name, $data = []) {
    if (!trim($name)) {
      return FALSE;
    }

    $configpath = $this->app->path('#storage:') . '/imagestyles';

    if (!$this->app->path('#storage:imagestyles')) {
      if (!$this->app->helper('fs')->mkdir($configpath)) {
        return FALSE;
      }
    }

    if ($this->exists($name)) {
        return FALSE;
    }

    $time = time();

    $style = array_replace_recursive([
      '_id'         => uniqid($name),
      'name'        => $name,
      'description' => $name,
      'effects'     => [],
      '_created'    => $time,
      '_modified'   => $time,
    ], $data);

    $export = var_export($style, TRUE);

    if (!$this->app->helper('fs')->write("#storage:imagestyles/{$name}.imagestyle.php", "<?php\n return {$export};")) {
        return FALSE;
    }

    $this->app->trigger('imagestyles.createstyle', [$style]);

    return $style;
  },

  'updateStyle' => function ($name, $data) {
    $metapath = $this->app->path("#storage:imagestyles/{$name}.imagestyle.php");

    if (!$metapath) {
      return FALSE;
    }

    $data['_modified'] = time();

    $style  = include $metapath;
    $style  = array_merge($style, $data);

    $this->app->trigger("imagestyles.save.before", [$style]);
    $this->app->trigger("imagestyles.save.before.{$name}", [$style]);

    $export  = var_export($style, TRUE);

    if (!$this->app->helper('fs')->write($metapath, "<?php\n return {$export};")) {
      return FALSE;
    }

    $this->app->trigger('imagestyles.save.after', [$style]);
    $this->app->trigger("imagestyles.save.after.{$name}", [$style]);

    return $style;
  },

  'saveStyle' => function ($name, $data) {
    if (!trim($name)) {
      return FALSE;
    }

    return isset($data['_id']) ? $this->updateStyle($name, $data) : $this->createStyle($name, $data);
  },

  'removeStyle' => function ($name) {

    if ($style = $this->style($name)) {

      $this->app->helper("fs")->delete("#storage:imagestyles/{$name}.imagestyle.php");

      $this->app->trigger('imagestyles.remove', [$style]);
      $this->app->trigger("imagestyles.remove.{$name}", [$style]);

      return TRUE;
    }

    return FALSE;
  },

  'exists' => function ($name) {
      return $this->app->path("#storage:imagestyles/{$name}.imagestyle.php");
  },

  'styles' => function ($extended = FALSE) {

    $stores = [];

    foreach ($this->app->helper("fs")->ls('*.imagestyle.php', '#storage:imagestyles') as $path) {

      $store = include $path->getPathName();

      if ($extended) {
        $store['itemsCount'] = $this->count($store['name']);
      }

      $stores[$store['name']] = $store;
    }

    return $stores;
  },

  'style' => function ($name) {
    // Cache.
    static $styles;

    if (is_null($styles)) {
      $styles = [];
    }

    if (!is_string($name)) {
      return FALSE;
    }

    if (!isset($styles[$name])) {

      $styles[$name] = FALSE;

      if ($path = $this->exists($name)) {
        $styles[$name] = include $path;
      }
    }

    return $styles[$name];
  },

  'applyStyle' => function ($name, $src, $settings = []) {
    if (!$style = $this->style($name)) {
      return FALSE;
    }

    $options = [
      'src' => $src,
      'rebuild' => isset($settings['rebuild']) ? $settings['rebuild'] : FALSE,
      'output' => isset($settings['output']) ? $settings['output'] : FALSE,
      'quality' => isset($style['quality']) ? $style['quality'] : FALSE,
      'base64' => isset($style['base64']) ? $style['base64'] : FALSE,
      'domain' => isset($style['domain']) ? $style['domain'] : FALSE,
      'mode' => $style['mode'],
      'width' => $style['width'],
      'height' => $style['height'],
    ];

    // If we have a custom folder stor storing the styles we pass it into thumbnail options.
    if (isset($settings['stylesfolder'])) {
      $options['cachefolder'] = $settings['stylesfolder'];
    }

    // Set anchor if its defined in the style.
    if (isset($style['anchor'])) {
      $options['fp'] = $style['anchor'];
    }

    // If focal point is set to be used unset it from the options so thumbnail function will retrieve it.
    if (isset($settings['fp']) && (bool) $settings['fp']) {
      unset($options['fp']);
    }

    // Override style definitions for base64 with settings argument.
    if (isset($settings['base64']) && (bool) $settings['base64']) {
      $options['base64'] = 1;
    }

    // Override style definitions for domain with settings argument.
    if (isset($settings['domain']) && (bool) $settings['domain']) {
      $options['domain'] = 1;
    }

    // Override style definitions for domain with settings argument.
    if (isset($settings['output']) && (bool) $settings['output']) {
      $options['output'] = 1;
    }

    // Set the effects.
    foreach ($style['effects'] as $effect) {
      $options[$effect['type']] = isset($effect['options']['value']) ? $effect['options']['value'] : TRUE;
    }

    // Generate the styled image (thumb) using cockpit thumbnail() function.
    $thumb = $this->app->module('cockpit')->thumbnail($options);

    // If site url is defined in the config.
    $site_url = $this->app->getSiteUrl();
    if (strpos($thumb, $site_url) !== FALSE) {
      $thumb = str_replace($site_url, '', $thumb);
    }
    // Get the base url and remove it.
    $base_url = $this->app->baseUrl('/');

    if (strpos($thumb, $base_url) !== FALSE) {
      $thumb = str_replace($base_url, '', $thumb);
    }

    // If domain is set to true force it in the output.
    if ($options['domain'] && strpos($thumb, 'http') === FALSE) {
      $thumb = rtrim($base_url, '/') . $thumb;
    }

    // Remove the storage folder.

    $thumb = str_replace('storage/', '/', $thumb);

    if (!empty($settings['token'])) {
      $thumb = "{$thumb}?cimgt={$settings['token']}";
    }

    return $thumb;
  },

  'previewStyle' => function ($src, $style) {
    $options = [
      'src' => $src,
      'rebuild' => 1,
      'quality' => intval(isset($style['quality']) ? $style['quality'] : 100),
      'base64' => 1,
      'mode' => $style['mode'],
      'width' => intval($style['width']),
      'height' => intval($style['height']),
    ];

    // Set anchor if its defined in the style.
    if (isset($style['anchor'])) {
      $options['fp'] = $style['anchor'];
    }

    foreach ($style['effects'] as $effect) {
      $options[$effect['type']] = isset($effect['options']['value']) ? $effect['options']['value'] : TRUE;
    }

    return $this->app->module('cockpit')->thumbnail($options);
  },

]);


// If admin include relevant files.
if (COCKPIT_ADMIN && !COCKPIT_API_REQUEST) {
  include_once __DIR__ . '/admin.php';
  include_once __DIR__ . '/actions.php';
}

// If REST include handlers for remote style actions.
if (COCKPIT_API_REQUEST) {
  $this->on('cockpit.rest.init', function ($routes) {
      $routes['imagestyles'] = 'ImageStyles\\Controller\\RestApi';
  });
}

