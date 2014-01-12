<?php
namespace Trahey\RestBundle\Annotations;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class OwnerKeypath
{
    public $userType;
    public $keypath;
}