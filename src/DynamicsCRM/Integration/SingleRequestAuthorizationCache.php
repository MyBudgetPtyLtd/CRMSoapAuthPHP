<?php
namespace DynamicsCRM\Integration;

use DynamicsCRM\Authorization\Token\AuthenticationToken;

class SingleRequestAuthorizationCache extends AuthorizationCache {
    private $Token;
    private $userIdentity;

    public function getAuthorizationToken()
    {
        return $this->Token;
    }

    public function storeAuthorizationToken(AuthenticationToken $token)
    {
        $this->Token = $token;
    }

    public function getUserIdentity()
    {
        return $this->userIdentity;
    }

    public function storeUserIdentity($userIdentity)
    {
        $this->userIdentity = $userIdentity;
    }
}