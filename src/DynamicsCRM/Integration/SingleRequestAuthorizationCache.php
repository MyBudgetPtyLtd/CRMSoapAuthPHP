<?php
namespace DynamicsCRM\Integration;

use DynamicsCRM\Auth\Token\AuthenticationToken;

class SingleRequestAuthorizationCache extends AuthorizationCache {
    private $Token;

    public function getAuthorizationToken()
    {
        return $this->Token;
    }

    public function storeAuthorizationToken(AuthenticationToken $token)
    {
        $this->Token = $token;
    }
}