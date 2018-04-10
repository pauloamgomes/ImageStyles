<?php

/**
 * @file
 * Implements image styles related actions on cockpit collections.
 */

$app->on('collections.find.after', function ($name, &$data) use ($app) {
  // Get the collection.
  $collection = $app->module('collections')->collection($name);

  // Get the collection fields.
  $fields = [];
  foreach ($collection['fields'] as $field) {
    $fields[$field['name']] = $field;
  }

  foreach ($data as $idx => $entry) {
    foreach ($entry as $fieldName => $values) {
      // Check for image (path).
      if (isset($values['path']) && isset($fields[$fieldName])) {
        $field = $fields[$fieldName];

        // Check field definition for required attributes.
        if (!isset($field['options']) || empty($field['options']['styles'])) {
          continue;
        }

        // Check the styles.
        if (!is_array($field['options']['styles'])) {
          continue;
        }

        // Iterate over the styles and get the style image url.
        foreach ($field['options']['styles'] as $style) {
          if ($url = $app->module('imagestyles')->applyStyle($style, $values['path'])) {
            $data[$idx][$fieldName]['styles'][] = [
              'style' => $style,
              'path' => $url,
            ];
          }
        }
      }
    }
  }
});
