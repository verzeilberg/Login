<?php
namespace User\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Entity\User;
use User\Entity\Role;
use User\Form\UserForm;
use User\Form\PasswordChangeForm;
use User\Form\PasswordResetForm;

/**
 * This controller is responsible for user management (adding, editing,
 * viewing users and changing user's password).
 */
class UserController extends AbstractActionController
{
    /**
     * Entity manager.
     * @var Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * User manager.
     * @var User\Service\UserManager
     */
    private $userManager;

    /**
     * Constructor.
     */
    public function __construct($entityManager, $userManager)
    {
        $this->entityManager = $entityManager;
        $this->userManager = $userManager;
    }

    /**
     * This is the default "index" action of the controller. It displays the
     * list of users.
     */
    public function indexAction()
    {
        $this->layout('layout/beheer');
        // Access control.
        if (!$this->access('user.manage')) {
            $this->getResponse()->setStatusCode(401);
            return;
        }

        $page = $this->params()->fromQuery('page', 1);

        $query = $this->entityManager->getRepository(User::class)
                ->getAllUsers();

        $paginator = $this->entityManager->getRepository(User::class)
            ->getUsersForPagination($query, $page);

        return new ViewModel([
            'users' => $paginator
        ]);
    }

    /**
     * This action displays a page allowing to add a new user.
     */
    public function addAction()
    {
        $this->layout('layout/beheer');
        // Create user form
        $form = new UserForm('create', $this->entityManager);

        // Get the list of all available roles (sorted by name).
        $allRoles = $this->entityManager->getRepository(Role::class)
                ->findBy([], ['name'=>'ASC']);
        $roleList = [];
        foreach ($allRoles as $role) {
            $roleList[$role->getId()] = $role->getName();
        }

        $form->get('roles')->setValueOptions($roleList);

        // Check if user has submitted the form
        if ($this->getRequest()->isPost()) {

            // Fill in the form with POST data
            $data = $this->params()->fromPost();

            $form->setData($data);

            // Validate form
            if($form->isValid()) {

                // Get filtered and validated data
                $data = $form->getData();

                // Add user.
                $user = $this->userManager->addUser($data);

                // Redirect to "view" page
                return $this->redirect()->toRoute('users',
                        ['action'=>'view', 'id'=>$user->getId()]);
            }
        }

        return new ViewModel([
                'form' => $form
            ]);
    }

    /**
     * The "view" action displays a page allowing to view user's details.
     */
    public function viewAction()
    {
        $this->layout('layout/beheer');
        $id = (int)$this->params()->fromRoute('id', -1);
        if ($id<1) {
            $this->getResponse()->setStatusCode(404);
            return;
        }

        // Find a user with such ID.
        $user = $this->entityManager->getRepository(User::class)
                ->find($id);

        if ($user == null) {
            $this->getResponse()->setStatusCode(404);
            return;
        }

        return new ViewModel([
            'user' => $user
        ]);
    }

    /**
     * The "edit" action displays a page allowing to edit user.
     */
    public function editAction()
    {
        $this->layout('layout/beheer');
        $id = (int)$this->params()->fromRoute('id', -1);
        if ($id<1) {
            $this->getResponse()->setStatusCode(404);
            return;
        }

        $user = $this->entityManager->getRepository(User::class)
                ->find($id);

        if ($user == null) {
            $this->getResponse()->setStatusCode(404);
            return;
        }

        // Create user form
        $form = new UserForm('update', $this->entityManager, $user);

        // Get the list of all available roles (sorted by name).
        $allRoles = $this->entityManager->getRepository(Role::class)
                ->findBy([], ['name'=>'ASC']);
        $roleList = [];
        foreach ($allRoles as $role) {
            $roleList[$role->getId()] = $role->getName();
        }

        $form->get('roles')->setValueOptions($roleList);

        // Check if user has submitted the form
        if ($this->getRequest()->isPost()) {

            // Fill in the form with POST data
            $data = $this->params()->fromPost();

            $form->setData($data);

            // Validate form
            if($form->isValid()) {

                // Get filtered and validated data
                $data = $form->getData();

                // Update the user.
                $this->userManager->updateUser($user, $data);

                // Redirect to "view" page
                return $this->redirect()->toRoute('users',
                        ['action'=>'view', 'id'=>$user->getId()]);
            }
        } else {

            $userRoleIds = [];
            foreach ($user->getRoles() as $role) {
                $userRoleIds[] = $role->getId();
            }

            $form->setData(array(
                    'full_name'=>$user->getFullName(),
                    'email'=>$user->getEmail(),
                    'status'=>$user->getStatus(),
                    'roles' => $userRoleIds
                ));
        }

        return new ViewModel(array(
            'user' => $user,
            'form' => $form
        ));
    }

    /**
     * This action displays a page allowing to change user's password.
     */
    public function changePasswordAction()
    {
        $this->layout('layout/beheer');
        $id = (int)$this->params()->fromRoute('id', -1);
        if ($id<1) {
            $this->getResponse()->setStatusCode(404);
            return;
        }

        $user = $this->entityManager->getRepository(User::class)
                ->find($id);

        if ($user == null) {
            $this->getResponse()->setStatusCode(404);
            return;
        }

        // Create "change password" form
        $form = new PasswordChangeForm('change');

        // Check if user has submitted the form
        if ($this->getRequest()->isPost()) {

            // Fill in the form with POST data
            $data = $this->params()->fromPost();

            $form->setData($data);

            // Validate form
            if($form->isValid()) {

                // Get filtered and validated data
                $data = $form->getData();

                // Try to change password.
                if (!$this->userManager->changePassword($user, $data)) {
                    $this->flashMessenger()->addErrorMessage(
                            'Sorry, the old password is incorrect. Could not set the new password.');
                } else {
                    $this->flashMessenger()->addSuccessMessage(
                            'Changed the password successfully.');
                }

                // Redirect to "view" page
                return $this->redirect()->toRoute('users',
                        ['action'=>'view', 'id'=>$user->getId()]);
            }
        }

        return new ViewModel([
            'user' => $user,
            'form' => $form
        ]);
    }

    /**
     * This action displays the "Reset Password" page.
     */
    public function resetPasswordAction()
    {
        // Create form
        $form = new PasswordResetForm();

        // Check if user has submitted the form
        if ($this->getRequest()->isPost()) {

            // Fill in the form with POST data
            $data = $this->params()->fromPost();

            $form->setData($data);

            // Validate form
            if($form->isValid()) {

                // Look for the user with such email.
                $user = $this->entityManager->getRepository(User::class)
                        ->findOneByEmail($data['email']);
                if ($user!=null && $user->getStatus() == User::STATUS_ACTIVE) {
                    // Generate a new password for user and send an E-mail
                    // notification about that.
                    $this->userManager->generatePasswordResetToken($user);

                    // Redirect to "message" page
                    return $this->redirect()->toRoute('users',
                            ['action'=>'message', 'id'=>'sent']);
                } else {
                    return $this->redirect()->toRoute('users',
                            ['action'=>'message', 'id'=>'invalid-email']);
                }
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }

    /**
     * This action displays an informational message page.
     * For example "Your password has been resetted" and so on.
     */
    public function messageAction()
    {
        // Get message ID from route.
        $id = (string)$this->params()->fromRoute('id');

        // Validate input argument.
        if($id!='invalid-email' && $id!='sent' && $id!='set' && $id!='failed') {
            throw new \Exception('Invalid message ID specified');
        }

        return new ViewModel([
            'id' => $id
        ]);
    }

    /**
     * This action displays the "Reset Password" page.
     */
    public function setPasswordAction()
    {
        $email = $this->params()->fromQuery('email', null);
        $token = $this->params()->fromQuery('token', null);

        // Validate token length
        if ($token!=null && (!is_string($token) || strlen($token)!=32)) {
            throw new \Exception('Invalid token type or length');
        }

        if($token===null ||
           !$this->userManager->validatePasswordResetToken($email, $token)) {
            return $this->redirect()->toRoute('users',
                    ['action'=>'message', 'id'=>'failed']);
        }

        // Create form
        $form = new PasswordChangeForm('reset');

        // Check if user has submitted the form
        if ($this->getRequest()->isPost()) {

            // Fill in the form with POST data
            $data = $this->params()->fromPost();

            $form->setData($data);

            // Validate form
            if($form->isValid()) {

                $data = $form->getData();

                // Set new password for the user.
                if ($this->userManager->setNewPasswordByToken($email, $token, $data['new_password'])) {

                    // Redirect to "message" page
                    return $this->redirect()->toRoute('users',
                            ['action'=>'message', 'id'=>'set']);
                } else {
                    // Redirect to "message" page
                    return $this->redirect()->toRoute('users',
                            ['action'=>'message', 'id'=>'failed']);
                }
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }
}


