<?php
namespace Trahey\RestBundle\Annotations;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class URLTokens
{
  public $singular;
  public $plural;
}