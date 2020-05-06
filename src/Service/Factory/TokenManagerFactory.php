<?php
namespace User\Service\Factory;

use Interop\Container\ContainerInterface;
use User\Service\TokenManager;

/**
 * This is the factory class for UserManager service. The purpose of the factory
 * is to instantiate the service and pass it dependencies (inject dependencies).
 */
class TokenManagerFactory
{
    /**
     * This method creates the UserManager service and returns its instance. 
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {        
        $entityManager = $container->get('doctrine.entitymanager.orm_default');
        $siteUserConfig = $container->get('config')['user'];
        $authenticationService = $container->get(\Laminas\Authentication\AuthenticationService::class);
        $viewRenderer = $container->get('ViewRenderer');

        return new TokenManager($entityManager, $siteUserConfig, $authenticationService, $viewRenderer);
    }
}
