<?php
namespace Trahey\RestBundle\Annotations;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class GrantReadAccessByEntityHasRelationToUser
{
    public $userType;
    public $keyPathOnEntityToUser;
}