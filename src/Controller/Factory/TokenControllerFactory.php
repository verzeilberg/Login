<?php
namespace User\Controller\Factory;

use Interop\Container\ContainerInterface;
use User\Controller\TokenController;
use Zend\ServiceManager\Factory\FactoryInterface;
use User\Service\AuthManager;
use User\Service\UserManager;
use User\Service\TokenManager;

/**
 * This is the factory for AuthController. Its purpose is to instantiate the controller
 * and inject dependencies into its constructor.
 */
class TokenControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $entityManager = $container->get('doctrine.entitymanager.orm_default');
        $authManager = $container->get(AuthManager::class);
        $userManager = $container->get(UserManager::class);
        $siteUserConfig = $container->get('config')['user'];
        $tokenManager = $container->get(TokenManager::class);
        return new TokenController($entityManager, $authManager, $userManager, $siteUserConfig,$tokenManager);
    }
}
