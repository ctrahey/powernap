<?php

namespace Trahey\PowerNapBundle\Mapping\Driver;
use Trahey\PowerNapBundle\Annotations\OwnerKeypath;
use Trahey\PowerNapBundle\Annotations\MutableRestProperty;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver,
  Doctrine\Common\Annotations\AnnotationReader,
  Gedmo\Exception\InvalidMappingException;
use Doctrine\Common\Annotations\Reader;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use \JMS\Serializer\Metadata\PropertyMetadata;
/**
 * This is an annotation mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Sluggable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
  /**
   * Annotation reader instance
   *
   * @var Reader
   */
  protected $reader;

  /**
   * {@inheritDoc}
   */
  public function readExtendedMetadata($meta, array &$config) {
    $class = $this->getMetaReflectionClass($meta);
    // class annotations

      if($accessSpec = $this->reader->getClassAnnotation($class, '\\Trahey\\RestBundle\\Annotations\\GrantReadAccessByEntityHasRelationToUser')) {
          $config['read_grants'][$accessSpec->userType] = array(
              'type' => 'entity_to_user',
              'keypath' => $accessSpec->keyPathOnEntityToUser
          );
      }
      if($accessSpec = $this->reader->getClassAnnotation($class, '\\Trahey\\RestBundle\\Annotations\\GrantReadAccessByUserHasRelationToEntityOwner')) {
          $config['read_grants'][$accessSpec->userType] = array(
              'type' => 'user_to_owner',
              'keypath' => $accessSpec->keyPathOnUserToEntityOwner
          );
      }
      if($file_passthrough = $this->reader->getClassAnnotation($class, '\\Trahey\\RestBundle\\Annotations\\GETAsFilePassThrough')) {
          $config['file_passthrough_getter'] = $file_passthrough->fileGetter;
      }
    if($ownerKeypath = $this->reader->getClassAnnotation($class, '\\Trahey\\RestBundle\\Annotations\\OwnerKeypath')) {
      $config['enforce_owner'] = true;
      $config['owner_keypath'] = $ownerKeypath->keypath;
      $config['owner_type'] = $ownerKeypath->userType;
    }
    if($tokens = $this->reader->getClassAnnotation($class, '\\Trahey\\RestBundle\\Annotations\\URLTokens')) {
      $config['singular'] = $tokens->singular;
      $config['plural'] = $tokens->plural;
    }
    // property annotations
    foreach ($class->getProperties() as $property) {
      if ($meta->isMappedSuperclass && !$property->isPrivate() ||
        $meta->isInheritedField($property->name) ||
        isset($meta->associationMappings[$property->name]['inherited'])
      ) {
        continue;
      }
      // Mutable Rest Property
      if ($annotation = $this->reader->getPropertyAnnotation($property, '\\Trahey\\RestBundle\\Annotations\\MutableRestProperty')) {
        $propName = $this->derivePropertyName($property, $annotation);
        $config['properties'][$propName]['setter'] = $this->deriveSetterName($property, $annotation);
        $config['properties'][$propName]['getter'] = $this->deriveGetterName($property, $annotation);
        $config['properties'][$propName]['as_reference'] = $annotation->asDoctrineReference;
        $config['properties'][$propName]['as_collection'] = $annotation->asDoctrineReferenceCollection;
        $config['properties'][$propName]['inverse_setter'] = $annotation->inverseSetter;
      }
    }
  }

  protected function derivePropertyName(\ReflectionProperty $property, MutableRestProperty $annotation) {
    if($annotation->property_name) {
      return $annotation->property_name;
    }
    // mimic FOS_Rest serialization strategy.
    $propMeta = new PropertyMetadata($property->getDeclaringClass()->getName(), $property->getName());
    $translator = new CamelCaseNamingStrategy();
    return $translator->translateName($propMeta);
  }

  protected function deriveSetterName(\ReflectionProperty $property, MutableRestProperty $annotation) {
    if($annotation->setter) {
      return $annotation->setter;
    }
    return 'set' . $this->getRootOfAccessorNamesForProperty($property);
  }
  protected function deriveGetterName(\ReflectionProperty $property, MutableRestProperty $annotation) {
    if($annotation->getter) {
      return $annotation->getter;
    }
    return 'get' . $this->getRootOfAccessorNamesForProperty($property);
  }

  protected function getRootOfAccessorNamesForProperty(\ReflectionProperty $property) {
     $name = $property->getName();
    $components = explode('_', $name);
    $components = array_map('ucfirst', $components);
    return implode($components);
  }
}