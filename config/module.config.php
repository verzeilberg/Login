<?php

namespace User;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

return [
    'login_settings' => [
        'mailing_settings' => [
            'password_reset_token_mail' => [
                'subject' => 'Password Reset',
                'sender_mail' => 'no-reply@example.com',
                'sender_name' => 'User Demo',
            ]
        ]
    ],
    'router' => [
        'routes' => [
            'login' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/login',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'login',
                    ],
                ],
            ],
            'logout' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/logout',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'logout',
                    ],
                ],
            ],
            'not-authorized' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/not-authorized',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'notAuthorized',
                    ],
                ],
            ],
            'reset-password' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/reset-password',
                    'defaults' => [
                        'controller' => Controller\UserController::class,
                        'action' => 'resetPassword',
                    ],
                ],
            ],
            'set-password' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/set-password',
                    'defaults' => [
                        'controller' => Controller\UserController::class,
                        'action' => 'setPassword',
                    ],
                ],
            ],
            'users' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/users[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        'controller' => 'userbeheer',
                        'action' => 'index',
                    ],
                ],
            ],
            'roles' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/roles[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]*',
                    ],
                    'defaults' => [
                        'controller' => 'rolebeheer',
                        'action' => 'index',
                    ],
                ],
            ],
            'permissions' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/permissions[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]*',
                    ],
                    'defaults' => [
                        'controller' => 'permissionbeheer',
                        'action' => 'index',
                    ],
                ],
            ],
            'request-token' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/request-token',
                    'defaults' => [
                        'controller' => Controller\TokenController::class,
                        'action' => 'requestToken',
                    ],
                ],
            ],
            'validate-token' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/validate-token',
                    'defaults' => [
                        'controller' => Controller\TokenController::class,
                        'action' => 'validateToken',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\AuthController::class => Controller\Factory\AuthControllerFactory::class,
            Controller\PermissionController::class => Controller\Factory\PermissionControllerFactory::class,
            Controller\RoleController::class => Controller\Factory\RoleControllerFactory::class,
            Controller\UserController::class => Controller\Factory\UserControllerFactory::class,
            Controller\TokenController::class => Controller\Factory\TokenControllerFactory::class,
        ],
        'aliases' => [
            'permissionbeheer' => Controller\PermissionController::class,
            'rolebeheer' => Controller\RoleController::class,
            'userbeheer' => Controller\UserController::class
        ],
    ],
    // We register module-provided controller plugins under this key.
    'controller_plugins' => [
        'factories' => [
            Controller\Plugin\AccessPlugin::class => Controller\Plugin\Factory\AccessPluginFactory::class,
            Controller\Plugin\CurrentUserPlugin::class => Controller\Plugin\Factory\CurrentUserPluginFactory::class,
        ],
        'aliases' => [
            'access' => Controller\Plugin\AccessPlugin::class,
            'currentUser' => Controller\Plugin\CurrentUserPlugin::class,
        ],
    ],
    // The 'access_filter' key is used by the User module to restrict or permit
    // access to certain controller actions for unauthorized visitors.
    'access_filter' => [
        'controllers' => [
            'userbeheer' => [
                // Give access to "resetPassword", "message" and "setPassword" actions
                // to anyone.
                ['actions' => ['resetPassword', 'message', 'setPassword'], 'allow' => '*'],
                // Give access to "index", "add", "edit", "view", "changePassword" actions to users having the "user.manage" permission.
                ['actions' => ['index', 'add', 'edit', 'view', 'changePassword'], 'allow' => '+user.manage']
            ],
            'rolebeheer' => [
                // Allow access to authenticated users having the "role.manage" permission.
                ['actions' => '*', 'allow' => '+role.manage']
            ],
            'permissionbeheer' => [
                // Allow access to authenticated users having "permission.manage" permission.
                ['actions' => '*', 'allow' => '+permission.manage']
            ],
            Controller\TokenController::class => [
                //All logged in user can have acces to token controller and actions
                ['actions' => ['requestToken', 'validateToken'], 'allow' => '@']
            ],
        ]
    ],
    'service_manager' => [
        'factories' => [
            \Laminas\Authentication\AuthenticationService::class => Service\Factory\AuthenticationServiceFactory::class,
            Service\AuthAdapter::class => Service\Factory\AuthAdapterFactory::class,
            Service\AuthManager::class => Service\Factory\AuthManagerFactory::class,
            Service\PermissionManager::class => Service\Factory\PermissionManagerFactory::class,
            Service\RbacManager::class => Service\Factory\RbacManagerFactory::class,
            Service\RoleManager::class => Service\Factory\RoleManagerFactory::class,
            Service\UserManager::class => Service\Factory\UserManagerFactory::class,
            Service\TokenManager::class => Service\Factory\TokenManagerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    // We register module-provided view helpers under this key.
    'view_helpers' => [
        'factories' => [
            View\Helper\Access::class => View\Helper\Factory\AccessFactory::class,
            View\Helper\CurrentUser::class => View\Helper\Factory\CurrentUserFactory::class,
        ],
        'aliases' => [
            'access' => View\Helper\Access::class,
            'currentUser' => View\Helper\CurrentUser::class,
        ],
    ],
    'doctrine' => [
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => AnnotationDriver::class,
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/Entity']
            ],
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver'
                ]
            ]
        ]
    ],
    // Configuration for your SMTP server (for sending outgoing mail).
    'smtp' => [
        'name' => 'localhost.localdomain',
        'host' => '127.0.0.1',
        'port' => 25,
        'connection_class' => 'plain',
        'connection_config' => [
            'username' => '<user>',
            'password' => '<pass>',
        ],
    ],
    'user' => [
        'token' => [
            'active' => true,

            'hours_token_valid' => 8,
            'reset_token_time' => true, //this will reset with any request but auth and token controller

            'only_token_code' => false, //if true only code will be send to user no link with token
            'defaultLogo' => 'img/bg.jpg',
            'mailtoken_img_alt' => 'verzeilberg.nl',
            'token_redirect_succes_route' => 'home', //@todo make function to give redirect optional for each role

            'token_mail_subject' => 'Inlog token Verzeilberg.nl',
            'token_mail_sender_email' => 'info@verzeilberg.nl',
            'token_mail_sender_name' => 'Verzeilberg',
            'token_mail_reply_email' => 'info@verzeilberg.nl',
            'token_mail_reply_name' => 'Verzeilberg',
            'token_mail_template' => 'user/templates/token_email.phtml',

            'mailtoken_img_alt' => 'Logo Verzeilberg',
            'mailtoken_header_name' => 'Verzeilberg',
            'mailtoken_header_style' => 'font-size: 26px;  color: #E31C23;',
            'mailtoken_no_mail_msg' => '<a href="mailto:info@verzeilberg.nl">Verzeilberg.nl</a>',
            'token_general_agreement' => false,
            'licence_agreement_partial' => 'user/templates/algemene_voorwaarden.phtml',

            'licence_agreement_check' => true, // not in use
            'licence_agreement_check_hours' => 12,// not in use


        ],
        'ignore_route_keys' => [
            'api',
            'oauth',
        ],
        'ignore_route_keys_partial' => [
            //'.rest.',
            '.rpc.',
            'api-tools',
        ],
    ],
];
