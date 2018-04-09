<?php

namespace ImageStyles\Controller;

use \LimeExtra\Controller;

/**
 * RestApi class for retrieving image styles.
 */
class RestApi extends Controller {

  /**
   * Retrieve an image.
   */
  public function style() {
    // Get request params.
    if (!$name = $this->param('style', NULL)) {
      return FALSE;
    }

    if (!$src = $this->param('src', NULL)) {
      return FALSE;
    }

    $settings['output'] = $this->param('output', FALSE);
    $settings['rebuild'] = $this->param('rebuild', FALSE);

    return $this->module('imagestyles')->applyStyle($name, $src, $settings);
  }

}
