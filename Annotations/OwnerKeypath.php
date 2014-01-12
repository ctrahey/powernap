<?php
namespace Trahey\PowerNapBundle\Annotations;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class OwnerKeypath
{
    public $userType;
    public $keypath;
}