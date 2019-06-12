<?php

/**
 * @file
 * Implements image styles related actions on cockpit collections.
 */

include_once __DIR__ . '/utils.php';

/**
 * Remove all saved styles when removing the collection.
 */
$app->on('collections.remove.before', function($name, $criteria) use($app) {
  if (!empty($criteria['_id'])) {
    $styles_path = '#storage:styles/' . $name . '/' . $criteria['_id'];
    if ($app->path($styles_path)) {
      $app->helper('fs')->delete($styles_path);
    }
  }
});

/**
 * On each collection.save.after populate it with styles.
 */
$app->on('collections.save.after', function($name, &$entry, $isUpdate) use($app) {

  if ($app->memory->get('bypassSyles', FALSE)) {
    return;
  }

  $collection = $app->module('collections')->collection($name);
  if (!$app->module('imagestyles')->hasStyles($collection)) {
    return;
  }

  $entry = $app->module('imagestyles')->updateEntryStyles($collection, $entry);

  // Re-save the collection by including the styles.
  $app->storage->save("collections/{$collection['_id']}", $entry);
});

/**
 * On each singleton.saveData.before populate it with styles.
 */
$app->on('singleton.saveData.before', function($singleton, &$data) use($app) {

  if (!$app->module('imagestyles')->hasStyles($singleton)) {
    return;
  }

  $data = $app->module('imagestyles')->updateEntryStyles($singleton, $data);
});
