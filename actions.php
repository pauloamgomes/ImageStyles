<?php

/**
 * @file
 * Implements image styles related actions on cockpit collections.
 */

$app->on('collections.find.after', function ($name, &$data) use ($app) {
  // Get the collection.
  $collection = $app->module('collections')->collection($name);

  // Get the collection fields' definitions.
  $fields = [];
  foreach ($collection['fields'] as $field) {
    $fields[$field['name']] = $field;
  }

  foreach ($data as $idx => $entry) {
    foreach ($entry as $fieldName => $values) {    
      
      // Check if current field in entry is defined by user, we don't want '_id' etc. to proceed.
      if (!isset($fields[$fieldName])) {
        continue;
      }

      switch ($fields[$fieldName]['type']) {
        case 'repeater':
          foreach ($data[$idx][$field['name']] as $idx1 => $repeatField) {
            if (!isset($repeatField['value']['path'])) {
              continue;
            }
            if (!isset($repeatField['field']['styles']) || !is_array($repeatField['field']['styles'])) {
              continue;
            }
            foreach ($repeatField['field']['styles'] as $idx2 => $style) {
              if ($url = $app->module('imagestyles')->applyStyle($style, $repeatField['value']['path'])) {
                $data[$idx][$field['name']][$idx1]['value']['styles'][$idx2] = [
                  'style' => $style,
                  'path' => $url,
                ];
              }
            }
          }
          break;

        case 'set':
          foreach ($field['options']['fields'] as $idx1 => $subField) {
            if (!isset($subField['styles']) || !is_array($subField['styles'])) {
              continue;
            }
            if (!isset($data[$idx][$fieldName][$subField['name']]['path'])) {
              continue;
            }
            foreach ($subField['styles'] as $idx2 => $style) {
              if ($url = $app->module('imagestyles')->applyStyle($style, $data[$idx][$fieldName][$subField['name']]['path'])) {
                $data[$idx][$fieldName][$subField['name']]['styles'][$idx2] = [
                  'style' => $style,
                  'path' => $url,
                ];
              }
            }
          }
          break;

        case 'gallery':
          if (is_array($values) && !empty($fields[$fieldName]['options']['styles'])) {

            foreach ($values as $idx1 => $item) {
              if (empty($item['path'])) {
                continue;
              }
              // Iterate over the styles and get the style image url.
              foreach ( (array) $fields[$fieldName]['options']['styles'] as $style ) {
                if ($url = $app->module('imagestyles')->applyStyle($style, $item['path'])) {
                  $data[$idx][$fieldName][$idx1]['styles'][] = [
                    'style' => $style,
                    'path' => $url,
                  ];
                }
              }
            }
          }
          break;

        case 'image':
          if (!empty($values['path']) && !empty($fields[$fieldName]['options']['styles'])) {
            // Iterate over the styles and get the style image url.
            foreach ( (array) $fields[$fieldName]['options']['styles'] as $style ) {
              if ($url = $app->module('imagestyles')->applyStyle($style, $values['path'])) {
                $data[$idx][$fieldName]['styles'][] = [
                  'style' => $style,
                  'path' => $url,
                ];
              }
            }
          }
          break;
      }
    }
  }
});
