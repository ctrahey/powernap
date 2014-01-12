<?php
namespace Trahey\RestBundle;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class CRUDEvent extends Event
{
  protected $entity;
  protected $request;
  protected $response_data;

  public function __construct($entity, Request $request)
  {
    $this->entity = $entity;
    $this->request = $request;
  }

  public function getEntity()
  {
    return $this->entity;
  }
  public function getRequest() {
    return $this->request;
  }
  public function getResponseData() {
    return $this->response_data;
  }
  public function setResponseData($data) {
    $this->response_data = $data;
  }
}