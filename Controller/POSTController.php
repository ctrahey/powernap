<?php
namespace Trahey\RestBundle\Controller;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Trahey\RestBundle\CRUDEvent;
/**
 * @Route(service="trahey_rest.post_controller")
 */

class POSTController extends RESTController
{
  /********
   * POST *
   ********/
  /**
   * @param Request $request
   * @param string $entity_type
   * @return \FOS\RestBundle\View\View
   *
   * @Route("{entity_type}")
   * @Method({"POST"})
   */
  public function postAction(Request $request, $entity_type) {
    $this->request = $request;
    $config = $this->configForEntity($entity_type);
    $className = $config['class'];
    $entity = $this->createEntityFromRequest($className, $config);
    $view = $this->view($entity, 201);
    $view->setFormat('json');
    return $view;
  }

  /**
   * @param Request $request
   * @param string $entity_type
   * @param integer $entity_id
   * @param string $prop_name
   * @return \FOS\RestBundle\View\View
   *
   * @Route("{entity_type}/{entity_id}/{prop_name}", requirements={"entity_id" = "\d+"})
   * @Method({"POST"})
   */
  public function appendChildOrInvokeAction(Request $request, $entity_type, $entity_id, $prop_name) {
    $this->request = $request;
    $entity = $this->getEntity($entity_type, $entity_id);
    $config = $this->configForEntity($entity_type);
    try {
      $prop = $this->getEntityProperty($entity, $prop_name);
      if($prop instanceof Collection) {
        // This is an append operation
        // if the request has an id, then we assume the object already exists
        if($child_id = $this->getRequest()->get('id')) {
          // in this case, the request is assumed to represent an existing object
          $child_entity = $this->getEntityByIdForMember($entity, $prop_name, $child_id);
        } else {
          // if there is no id in the request, make a new one!
          $className = $this->getClassNameForProperty($entity, $prop_name);
          $child_entity = $this->createEntityFromRequest($className, $config);
        }
        $prop->add($child_entity);
        $this->getDoctrine()->getManager()->flush();
        $data = $child_entity;
      } elseif(is_string($prop)) {
      }
    } catch (NotFoundHttpException $e) {
      // This is an invocation
      $newEvent = new CRUDEvent($entity, $this->getRequest());
      $evenFormat = 'trahey_rest.%s.call.%s';
      $eventName = sprintf($evenFormat, $config['common_name'], $prop_name);
      $this->getEventDispatcher()->dispatch($eventName, $newEvent);
      $data = $newEvent->getResponseData();
    }

    $view = $this->view($data, 200);
    $view->setFormat('json');
    return $view;
  }

}