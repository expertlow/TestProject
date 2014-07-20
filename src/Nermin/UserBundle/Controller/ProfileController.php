<?php

namespace Nermin\UserBundle\Controller;

use Nermin\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileController extends Controller
{
    /**
     * @Method({"GET","POST"})
     * @Route("profile/{id}/edit", name="profile_edit")
     * @Template()
     */
    public function editAction(Request $request, User $user)
    {
        $form = $this->getProfileForm($user);
        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var User $profile */
            $profile = $form->getData();
            $avatar  = $profile->getAvatar();
            if ($avatar instanceof UploadedFile) /** @var UploadedFile $avatar */ {
                /** @var File $avatarFile */
                $avatarFile = $avatar->move($this->container->getParameter('avatar_directory'), md5(uniqid()).'.'.$avatar->guessExtension());
                $rootDir    = $this->container->getParameter('kernel.root_dir');
                $avatarPath = substr($avatarFile->getRealPath(), strlen($rootDir));
                $avatarPath = str_replace(array('\\\\', '\\'), '/', $avatarPath);
                $profile->setAvatar($avatarPath);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($profile);
            $em->flush();

            return $this->redirect($this->generateUrl('home'));
        }

        return array(
            'user' => $user,
            'form' => $form->createView(),
        );
    }

    private function getProfileForm(User $user = null)
    {
        $builder = $this->createFormBuilder($user);
        $builder->add('email', 'email', array(
            'constraints' => array(
                new NotBlank(),
                new Email(),
            ),
        ));
        $builder->add('avatar', 'file', array(
            'constraints' => array(
                new Image(),
            ),
        ));
        $builder->add('submit', 'submit', array(
            'attr' => array(
                'class' => 'btn-primary',
            ),
        ));

        return $builder->getForm();
    }
}
