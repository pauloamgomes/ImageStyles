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

  $styles = $app->module('imagestyles')->styles();
  if (empty($styles)) {
    return;
  }

  $uploads_path = ltrim(str_replace(COCKPIT_DIR, '', $app->path("#uploads:")), '/');

  foreach ($data as $idx => $entry) {
    foreach ($entry as $fieldName => $values) {

      // Check if current field in entry is defined by user, we don't want '_id' etc. to proceed.
      if (!isset($fields[$fieldName])) {
        continue;
      }

      switch ($fields[$fieldName]['type']) {
        case 'repeater':
          foreach ($data[$idx][$fieldName] as $idx1 => $repeatField) {
            // If repeater field is a set with an image field.
            if ($repeatField['field']['type'] == 'set') {
              $setStyles = [];
              foreach ($repeatField['field']['options']['fields'] as $setField) {
                if ($setField['type'] == 'image' && isset($setField['styles'])) {
                  $setStyles[$setField['name']] = $setField['styles'];
                }
              }
              foreach ($setStyles as $setFieldname => $setStyle) {
                if (isset($data[$idx][$fieldName][$idx1]['value'][$setFieldname]['path'])) {
                  foreach ($setStyle as $style) {
                    if ($url = $app->module('imagestyles')->applyStyle($style, $data[$idx][$fieldName][$idx1]['value'][$setFieldname]['path'])) {
                      $data[$idx][$fieldName][$idx1]['value'][$setFieldname]['styles'][] = [
                        'style' => $style,
                        'path' => $url,
                      ];
                    }
                  }
                }
              }
              continue;
            }

            // If repeater field is an image.
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
          if (!isset($field['options']['fields'])) {
            continue;
          }

          foreach ((array) $field['options']['fields'] as $idx1 => $subField) {

            // If set subfield is a repeater check we have an image field inside.
            if ($subField['type'] == 'repeater' && $subField['options']['field']['type'] == 'image' && !empty($subField['options']['field']['styles'])) {
              $repeaterStyles[$subField['options']['field']['name']] = (array) $subField['options']['field']['styles'];
              foreach ($repeaterStyles as $repeatFieldName => $styles) {
                foreach ($styles as $style) {
                  foreach ($data[$idx][$fieldName][$subField['name']] as $idx2 => $repeaterField) {
                    if (!empty($repeaterField['value']['path']) && $url = $app->module('imagestyles')->applyStyle($style, $repeaterField['value']['path'])) {
                      $data[$idx][$fieldName][$subField['name']][$idx2]['value']['styles'][] = [
                        'style' => $style,
                        'path' => $url,
                      ];
                    }
                  }
                }
              }
              continue;
            }

            // Normal image field inside a set.
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

        case 'layout':
          // Configured assets in layout dont accept any options.
          // Instead of retrieving the configured styles we generate for all.
          foreach ($data[$idx][$fieldName] as $idx1 => $fieldData) {
            if (!isset($fieldData['settings'])) {
              continue;
            }
            foreach ($fieldData['settings'] as $settingName => $setting) {
              if (is_array($setting) && isset($setting['path']) && (!empty($setting['image']) || isset($setting['meta']) || array_key_exists('width', $fieldData['settings']))) {
                foreach ($styles as $style => $styleSettings) {
                  if (!empty($styleSettings['base64']) || !empty($styleSettings['output'])) {
                    continue;
                  }

                  $setting['path'] = ltrim($setting['path'], '/');
                  if (strpos($setting['path'], $uploads_path) !== 0) {
                    $setting['path'] = $uploads_path . $setting['path'];
                  }

                  if ($url = $app->module('imagestyles')->applyStyle($style, $setting['path'])) {
                    $data[$idx][$fieldName][$idx1]['settings'][$settingName]['styles'][] = [
                      'style' => $style,
                      'path' => $url,
                    ];
                  }
                }
              }
            }
          }
          break;
      }
    }
  }
});
