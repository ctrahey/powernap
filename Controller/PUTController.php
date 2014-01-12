<?php
namespace Trahey\RestBundle\Controller;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Trahey\RestBundle\CRUDEvent;
/**
 * @Route(service="trahey_rest.put_controller")
 */

class PUTController extends RESTController
{
  /********
   * PUT  *
   ********/
  /**
   * @param Request $request
   * @param         $entity_type
   * @param         $entity_id
   * @return \FOS\RestBundle\View\View
   *
   * @Route("{entity_type}/{entity_id}", requirements={"entity_id" = "\d+"})
   * @Method({"PUT"})
   */
  public function putAction(Request $request, $entity_type, $entity_id) {
    $this->request = $request;
    $entity = $this->getEntity($entity_type, $entity_id);
    $config = $this->configForEntity($entity_type);
    $this->populateObjectFromRequest($entity, $request->request);
    $em = $this->getDoctrine()->getManager();
    $em->flush();
    $view = $this->view($entity, 200);
    $view->setFormat('json');
    return $view;
  }

  /**
   * @param Request $request
   * @param         $entity_type
   * @param         $entity_id
   * @param         $prop_name
   * @param         $child_id
   * @return \FOS\RestBundle\View\View
   *
   * @Route("{entity_type}/{entity_id}/{prop_name}/{child_id}", requirements={"entity_id" = "\d+", "child_id" = "\d+"})
   * @Method({"PUT"})
   */
  public function putMemberChildAction(Request $request, $entity_type, $entity_id, $prop_name, $child_id) {
    $this->request = $request;
    $entity = $this->getEntity($entity_type, $entity_id);
    $child = $this->getEntityMemberChild($entity, $prop_name, $child_id);
    $this->populateObjectFromRequest($child, $request->request);
    $this->getDoctrine()->getManager()->flush();
    $view = $this->view($child, 200);
    $view->setFormat('json');
    return $view;
  }
}