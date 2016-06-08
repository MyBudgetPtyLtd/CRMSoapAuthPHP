<?php
namespace DynamicsCRM\Integration;

use DynamicsCRM\Authorization\CrmUser;
use DynamicsCRM\Authorization\Token\AuthenticationToken;

abstract class AuthorizationCache {
    /**
     * @return AuthenticationToken the cached authentication token or null if none present.
     */
    public abstract function getAuthorizationToken();

    /**
     * @param AuthenticationToken $token the token to cache
     */
    public abstract function storeAuthorizationToken(AuthenticationToken $token);

    /**
     * @return CrmUser the identity of the user
     */
    public abstract function getUserIdentity();

    /**
     * @param CrmUser $user the identity of the current user;
     */
    public abstract function storeUserIdentity($user);
}
