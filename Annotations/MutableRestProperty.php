<?php
namespace Trahey\RestBundle\Annotations;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
class MutableRestProperty
{
  public $property_name;
  public $setter;
  public $getter;
  public $asDoctrineReference;
  public $asDoctrineReferenceCollection;
  public $inverseSetter;
}