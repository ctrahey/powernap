<?php
namespace Trahey\PowerNapBundle\Annotations;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class GrantReadAccessByUserHasRelationToEntityOwner
{
    public $userType;
    public $keyPathOnUserToEntityOwner;
}