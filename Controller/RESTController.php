<?php
namespace Trahey\PowerNapBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\Controller\FOSRestController as Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Trahey\PowerNapBundle\CRUDEvent;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Trahey\PowerNapBundle\EntityAnnotationListener;
use Trahey\PowerNapBundle\IdentityResolver;

/**
 * @Route(service="trahey_rest.rest_controller")
 */
class RESTController extends Controller
{
  /**
   * @var array
   */
  private $_config;

  /**
   * @var Registry
   */
  protected $doctrine;

  /**
   * @var EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * @var Request
   */
  protected $request;

  /**
   * @var IdentityResolver $identityResolver;
   */
  protected $identityResolver;
  /**
   * @var \Trahey\PowerNapBundle\EntityAnnotationListener
   */
  protected $annotations;

  public function __construct($config = array(), Registry $doctrine, EventDispatcherInterface $eventDispatcher, EntityAnnotationListener $annotations, IdentityResolver $resolver = NULL) {
    $this->_config = $config;
    $this->doctrine = $doctrine;
    $this->eventDispatcher = $eventDispatcher;
    $this->annotations = $annotations;
    $this->identityResolver = $resolver;
  }

  /**
   * @return EntityAnnotationListener
   */
  public function getAnnotations() {
    return $this->annotations;
  }
  /**
   * @return EventDispatcher
   */
  public function getEventDispatcher() {
    return $this->eventDispatcher;
  }

  /**
   * @return Request
   */
  public function getRequest() {
     return $this->request;
  }

  /**
   * @return \Doctrine\Bundle\DoctrineBundle\Registry
   */
  public function getDoctrine() {
    return $this->doctrine;
  }
  public function dereferenceKeypathOnObject($object, $keypath) {
      // e.g. 'parent.zip_code' should map to:
      // return $object->getParent()->getZipCode();
      if(!is_array($keypath)) {
          $keypath = explode('.', $keypath);
      }
      foreach($keypath as $currentPathComponent) {
        $getter = 'get' . ucfirst($currentPathComponent);
        $object = $object->$getter();
      }
      return $object;
  }
  /**
   * @param string $singular_entity_name
   * @param integer $entity_id
   * @return object
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  protected function getEntity($singular_entity_name, $entity_id) {
    $config = $this->configForEntity($singular_entity_name);
    $repo = $this->getDoctrine()->getManager()->getRepository($config['class']);
    $entity = $repo->find($entity_id);
    $userSide = false;
    if($config['enforce_owner']) {
        // starting with the assumption of left == right being user == entity.owner
        // expressed as $userSide == $entitySide
        $currentType = $this->getCurrentUserType();
        if(!$currentType) {
            throw new AccessDeniedHttpException('AUTH_REQUIRED');
        }
        $currentUser = $this->getCurrentUser();
        $getMethod = 'get' . ucfirst($config['owner_keypath']);
        $entityOwner = call_user_func(array($entity, $getMethod));
        $entitySide = $entityOwner;
        // see if we are the same type as owner
        if($config['owner_type'] == $currentType) {
            $userSide = $currentUser;
        } elseif(array_key_exists('read_grants', $config) &&  array_key_exists($currentType, $config['read_grants'])) {
            $grant = $config['read_grants'][$currentType];
            switch($grant['type']) {
                case 'entity_to_user':
                    $userSide = $currentUser;
                    $entitySide = $this->dereferenceKeypathOnObject($entity, $grant['keypath']);
                    break;
                case 'user_to_owner':
                    $entitySide = $entityOwner;
                    $userSide = $this->dereferenceKeypathOnObject($currentUser, $grant['keypath']);
                    break;
            }
        }

      if($entitySide && $userSide != $entitySide) {
        throw new AccessDeniedHttpException('NOT_MINE');
      }
    }
    return $entity;
  }

  /**
   * @param Object $entity
   * @param string $property_name
   * @return mixed the value returned by the associated getter
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  protected function getEntityProperty($entity, $property_name) {
    $getterMethod = 'get' . ucfirst($property_name);
    if(!is_callable(array($entity, $getterMethod))) {
      throw new NotFoundHttpException();
    }
    return $entity->$getterMethod();
  }

  /**
   * @param Object $parent_entity
   * @param string $member_name
   * @param integer $child_id
   * @return object the object with id $child_id if it is a member of the collection found at $parent_entity->$member_name
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  protected function getEntityMemberChild($parent_entity, $member_name, $child_id) {
    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata $metaData
     * @var \Doctrine\Common\Persistence\ObjectManager $em
     * @var Collection $list
     */
    $list = $this->getEntityProperty($parent_entity, $member_name);
    $entity = $this->getEntityByIdForMember($parent_entity, $member_name, $child_id);
    if(!$list->contains($entity)) {
      throw new NotFoundHttpException();
    }
    return $entity;
  }

  protected function getEntityByIdForMember($parent_entity, $member_name, $child_id) {
    $em = $this->getDoctrine()->getManager();
    $repo = $em->getRepository($this->getClassNameForProperty($parent_entity, $member_name));
    return $repo->find($child_id);
  }

  protected function getClassNameForProperty($entity, $property) {
    $em = $this->getDoctrine()->getManager();
    $metaData = $em->getClassMetadata(get_class($entity));
    $field = $metaData->getAssociationMapping($property);
    return $field['targetEntity'];
  }

  protected function createEntityFromRequest($className) {
    $newObject = new $className;
    $this->populateObjectFromRequest($newObject, $this->getRequest()->request);
    $em = $this->getDoctrine()->getManager();
    $em->persist($newObject);
    $em->flush();
    $newEvent = new CRUDEvent($newObject, $this->getRequest());
    $this->getEventDispatcher()->dispatch('trahey_rest.entity_created', $newEvent);
    return $newObject;
  }

    /**
     * @param              $entity
     * @param ParameterBag $values
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function populateObjectFromRequest($entity, ParameterBag $values) {
    $config = $this->configForEntity($entity);
    if(array_key_exists('enforce_owner', $config) && $config['enforce_owner']) {
      if(!$ownerVal = $this->getCurrentUser()) {
        throw new AccessDeniedHttpException('AUTH_REQUIRED');
      }
      $setterMethod = 'set' . ucfirst($config['owner_keypath']);
      $entity->$setterMethod($this->getCurrentUser());
    }
    foreach ($config['properties'] as $propName => $accessors) {
      $val = $values->get($propName);
      if(is_null($val)) continue;
      if($item_class = $accessors['as_collection']) {
          if(!is_array($val)) {
              throw new BadRequestHttpException('BAD_REQUEST');
          }
          $em = $this->getDoctrine()->getManager();
          $collection = new ArrayCollection();
          // loop through each of them & make new, and add them to collection.
          foreach($val as $value_spec) {
            $newObj = new $item_class();
              if(array_key_exists('inverse_setter', $accessors)) {
                  $newObj->{$accessors['inverse_setter']}($entity);
              }
            $em->persist($newObj);
            $params = new ParameterBag($value_spec);
            $this->populateObjectFromRequest($newObj, $params);
            $collection->add($newObj);
          }
          $val = $collection;
      } elseif ($accessors['as_reference']) {
        $val = $this->getDoctrine()->getManager()->getReference($accessors['as_reference'], $val);
      }
      $entity->{$accessors['setter']}($val);
    }
    if(is_callable(array($entity, 'initWithParamsFromRESTRequest'))) {
        $entity->initWithParamsFromRESTRequest($values, $this->getRequest());
    }
  }

  protected function getCurrentUserType() {
    return $this->identityResolver->identityTypeKeyForCurrentSession();
  }
  protected function getCurrentUser() {
    return $this->identityResolver->identityObjectForCurrentSession();
  }

  protected function urlComponentToEntityName($fromURL) {
    $components = explode('_', $fromURL);
    $components = array_map("ucfirst", $components);
    return implode('', $components);
  }

  protected function configForEntity($className) {
    $common_name = $className;
    if(is_object($className)) $className = get_class($className);
    if(array_key_exists($className, $this->_config['entities'])) $className = $this->_config['entities'][$className];
    $config = $this->getAnnotations()->getConfiguration($this->getDoctrine()->getManager(), $className);
    $config['class'] = $className;
    $config['common_name'] = $common_name;
    if($config) {
      return $config;
    }
    throw new NotFoundHttpException();
  }

  public function getableOptsActions() {
    $response = new Response('');
    $response->headers->add(array('Allow'=>'GET'));
    return $response;
  }

  /**
   * @return Response
   * @Route("{entity_type}")
   * @Route("{entity_type}/{entity_id}/{prop_name}", requirements={"entity_id" = "\d+"})
   * @Method({"OPTIONS"})
   */
  public function listOptionsAction() {
    $response = new Response('');
    $response->headers->add(array('Allow'=>'GET, POST'));
    return $response;
  }

  /**
   * @return Response
   * @Route("{entity_type}/{entity_id}", requirements={"entity_id" = "\d+"})
   * @Route("{entity_type}/{entity_id}/{prop_name}/{child_id}", requirements={"entity_id" = "\d+", "child_id" = "\d+"})
   * @Method({"OPTIONS"})
   */
  public function singleItemOptionsAction() {
    $response = new Response('');
    $response->headers->add(array('Allow'=>'GET, PUT, DELETE, POST'));
    return $response;
  }

    /**
     * @param string $key
     */
    public function aliasForKey($key) {
        static $used = array();
        $firstChar = mb_substr($key, 0,1);
        $used[$firstChar] = array_key_exists($firstChar, $used) ? $used[$firstChar]++ : 1;
        return $firstChar . $used[$firstChar];
    }

}
