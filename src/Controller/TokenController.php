<?php

namespace User\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Service\Helper\AuthResult;
use Laminas\Uri\Uri;
use User\Form\LoginForm;
use SiteUser\Entity\User;
use Doctrine\ORM\EntityManager;
use User\Service\AuthManager;
use User\Service\UserManager;
use User\Service\TokenManager;

/**
 * This controller is responsible for letting the user to log in and log out.
 */
class TokenController extends AbstractActionController
{
    /**
     * Entity manager.
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Auth manager.
     * @var AuthManager
     */
    private $authManager;

    /**
     * User manager.
     * @var UserManager
     */
    private $userManager;
    
    /**
     * @var TokenManager
     */
    private $tokenManager;

    /**
     * @var array $siteUserConfig
     */
    private $siteUserConfig;

    /**
     * TokenController constructor.
     * @param EntityManager $entityManager
     * @param AuthManager $authManager
     * @param UserManager $userManager
     * @param $siteUserConfig
     */
    public function __construct(
        EntityManager $entityManager,
        AuthManager $authManager,
        UserManager $userManager,
        array $siteUserConfig,
        TokenManager $tokenManager
    ) {
        $this->entityManager  = $entityManager;
        $this->authManager    = $authManager;
        $this->userManager    = $userManager;
        $this->siteUserConfig = $siteUserConfig;
        $this->tokenManager   = $tokenManager;
    }

    /**
     * Authenticates user given email address and password credentials.
     */
    public function requestTokenAction()
    {

        if ($this->params()->fromPost('send_tokenlink') == '1') {
            $uri = $this->getRequest()->getUri();
            $baseUrl = sprintf('%s://%s/', $uri->getScheme(), $uri->getHost());

            $generatedToken = $this->tokenManager->generateToken($this->getRequest());

            $this->tokenManager->sendTokenEmail($generatedToken,$baseUrl);

            $this->flashMessenger()->addSuccessMessage('Token sent');
            return $this->redirect()->toRoute('validate-token');
        }

        return new ViewModel([
            'siteUserConfig'    => $this->siteUserConfig
        ]);
    }

    public function validateTokenAction()
    {

        $tokenString = $this->params()->fromQuery('token',null);

        $validToken = null;
        if ($this->getRequest()->isPost()) {
            $tokenCode = $this->params()->fromPost('token_code',null);
            $validToken = $this->tokenManager->validateToken($tokenCode,$this->getRequest());
        } elseif (
            !empty($tokenString) &&
            strlen($tokenString) > 25 &&
            $this->siteUserConfig['token']['only_token_code'] !== true)
        {
            $validToken = $this->tokenManager->validateToken($tokenString,$this->getRequest());
        }

        if ($validToken === true) {
            $this->flashMessenger()->addSuccessMessage('Token activated');
            return $this->redirect()->toRoute($this->siteUserConfig['token']['token_redirect_succes_route']);
        }

        return new ViewModel([
            'siteUserConfig' => $this->siteUserConfig,
            'validToken' => $validToken
        ]);
    }
}
