<?php

namespace App\Controller;

use App\Entity\Student;
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

// import pour le mot de passe oublé
use App\Entity\ResetPasswordToken; 
use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordFormType;
use App\Repository\ResetPasswordTokenRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

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

        // récup du dernière last username saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
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
        if ($this->getUser()) {         // retourne objet user connecté ou null si pas connecté
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

            $student = new Student();
            $student->setUser($user);
            $em->persist($student);
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



    // formulaire email
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request                      $request,
        UserRepository               $userRepo,
        ResetPasswordTokenRepository $tokenRepo,
        EntityManagerInterface       $em,
        MailerInterface              $mailer
    ): Response {

        //rediriger user s'il est déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){

            $email = $form->get('email')->getData();
            $user = $userRepo->findOneBy(['email' => $email]);

            //TOUJOURS => afficher le même message pour ne pas révéler si email existe
            if($user){

                // invalider ancien token de ce user
                $oldToken = $tokenRepo->findBy(['user' => $user, 'used' => false]);

                foreach($oldToken as $old){
                    $old->setUsed(true);
                }
            
                // créer un nouveau token
                $token = new ResetPasswordToken();
                $token->setToken(bin2hex(random_bytes(32)));
                $token->setUser($user);
                // $token->setExpiresAt(new \DateTime('+1 hour'));
                $token->setExpiresAt(new \DateTime('+15 minutes'));
                $token->setUsed(false);

                $em->persist($token);
                $em->flush();

                // envoyer email
                $resetLink = $this->generateUrl(
                    'app_reset_password',
                    ['token' => $token->getToken()],
                    \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL

                    // https://localhost/reset-password/a3f7b2c9d1e4...
                );

                $emailMessage = (new Email())
                    ->from('noreply@roombooking.fr')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe — RoomBooking')
                    ->html($this->renderView('emails/reset_password.html.twig', [
                        'user'      => $user,
                        'resetLink' => $resetLink,
                        'expiresAt' => $token->getExpiresAt(),
                    ]));

                $mailer->send($emailMessage);
            }

            //même message si email exite ou non
            $this->addFlash('success', 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé');
            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('auth/forgot_password.html.twig', [
            'form' => $form,
        ]);

    }


    //nouveau mot de passe
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string                       $token,
        Request                      $request,
        ResetPasswordTokenRepository $tokenRepo,
        EntityManagerInterface       $em,
        UserPasswordHasherInterface  $hasher
    ) : Response {

        //rediriger user s'il est déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        //vérif le token
        $resetToken = $tokenRepo->findOneBy(['token' => $token, 'used' =>false]);


        if(!$resetToken || $resetToken->getExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Ce lien est invalide ou a expiré');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();

            // mise à jour le password de user
            $user = $resetToken->getUser();
            $user->setPassword($hasher->hashPassword($user, $newPassword));

            //invalider le token
            $resetToken->setUsed(true);

            $em->flush();

            $this->addFlash('success', 'Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/reset_password.html.twig', [
            'form'  => $form,
            'token' => $token,
        ]);
    }



}
