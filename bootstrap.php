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
  $config = $this->retrieve('config/cloudstorage');

  // If we don't have any cloudstorage configuration rely only on localstorage.
  if (!$config || !isset($config['styles'])) {
    $storages['styles'] = [
      'adapter' => 'League\Flysystem\Adapter\Local',
      'args' => [COCKPIT_PUBLIC_STORAGE_FOLDER . '/styles'],
      'mount' => TRUE,
      'url' => $app->pathToUrl('#storage:', TRUE) . 'styles',
    ];
  }
});

/**
 * Image Style module functions.
 */
$this->module('imagestyles')->extend([

  'hasStyles' => function(array $collection) {
    $fields = array_dot($collection['fields']);
    $paths = [];
    $imageTypes = ['image', 'asset', 'gallery', 'repeater', 'set', 'layout'];
    foreach ($fields as $key => $value) {
      if (preg_match('/\.type$/', $key) && in_array($value, $imageTypes)) {
        return TRUE;
      }
    }
    return FALSE;
  },

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
    $results = [];

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
    $_id = $entry['_id'] ?? $collection['_id'];
    $styles_path = '#storage:styles/' . $collection['name'] . '/' . $_id;

    if (!$this->app->path($styles_path)) {
      if (!$this->app->helper('fs')->mkdir($styles_path)) {
        return $entry;
      }
      $cimgt = time();
    }

    if (!file_exists($this->app->path($styles_path) . '/index.html')) {
      $this->app->helper('fs')->write($this->app->path($styles_path) .'/index.html', '');
    }

    $settings['stylesfolder'] = 'styles://' . $collection['name'] . '/' . $_id;

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
      $parent['styles'] = [];

      if (!isset($parent['path'])) {
        continue;
      }

      $segments = explode('.', $parent_path);
      $field_container = $segments[0];
      $field_name = end($segments);

      $dot_fields_field = [];

      // Gallery.
      if (is_numeric($field_name) && count($segments) === 2) {
        $field_name = $segments[count($segments) - 2];
        $dot_fields_field = array_filter($dot_fields, function($dot_field) use ($field_container) {
          if (preg_match("/^{$field_container}\..*\.styles/", $dot_field)) {
            return $dot_field;
          }
        }, ARRAY_FILTER_USE_KEY);
      }
      // Repeater.
      elseif ($field_name === 'value' && count($segments) > 2) {
        $field_name = $segments[count($segments) - 3];
        $dot_fields_field = array_filter($dot_fields, function($dot_field) use ($field_container) {
          if (preg_match("/^{$field_container}\..*\.styles/", $dot_field)) {
            return $dot_field;
          }
        }, ARRAY_FILTER_USE_KEY);
      }
      // Set.
      elseif (count($segments) === 2) {
        $dot_fields_field = array_filter($dot_fields, function($dot_field) use ($field_container, $field_name) {
          if (preg_match("/^{$field_container}\..*\.styles/", $dot_field)) {
            return $dot_field;
          }
        }, ARRAY_FILTER_USE_KEY);
      }
      elseif (is_numeric($field_name) && count($segments) > 2) {
        $dot_fields_field = array_filter($dot_fields, function($dot_field) use ($field_container, $field_name) {
          if (preg_match("/^{$field_container}\..*\.styles/", $dot_field)) {
            return $dot_field;
          }
        }, ARRAY_FILTER_USE_KEY);
      }
      // Asset.
      elseif (count($segments) === 1) {
        $dot_fields_field = array_filter($dot_fields, function($dot_field) use ($field_name) {
          if (preg_match("/^{$field_name}\..*\.styles/", $dot_field)) {
            return $dot_field;
          }
        }, ARRAY_FILTER_USE_KEY);
      }
      // Layout field components.
      elseif (count($segments) > 3) {
        // Gallery inside field layout.
        if (is_numeric($field_name)) {
          $field_name = $segments[count($segments) - 2];
        }
        elseif ($field_name === 'value') {
          $field_name = $segments[count($segments) - 3];
        }

        $field_container = $segments[3];

        $dot_fields_field = array_filter($dot_fields, function($dot_field) use ($field_container, $field_name) {
          if (preg_match("/_components\.[a-zA-Z0-9_]+\.{$field_container}\..*\.styles/", $dot_field)) {
            return $dot_field;
          }
          elseif (preg_match("/_components\.[a-zA-Z0-9_]+\.{$field_name}\..*\.styles/", $dot_field)) {
            return $dot_field;
          }
        }, ARRAY_FILTER_USE_KEY);
      }

      $field_styles = array_unique(array_values($dot_fields_field));

      // Only continue if there are styles to apply.
      if (empty($field_styles)) {
        continue;
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

      // Check if its an asset.
      $assetId = NULL;
      if (!empty($parent['_id'])) {
        $assetId = $parent['_id'];
      }
      elseif (isset($parent['meta']) && !empty($parent['meta']['asset'])) {
        $assetId = $parent['meta']['asset'];
      }
      // If is an asset use _id instead of path so focal point can be used.
      if ($assetId && $asset = $this->app->storage->findOne('cockpit/assets', ['_id' => $assetId])) {
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
      'rebuild' => isset($settings['rebuild']) ? (bool) $settings['rebuild'] : FALSE,
      'output' => isset($settings['output']) ? (bool) $settings['output'] : FALSE,
      'quality' => isset($style['quality']) ? $style['quality'] : FALSE,
      'base64' => isset($style['base64']) ? (bool) $style['base64'] : FALSE,
      'domain' => isset($style['domain']) ? (bool) $style['domain'] : FALSE,
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

    // If output should be base64 don't continue;
    if ($options['base64']) {
      return $thumb;
    }

    // If site url is defined in the config.
    $site_url = $this->app->getSiteUrl();
    if ($site_url && strpos($thumb, $site_url) !== FALSE && $site_url !== '/') {
      $thumb = str_replace($site_url, '', $thumb);
    }

    // Get the base url and remove it.
    $base_url = $this->app->baseUrl('/');

    if ($base_url && strpos($thumb, $base_url) !== FALSE && $base_url !== '/') {
      $thumb = str_replace($base_url, '', $thumb);
    }

    // If domain is not set remove it (e.g. when using cloudstorage).
    if (!$options['domain'] && strpos($thumb, 'http') === 0) {
      $parts = parse_url($thumb);
      $config = $this->app->retrieve('config/cloudstorage');

      // Check if styles are using S3 cloudstorage.
      if ($config && !empty($config['styles']['bucket'])) {
        $thumb = str_replace("/{$config['styles']['bucket']}", '', $parts['path']);
      }
      else {
        $thumb = $parts['path'];
      }
    }

    // If domain is set to true force it in the output.
    if ($options['domain'] && strpos($thumb, 'http') === FALSE) {
      $thumb = rtrim($base_url, '/') . $thumb;
    }

    // If token is present (and output is not base64) append it.
    if (!empty($settings['token']) && !$options['base64']) {
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

// CLI includes.
if (COCKPIT_CLI) {
  $this->path('#cli', __DIR__ . '/cli');
}

// If REST include handlers for remote style actions.
if (COCKPIT_API_REQUEST) {
  $this->on('cockpit.rest.init', function ($routes) {
      $routes['imagestyles'] = 'ImageStyles\\Controller\\RestApi';
  });
}

