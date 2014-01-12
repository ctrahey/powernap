<?php
namespace Trahey\PowerNapBundle\Annotations;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class URLTokens
{
  public $singular;
  public $plural;
}