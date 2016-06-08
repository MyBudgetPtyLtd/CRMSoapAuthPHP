<?php
namespace DynamicsCRM\Auth\Token;

abstract class AuthenticationToken
{
    public $Expires;
    public $Token1;
    public $Token2;
    public $KeyIdentifier;
    public $Url;

    public abstract function CreateSoapHeader($action);
}
