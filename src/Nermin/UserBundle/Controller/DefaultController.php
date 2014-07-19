<?php

namespace Nermin\UserBundle\Controller;

use Nermin\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class DefaultController extends Controller
{
    /**
     * @Method("GET")
     * @Route("/", name="home")
     */
    public function indexAction()
    {
        return new Response('<body>Velkom</body>');
    }

    /**
     * @Method({"GET","POST"})
     * @Route("/register", name="register")
     */
    public function registerAction(Request $request)
    {
        $form = $this->getRegisterForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $user = new User();
            // Populate user provided data
            $user->setUsername($data['username']);
            $user->setEmail($data['email']);
            $user->setCreatedAt(new \DateTime());

            // Generate a random salt
            $salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
            $user->setSalt($salt);

            // Encode user's password
            $encoder = $this->get('security.encoder_factory')->getEncoder($user);
            $password = $encoder->encodePassword($data['password'], $salt);
            $user->setPassword($password);

            // Set any roles we want
            $user->setRoles(array('ROLE_USER', 'ROLE_UPLOADER'));

            // Save user to the database
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            // Log the user in
            $token = new UsernamePasswordToken($user->getUsername(), '', 'main', $user->getRoles());
            $this->get('security.context')->setToken($token);

            return $this->redirect($this->generateUrl('home'));
        }

        return $this->render('NerminUserBundle:Default:registracija.html.twig', array(
            'form'=>$form->createView(),
        ));
    }

    private function getRegisterForm()
    {
        $builder = $this->createFormBuilder(null, array(
            'action' => $this->generateUrl('register'),
            'attr' => array(
                'novalidate' => 'novalidate',
            ),
        ));
        $builder->add('username', 'text', array(
            'constraints' => array(
                new NotBlank(),
            ),
        ));
        $builder->add('email', 'email', array(
            'constraints' => array(
                new NotBlank(),
                new Email(),
            ),
        ));
        $builder->add('password', 'repeated', array(
            'type' => 'password',
            'constraints' => array(
                new NotBlank(),
                new Length(array('min' => 5)),
            ),
        ));
        $builder->add('submit', 'submit');

        return $builder->getForm();
    }

    /**
     * @Method("GET")
     * @Route("/upload")
     */
    public function uploadAction()
    {
        return new Response('<body>Upload content</body>');
    }

    /**
     * @Method("POST")
     * @Route("/login_check", name="login_check")
     */
    public function loginCheckAction()
    {
        // Symfony ce presresti ovaj poziv i pokusace da loguje korisnika.
    }

    /**
     * @Method("GET")
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
        // Symfony ce presresti ovaj poziv i pokusace da loguje korisnika.
    }

    /**
     * @Method("GET")
     * @Route("/login")
     */
    public function loginAction(Request $request)
    {
        /** @var $session \Symfony\Component\HttpFoundation\Session\Session */
        $session = $request->getSession();

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContextInterface::AUTHENTICATION_ERROR);
        } elseif (null !== $session && $session->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
            $error = $session->get(SecurityContextInterface::AUTHENTICATION_ERROR);
            $session->remove(SecurityContextInterface::AUTHENTICATION_ERROR);
        } else {
            $error = '';
        }

        if ($error) {
            // TODO: this is a potential security risk (see http://trac.symfony-project.org/ticket/9523)
            $error = $error->getMessage();
        }
        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get(SecurityContextInterface::LAST_USERNAME);

        $csrfToken = $this->container->has('form.csrf_provider')
            ? $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate')
            : null;

        return $this->render('NerminUserBundle:Default:index.html.twig', array(
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token' => $csrfToken,
        ));
    }
}
