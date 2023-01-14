<?php
namespace User\Controller\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use User\Controller\ProfileController;
use User\Service\UserManager;

/**
 * This is the factory for ProfileController. Its purpose is to instantiate the
 * controller and inject dependencies into it.
 */
class ProfileControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ProfileController
    {
        $entityManager = $container->get('doctrine.entitymanager.orm_default');
        $userManager = $container->get(UserManager::class);
        
        // Instantiate the controller and inject dependencies
        return new ProfileController($entityManager, $userManager);
    }
}