<?php
namespace Trahey\PowerNapBundle\Controller;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\File\File;
/**
 * @Route(service="trahey_rest.get_controller")
 */
class GETController extends RESTController
{
  /********
   * GET  *
   ********/

  /**
   * Get a list of entities
   * @param Request $request
   * @param string $entity_type
   * @return \FOS\RestBundle\View\View
   * @Route("{entity_type}")
   * @Method({"GET"})
   * @todo presently "null means public" is fixed. This should perhaps be configurable via the OwnerKeypath annotation
   */
  public function listAction(Request $request, $entity_type) {
    /**
     * @var \Doctrine\ORM\EntityRepository $repo
     */
    $this->request = $request;
    $config = $this->configForEntity($entity_type);
    $repo = $this->getDoctrine()->getRepository($config['class']);
    if($config['enforce_owner']) {
      $curUserType = $this->getCurrentUserType();
      $curUser = $this->getCurrentUser();
        $query = $repo->createQueryBuilder('e');
      if($curUserType == $config['owner_type']) {
          $predicate = sprintf('e.%s = :owner', $config['owner_keypath']);
          $query->where($predicate)->orWhere('e.'.$config['owner_keypath'] . ' IS NULL');
          $query->setParameter('owner', $this->getCurrentUser());
      } elseif(array_key_exists('read_grants', $config) && array_key_exists($curUserType, $config['read_grants'])){
          $grant = $config['read_grants'][$curUserType];
          $pathParts = explode('.', $grant['keypath']);
          $terminalPart = array_pop($pathParts);
          $prevAlias = 'e';
          switch($grant['type']) {
              case 'entity_to_user':
                  foreach($pathParts as $partToJoin) {
                      $penultimateAlias = $this->aliasForKey($partToJoin);
                      $query->join($prevAlias . '.' . $partToJoin, $penultimateAlias);
                      $prevAlias = $penultimateAlias;
                  }
                  $predicate = sprintf('%s.%s = :user', $penultimateAlias, $terminalPart);
                  $query->where($predicate)->orWhere('e.'.$config['owner_keypath'] . ' IS NULL');
                  $query->setParameter('user', $curUser);
                  break;
              case 'user_to_entity':
                  break;
          }
      }
      $data = $query->getQuery()->execute();
    } else {
      $data = $repo->findAll();
    }
    $view = $this->view($data, 200);
    $view->setFormat('json');
    return $view;
  }

  /**
   * Get a single entity
   * @param Request $request
   * @param string $singular_entity_name
   * @param integer $entity_id
   * @return \FOS\RestBundle\View\View
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *
   * @Route("{entity_type}/{entity_id}", requirements={"entity_id" = "\d+"})
   * @Method({"GET"})
   */
  public function getAction(Request $request, $entity_type, $entity_id) {
    $this->request = $request;
    $entity = $this->getEntity($entity_type, $entity_id);
      // check for file-passthrough
      $config = $this->configForEntity($entity);
      if($getter = $config['file_passthrough_getter']) {
          /**
           * @var $file File
           */
          $file = $entity->$getter();
          $fp = fopen($file->getPathname(), 'rb');
          ob_end_clean();
          $type = "Content-Type: " . $file->getMimeType();
          $length = "Content-Length: " . $file->getSize();
          header($type);
          header($length);
          fpassthru($fp);
          exit;
      } else {
          $view = $this->view($entity, 200);
          $view->setFormat('json');
          return $view;
      }
  }

  /**
   * Get a member property of the specified entity
   * @param Request $request
   * @param string $entity_type
   * @param integer $entity_id
   * @param string $prop_name
   * @return \FOS\RestBundle\View\View
   *
   * @Route("{entity_type}/{entity_id}/{prop_name}", requirements={"entity_id" = "\d+"})
   * @Method({"GET"})
   */
  public function getMemberAction(Request $request, $entity_type, $entity_id, $prop_name) {
    $this->request = $request;
    $entity = $this->getEntity($entity_type, $entity_id);
    $member = $this->getEntityProperty($entity, $prop_name);
    $view = $this->view($member, 200);
    $view->setFormat('json');
    return $view;
  }

  /**
   * Get a member of a collection owned by the specified entity
   * @param Request $request
   * @param string $entity_type
   * @param integer $entity_id
   * @param string $prop_name
   * @param integer $child_id
   * @return \FOS\RestBundle\View\View
   *
   * @Route("{entity_type}/{entity_id}/{prop_name}/{child_id}", requirements={"entity_id" = "\d+", "child_id" = "\d+"})
   * @Method({"GET"})
   */
  public function getMemberChildAction(Request $request, $entity_type, $entity_id, $prop_name, $child_id) {
    $this->request = $request;
    $entity = $this->getEntity($entity_type, $entity_id);
    $child = $this->getEntityMemberChild($entity, $prop_name, $child_id);
    $view = $this->view($child, 200);
    $view->setFormat('json');
    return $view;
  }

}