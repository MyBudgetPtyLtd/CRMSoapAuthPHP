<?php
namespace DynamicsCRM\Integration;

use DynamicsCRM\Authentication\Token\AuthenticationToken;

class SingleRequestAuthenticationCache extends AuthenticationCache {
    private $token;
    private $userIdentity;

    public function getAuthenticationToken()
    {
        return $this->token;
    }

    public function storeAuthenticationToken(AuthenticationToken $token)
    {
        $this->token = $token;
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