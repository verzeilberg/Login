<?php

namespace User\Service\Helper;

use Zend\Authentication\Result;

class AuthResult extends Result
{

    /**
     * To many failed login attempts
     */
    const FAILURE_SEC_COUNTER          = -5;

    /**
     * User inactive for to long
     */
    const FAILURE_INACTIVE_TO_LONG      = -6;

    /**
     * Authentication success => First Time login
     */
    const SUCCESS_FIRST_TIME            = 2;

    public function __construct(int $code, $identity, array $messages = [])
    {
        parent::__construct($code, $identity, $messages);
    }
}