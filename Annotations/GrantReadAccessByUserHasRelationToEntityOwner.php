<?php
namespace Trahey\RestBundle\Annotations;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class GrantReadAccessByUserHasRelationToEntityOwner
{
    public $userType;
    public $keyPathOnUserToEntityOwner;
}