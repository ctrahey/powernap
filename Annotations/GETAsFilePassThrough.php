<?php
namespace Trahey\PowerNapBundle\Annotations;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class GETAsFilePassThrough
{
    public $fileGetter;
}