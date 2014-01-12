<?php
namespace Trahey\RestBundle;

interface IdentityResolver {
    /**
     * @return mixed an object which represents the currently logged-in user
     */
    public function identityObjectForCurrentSession();

    /**
     * @return string a key which disambiguates the 'type' of user logged in. Perhaps a role.
     */
    public function identityTypeKeyForCurrentSession();
}