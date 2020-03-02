<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace SiteUser;

use Zend\Mvc\MvcEvent;
use Zend\Mvc\Controller\AbstractActionController;
use SiteUser\Controller\AuthController;
use SiteUser\Controller\TokenController;
use SiteUser\Service\AuthManager;
use Zend\Session\Container;

class Module
{
    /**
     * This method returns the path to module.config.php file.
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
    
    /**
     * This method is called once the MVC bootstrapping is complete and allows
     * to register event listeners. 
     */
    public function onBootstrap(MvcEvent $event)
    {
        // Get event manager.
        $eventManager = $event->getApplication()->getEventManager();
        $sharedEventManager = $eventManager->getSharedManager();
        // Register the event listener method. 
        $sharedEventManager->attach(AbstractActionController::class, 
                MvcEvent::EVENT_DISPATCH, [$this, 'onDispatch'], 100);
        
        $sessionManager = $event->getApplication()->getServiceManager()->get('Zend\Session\SessionManager');
        
        $this->forgetInvalidSession($sessionManager);
    }
    
    protected function forgetInvalidSession($sessionManager) 
    {
    	try {
    		$sessionManager->start();
    		return;
    	} catch (\Exception $e) {
    	}
    	/**
    	 * Session validation failed: toast it and carry on.
    	 */
    	// @codeCoverageIgnoreStart
    	session_unset();
    	// @codeCoverageIgnoreEnd
    }
    
    /**
     * Event listener method for the 'Dispatch' event. We listen to the Dispatch
     * event to call the access filter. The access filter allows to determine if
     * the current visitor is allowed to see the page or not. If he/she
     * is not authorized and is not allowed to see the page, we redirect the user 
     * to the login page.
     *
     * @todo check if this realy not getting triggerd when api is called
     * this will work when asking for oauth token but other api classes dont trigger this event
     */
    public function onDispatch(MvcEvent $event)
    {
        // Get controller and action to which the HTTP request was dispatched.
        $controller = $event->getTarget();
        $controllerName = $event->getRouteMatch()->getParam('controller', null);
        $actionName = $event->getRouteMatch()->getParam('action', null);
        
        // Convert dash-style action name to camel-case.
        $actionName = str_replace('-', '', lcfirst(ucwords($actionName, '-')));
        
        // Get the instance of AuthManager service.
        $authManager = $event->getApplication()->getServiceManager()->get(AuthManager::class);

        //Site user config array
        $siteUserconfig = $event->getApplication()->getServiceManager()->get('config')['siteuser'];
        $tokenConfig = $siteUserconfig['token'];


        //ignore_route_keys
        $routerIgnoreKeys = $siteUserconfig['ignore_route_keys'];
        
        $routerKey = $event->getRouteMatch()->getMatchedRouteName();
    
        //return if ignore route in key list
        if (in_array($routerKey,$routerIgnoreKeys)) {return;}

        $routerIgnorePartial = false;
        //check if route key contains .rest.
        $routerIgnoreKeysPartial = $siteUserconfig['ignore_route_keys_partial'];
    
        foreach ($routerIgnoreKeysPartial as $routerIgnoreKeyPartial) {
            $pos = strpos($routerKey, $routerIgnoreKeyPartial);
            if ($pos === false) { continue; }
            //stop foreach if found
            $routerIgnorePartial = true;
            break;
        }
    
    
        if ($routerIgnorePartial) {return;}
        //zend auth service to get identity
        $authenticationService = $event->getApplication()->getServiceManager()->get(\Zend\Authentication\AuthenticationService::class);


        // UserToken auth
        // Execute the token check on every controller except AuthController
        // (to avoid infinite redirect)


        if ($authenticationService->hasIdentity() &&
            $tokenConfig['active'] == true        &&
            $controllerName != AuthController::class &&
            $controllerName != TokenController::class
        ) {

            $datetime = new \DateTime("now");
            $tokenContainer = new Container('UserToken');

            //check if token is set and is valid
            if (isset($tokenContainer->token_valid_until) && $tokenContainer->token_valid_until > $datetime->getTimestamp()) {
                if ($tokenConfig['reset_token_time']) {
                    //amount of hours to extend token
                    $hoursValid = intval($tokenConfig['hours_token_valid']);
                    $modifyHours = "+" . $hoursValid . " hours";
                    $datetime->modify($modifyHours);

                    //update token valid until time
                    $tokenContainer->token_valid_until = $datetime->getTimestamp();
                }

                // Additional token check if the token has the same user id
                if ($tokenContainer->token_user_id != $authenticationService->getIdentity()->getId()
                ) {
                    $tokenContainer->token_valid_until = null;
                    return $controller->redirect()->toRoute('request-token');
                }

                //php session check
                if ($tokenContainer->session_id != session_id()) {
                    $tokenContainer->token_valid_until = null;
                    return $controller->redirect()->toRoute('request-token');
                }

                //IP check
                if ($event->getApplication()->getRequest()->getServer('REMOTE_ADDR') != $tokenContainer->token_ip) {
                    $tokenContainer->token_valid_until = null;
                    return $controller->redirect()->toRoute('request-token');
                }
            } else {
                return $controller->redirect()->toRoute('request-token');
            }
        }

        //Status check of user
        if ($controllerName != AuthController::class && get_class($event->getApplication()->getRequest()) !== \Zend\Console\Request::class)
        {
            //if not true it means the user is blocked
            if(!$authManager->statusCheck()) {
                $authManager->logout();
            }
        }


        // Execute the access filter on every controller except AuthController
        // (to avoid infinite redirect).
        // reload user on redirect
        // reload roles on redirect
        // remove $authManager->reloadUser(); to enable caching but this will have impact on update user and roles
        if ($controllerName != AuthController::class && get_class($event->getApplication()->getRequest()) !== \Zend\Console\Request::class)
        {
            if ($authenticationService->hasIdentity()) {
                $authManager->reloadUser();//this wont do roles these are changed in rbacManager with this function !
            }

            $result = $authManager->filterAccess($controllerName, $actionName);

            if ($result==AuthManager::AUTH_REQUIRED) {
                // Remember the URL of the page the user tried to access. We will
                // redirect the user to that URL after successful login.
                $uri = $event->getApplication()->getRequest()->getUri();
                // Make the URL relative (remove scheme, user info, host name and port)
                // to avoid redirecting to other domain by a malicious user.
                $uri->setScheme(null)
                    ->setHost(null)
                    ->setPort(null)
                    ->setUserInfo(null);
                $redirectUrl = $uri->toString();

                // Redirect the user to the "Login" page.
                return $controller->redirect()->toRoute('login', [], 
                        ['query'=>['redirectUrl'=>$redirectUrl]]);
            }
            else if ($result==AuthManager::ACCESS_DENIED) {
                // Redirect the user to the "Not Authorized" page.
                return $controller->redirect()->toRoute('not-authorized');
            }
        }
    }
}
