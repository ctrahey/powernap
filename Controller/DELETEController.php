<?php
namespace Trahey\PowerNapBundle\Controller;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Trahey\PowerNapBundle\CRUDEvent;
/**
 * @Route(service="trahey_rest.delete_controller")
 */

class DELETEController extends RESTController
{
  /**********
   * DELETE *
   **********/
  /**
   * @param Request $request
   * @param         $entity_type
   * @param         $entity_id
   * @return \FOS\RestBundle\View\View
   *
   * @Route("{entity_type}/{entity_id}", requirements={"entity_id" = "\d+"})
   * @Method({"DELETE"})
   */
  public function deleteAction(Request $request, $entity_type, $entity_id) {
    $this->request = $request;
    $entity = $this->getEntity($entity_type, $entity_id);
    $em = $this->getDoctrine()->getManager();
    $em->remove($entity);
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
   * @Method({"DELETE"})
   */
  public function deleteChildAction(Request $request, $entity_type, $entity_id, $prop_name, $child_id) {
    $this->request = $request;
    $entity = $this->getEntity($entity_type, $entity_id);
    $child = $this->getEntityMemberChild($entity, $prop_name, $child_id);
    $prop = $this->getEntityProperty($entity, $prop_name);
    if($prop instanceof Collection) {
      $prop->removeElement($child);
      $this->getDoctrine()->getManager()->flush();
    }
    $view = $this->view(NULL,200);
    $view->setFormat('json');
    return $view;
  }
}