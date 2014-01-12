<?php
namespace Trahey\PowerNapBundle\Annotations;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class GrantReadAccessByEntityHasRelationToUser
{
    public $userType;
    public $keyPathOnEntityToUser;
}