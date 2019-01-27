<?php

/**
 * @file
 * Helper functions for handling arrays.
 * Based on Laravel helpers - https://github.com/rappasoft/laravel-helpers/blob/master/src/helpers.php
 */

/**
 * Return the default value of the given value.
 *
 * @param $value
 * @return mixed
 */
function value($value) {
  return $value instanceof Closure ? $value() : $value;
}

/**
 * Sets the value of an array using a doted path.
 *
 * @param $array
 * @param $key
 * @param $value
 * @return mixed
 */
function array_set(&$array, $key, $value) {
  if (is_null($key)) return $array = $value;
  $keys = explode('.', $key);
  while (count($keys) > 1) {
    $key = array_shift($keys);
    // If the key doesn't exist at this depth, we will just create an empty array
    // to hold the next value, allowing us to create the arrays to hold final
    // values at the correct depth. Then we'll keep digging into the array.
    if ( ! isset($array[$key]) || ! is_array($array[$key])) {
      $array[$key] = [];
    }
    $array =& $array[$key];
  }
  $array[array_shift($keys)] = $value;
  return $array;
}

/**
 * Gets the value of an array using a doted path.
 *
 * @param $array
 * @param $key
 * @param null $default
 * @return mixed
 */
function array_get($array, $key, $default = null) {
  if (is_null($key)) {
    return $array;
  }
  if (isset($array[$key])) {
    return $array[$key];
  }
  foreach (explode('.', $key) as $segment) {
    if ( ! is_array($array) || ! array_key_exists($segment, $array)) {
      return value($default);
    }
    $array = $array[$segment];
  }
  return $array;
}

/**
 * Flatten a multi-dimensional associative array with dots.
 *
 * @param $array
 * @param string $prepend
 * @return array
 */
function dot($array, $prepend = '') {
  $results = [];
  foreach ($array as $key => $value) {
    if (is_array($value)) {
      $results = array_merge($results, dot($value, $prepend.$key.'.'));
    }
    else {
      $results[$prepend.$key] = $value;
    }
  }
  return $results;
}

/**
 * Flatten a multi-dimensional associative array with dots.
 *
 * @param $array
 * @param string $prepend
 * @return array
 */
function array_dot($array, $prepend = '') {
  $results = [];
  foreach ($array as $key => $value) {
    if (is_array($value)) {
      $results = array_merge($results, dot($value, $prepend.$key.'.'));
    }
    else {
      $results[$prepend.$key] = $value;
    }
  }
  return $results;
}

/**
 * Helper function to get defined styles from the fields definitions.
 */
function _get_field_styles($array, $field_name, $fields) {
  $styles = [];
  foreach ($array as $key => $value) {
    if (preg_match("/\.name$/", $key) && $value === $field_name) {
      $parent_path = str_replace('.name', '', $key);
      $parent = array_get($fields, $parent_path);

      if (!isset($parent['options'])) {
        continue;
      }

      if (!empty($parent['options']['styles']) && is_array($parent['options']['styles'])) {
        $styles = array_merge($styles, $parent['options']['styles']);
      }
      elseif (isset($parent['options']['field']) && isset($parent['options']['field']['styles'])) {
        $styles = array_merge($styles, $parent['options']['field']['styles']);
      }
      elseif (isset($parent['options']['field']['options']) || !empty($parent['options']['field']['options']['fields'])) {
        $subDotArray = array_dot($parent['options']['field']);
        $subStyles = [];
        foreach ($subDotArray as $dotKey => $dotEntry) {
          if (preg_match("/^options\.styles/", $dotKey)) {
            $subStyles[] = array_get($parent['options']['field'], $dotKey);
          }
        }
        $styles = array_merge($styles, $subStyles);
      }
    }
  }

  return array_unique(array_filter($styles));
}
