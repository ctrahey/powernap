<?php
namespace Trahey\RestBundle\Annotations;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class GETAsFilePassThrough
{
    public $fileGetter;
}