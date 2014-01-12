<?php
namespace Trahey\PowerNapBundle;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Gedmo\Mapping\MappedEventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
class EntityAnnotationListener extends MappedEventSubscriber implements ContainerAwareInterface{
  /**
   * @var ContainerInterface
   */
  protected $container;

  public function setContainer(ContainerInterface $container = null)
  {
    $this->container = $container;
  }
  /**
   * Specifies the list of events to listen
   *
   * @return array
   */
  public function getSubscribedEvents()
  {
    return array(
      'loadClassMetadata',
    );
  }
  /**
   * Mapps additional metadata
   *
   * @param EventArgs $eventArgs
   * @return void
   */
  public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
  {
    $ea = $this->getEventAdapter($eventArgs);
    $this->loadMetadataForObjectClass($ea->getObjectManager(), $eventArgs->getClassMetadata());
  }

  /**
   * {@inheritDoc}
   */
  protected function getNamespace()
  {
    return __NAMESPACE__;
  }
}