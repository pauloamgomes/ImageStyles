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
  public function style($name) {
    if (!$src = $this->param('src', NULL)) {
      return FALSE;
    }

    $settings['output'] = $this->param('output', 0);
    $settings['base64'] = $this->param('base64', 0);
    $settings['rebuild'] = $this->param('rebuild', 0);
    $settings['domain'] = $this->param('domain', 0);
    $settings['fp'] = $this->param('fp', 0);

    return $this->module('imagestyles')->applyStyle($name, $src, $settings);
  }

}
