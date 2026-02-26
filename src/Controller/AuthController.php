<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        
        //rediriger vers home => si je suis connecté
        if($this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_username' =>$lastUsername,
            'error'         => $error,
        ]);
    }

    /**
     * insription
     */
    #[Route('/registration', name: 'app_registration')]
    public function registration(
        Request                     $request,
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $hasher,
        UserRepository              $userRepo
    ): Response {

        // Rediriger vers home si déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Vérifier que l'email n'existe pas déjà
            if ($userRepo->findOneBy(['email' => $data['email']])) {

                $this->addFlash('error', 'Un compte avec cet email existe déjà.');
                return $this->render('auth/registration.html.twig', ['form' => $form]);
            }

            $user = new User();
            $user->setFirstname($data['firstname']);
            $user->setLastname($data['lastname']);
            $user->setEmail($data['email']);
            $user->setPassword($hasher->hashPassword($user, $data['password']));

            // Par défaut ROLE_STUDENT — l'admin peut modifier le rôle ensuite
            // $user->setRoles(['ROLE_STUDENT']); => il ne faut pas: automatiquement rôle student

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Compte créé avec succès ! Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/registration.html.twig', [
            'form' => $form,
        ]);
    }


    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        //Symfony intercept automatiquement cette route 
        throw new \LogicException('Cette méthode ne devrait pas être atteinte.');
    }
}
