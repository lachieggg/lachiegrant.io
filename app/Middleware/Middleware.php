<?php

namespace LoginApp\Middleware;

class Middleware
{
  protected $container;

  /**
   * @param $container
   */
  public function __construct($container)
  {
    $this->container = $container;
  }
}
