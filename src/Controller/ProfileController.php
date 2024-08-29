<?php

declare(strict_types=1);

namespace User\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Repository\UserRepository;

class ProfileController extends AbstractActionController
{

    /**
     * Entity manager.
     * @var UserRepository
     */
    private $userRepository;

    /**
     * User manager.
     * @var User\Service\UserManager
     */
    private $userManager;

    /**
     * Constructor.
     */
    public function __construct($userRepository, $userManager)
    {
        $this->userRepository = $userRepository;
        $this->userManager = $userManager;
    }

    public function indexAction()
    {


        $page = $this->params()->fromQuery('page', 1);
        $query = $this->userManager->getAllUsers();

        $searchString = '';
        if ($this->getRequest()->isPost()) {
            $searchString = $this->getRequest()->getPost('search');
            $query = $this->userManager->searchUsers($searchString);
        }

        $users = $this->userManager->getUsersForPagination($query, $page, 10);


        return new ViewModel([
            'users' => $users,
        ]);
    }

    public function profileAction()
    {

        die('profile');
        return new ViewModel([
            'user' => $this->currentUser(),
        ]);
    }

    public function editProfileAction()
    {
        return new ViewModel();
    }
}
