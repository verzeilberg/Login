<?php
namespace User\Service;

use User\Entity\User;
use Laminas\Math\Rand;
use Laminas\Authentication\AuthenticationService;
use Doctrine\ORM\EntityManager;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\Mail;
use Laminas\Mime\Part as MimePart;
use Laminas\Mime\Message as MimeMessage;
use Laminas\View\Model\ViewModel;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Hostname;
use Laminas\Mail\Message;
use User\Entity\Token;
use Laminas\Session\Container;

/**
 * This service is responsible for adding/editing users
 * and changing user password.
 */
class TokenManager
{
    /**
     * Doctrine entity manager.
     * @var Doctrine\ORM\EntityManager
     */
    private $entityManager;  
      /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @var PhpRenderer $viewRenderer
     */
    private $viewRenderer;

    /**
     * TokenManager constructor.
     * @param EntityManager $entityManager
     * @param $siteUserConfig
     * @param AuthenticationService $authenticationService
     * @param PhpRenderer $viewRenderer
     */
    public function __construct(EntityManager $entityManager,
        $siteUserConfig,
        AuthenticationService $authenticationService,
        PhpRenderer $viewRenderer)
    {
        $this->entityManager = $entityManager;
        $this->siteUserConfig = $siteUserConfig;

        $this->authenticationService = $authenticationService;
        $this->viewRenderer = $viewRenderer;
    }


    /**
     * @param Token $token
     * @param string $baseUrl
     */
    public function sendTokenEmail(Token $token, $baseUrl)
    {
        $tokenConfig = $this->siteUserConfig['token'];
        $userEmail = $this->authenticationService->getIdentity();
        $user = $this->entityManager->getRepository(\User\Entity\User::class)
            ->findOneBy(['email' => $userEmail]);
        $view = new ViewModel(array(
            'baseUrl'   => $baseUrl,
            'user'      => $user,
            'config'    => $this->siteUserConfig,
            'token'     => $token
        ));

        $view->setTerminal(true);
        $view->setTemplate($tokenConfig['token_mail_template']);

        $viewRenderer = $this->viewRenderer;
        $email_body = $viewRenderer->render($view);

        $mail = new Mail\Message();
        $mail->setEncoding("UTF-8");

        $html = new MimePart($email_body);
        $html->type = "text/html";

        $body = new MimeMessage();
        $body->setParts(array($html));

        $mail->setBody($body);



        $toEmailAddress = trim($user->getEmail());
        $toEmailAddress = strtr(utf8_decode($toEmailAddress),
            'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ',
            'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');

        $mail->setFrom($tokenConfig['token_mail_sender_email'], $tokenConfig['token_mail_sender_name']);
        $mail->addReplyTo($tokenConfig['token_mail_reply_email'], $tokenConfig['token_mail_reply_name']);
        $mail->addTo($toEmailAddress, $user->getFullName());
        $mail->setSubject($tokenConfig['token_mail_subject']);

        $transport = new Mail\Transport\Sendmail();
        $transport->send($mail);

    }

    public function generateToken($request)
    {
        $userEmail = $this->authenticationService->getIdentity();

        $user = $this->entityManager->getRepository(\User\Entity\User::class)
            ->findOneBy(['email' => $userEmail]);

        $datetime = new \DateTime("now");
        $hoursValid = intval($this->siteUserConfig['token']['hours_token_valid']);
        $modifyHours = "+" . $hoursValid . " hours";
        $datetime->modify($modifyHours);

        $token = $this->entityManager->find(Token::class, $user->getId());

        if (!$token) {
            $token = new Token;
            $token->setUserId($user->getId());
        }
        $token->setIP($request->getServer('REMOTE_ADDR'));
        $token->setSessionID(session_id());
        $token->setTimestamp($datetime->getTimestamp());
        $token->setToken(uniqid(mt_rand(), true));
        $token->setUsername($user->getEmail());

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }

    public function validateToken($tokenString,$request)
    {

        $tokenString = str_replace(' ', '', $tokenString);
        $valid = false;
        $errors = array();

        $datetime = new \DateTime("now");
        $timestamp = $datetime->getTimestamp();

        $userEmail = $this->authenticationService->getIdentity();
        $user = $this->entityManager->getRepository(\User\Entity\User::class)
            ->findOneBy(['email' => $userEmail]);
        /**
         * @var Token $token
         */
        $token = $this->entityManager->find(Token::class, $user->getId());

        if ($token === null) {
            $errors[] = 'Geen geldig token gevonden.';
            return $errors;
        }

        //5 string validation
        if (strlen($tokenString) == 5) {
            $dbSmallCode = substr($token->getToken(), -5);

            if ($tokenString !== $dbSmallCode) {
                $errors[] = 'Token komt niet overeen met laatst aangevraagde token.';
            }
        //full string validation
        } else {
            if ($token->getToken() !== $tokenString) {
                $errors[] = 'Token komt niet overeen met laatst aangevraagde token.';
            }
        }

        // check IP address
        if ($token->getIP() !== $request->getServer('REMOTE_ADDR')) {
            $errors[] = 'IP address komt niet overeen met aangevraagde token';
        }

        // check session ID
        if ($token->getSessionID() !== session_id()) {
            $errors[] = 'Sessie nummer van de browser komt niet overeen met aangevraagde token';
        }

        if ($timestamp > $token->getTimestamp()) {
            $errors[] = 'Gebruikers token is niet meer geldig';
        }

        if (empty($errors)) {
            // save token valid_until timestamp in session
            $container = new Container('UserToken');
            $container->token_valid_until = $token->getTimestamp();
            $container->token_user_name = $token->getUsername();
            $container->token_user_id = $token->getUserId();
            $container->session_id = $token->getSessionID();
            $container->token_ip = $token->getIP();

            return true;
        } else {
            return $errors;
        }

    }
}

