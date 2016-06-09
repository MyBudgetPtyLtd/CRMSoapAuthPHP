<?php
namespace DynamicsCRM\Integration;

use DynamicsCRM\Authentication\CrmUser;
use DynamicsCRM\Authentication\Token\AuthenticationToken;

abstract class AuthenticationCache {
    /**
     * @return AuthenticationToken the cached authentication token or null if none present.
     */
    public abstract function getAuthenticationToken();

    /**
     * @param AuthenticationToken $token the token to cache
     */
    public abstract function storeAuthenticationToken(AuthenticationToken $token);

    /**
     * @return CrmUser the identity of the user
     */
    public abstract function getUserIdentity();

    /**
     * @param CrmUser $user the identity of the current user;
     */
    public abstract function storeUserIdentity($user);
}
