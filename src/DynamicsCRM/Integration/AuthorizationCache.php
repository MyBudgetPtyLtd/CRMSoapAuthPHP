<?php
namespace DynamicsCRM\Integration;

use DynamicsCRM\Auth\Token\AuthenticationToken;

abstract class AuthorizationCache {
    /**
     * @return AuthenticationToken the cached authentication token or null if none present.
     */
    public abstract function getAuthorizationToken();

    /**
     * @param AuthenticationToken $token the token to cache
     */
    public abstract function storeAuthorizationToken(AuthenticationToken $token);
}
