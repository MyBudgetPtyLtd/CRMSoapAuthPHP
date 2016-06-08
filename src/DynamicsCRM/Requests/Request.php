<?php
namespace DynamicsCRM\Requests;

abstract class Request
{
    public abstract function getAction();
    public abstract function getRequestXML();
}