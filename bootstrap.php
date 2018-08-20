<?php

/**
 * @file
 * Cockpit module bootstrap implementation.
 */

$this->module('imagestyles')->extend([

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

    // Set anchor if its defined in the style.
    if (isset($style['anchor'])) {
      $options['fp'] = $style['anchor'];
    }

    // Override style definitions for base64 with settings argument.
    if (isset($settings['base64']) && $settings['base64']) {
      $options['base64'] = 1;
    }

    // Override style definitions for domain with settings argument.
    if (isset($settings['domain']) && $settings['domain']) {
      $options['domain'] = 1;
    }

    // Override style definitions for domain with settings argument.
    if (isset($settings['output']) && $settings['output']) {
      $options['output'] = 1;
    }

    // Set the effects.
    foreach ($style['effects'] as $effect) {
      $options[$effect['type']] = isset($effect['options']['value']) ? $effect['options']['value'] : TRUE;
    }

    return $this->app->module('cockpit')->thumbnail($options);
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

// If admin.
if (COCKPIT_ADMIN && !COCKPIT_API_REQUEST) {
  include_once __DIR__ . '/admin.php';
}

// If REST include handlers for remote style actions.
if (COCKPIT_API_REQUEST) {
  $this->on('cockpit.rest.init', function ($routes) {
      $routes['imagestyles'] = 'ImageStyles\\Controller\\RestApi';
  });

  // Include actions.
  include_once __DIR__ . '/actions.php';
}

