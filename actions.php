<?php

/**
 * @file
 * Implements image styles related actions on cockpit collections.
 */

include_once __DIR__ . '/utils.php';

$app->on('collections.save.before', function($name, &$entry, $isUpdate) use($app) {

  // Get the collection.
  $collection = $app->module('collections')->collection($name);

  // Get the collection fields' definitions.
  $fields = [];
  foreach ($collection['fields'] as $field) {
    $fields[$field['name']] = $field;
  }

  // Get all available styles.
  $styles = array_map(function($style) {
    return $style['name'];
  }, $app->module('imagestyles')->styles());

  if (empty($styles)) {
    return;
  }

  // Get all paths inside a collection entry.
  $paths = array_dot($entry);
  foreach ($paths as $key => $value) {
    if (!preg_match('/^.*\.path$/', $key)) {
      continue;
    }
    $parent_path = str_replace('.path', '', $key);
    $parent = array_get($entry, $parent_path);
    $parent['styles'] = $app->module('imagestyles')->getUrlStyles($value, $styles);
    array_set($entry, $parent_path, $parent);
  }

});
