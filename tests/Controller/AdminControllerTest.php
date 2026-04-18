<?php
// tests/Controller/AdminControllerTest.php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Administrator;
use App\Entity\Room;
use App\Entity\Classe;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);
    }

    // ================================================================
    // HELPER — créer un admin en BDD pour les tests
    // ================================================================
    private function createAdminUser(): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setFirstname('Admin');
        $user->setLastname('Test');
        $user->setEmail('admin_test_' . uniqid() . '@test.fr');
        $user->setPassword($hasher->hashPassword($user, 'Admin1234!'));

        $admin = new Administrator();
        $admin->setUser($user);
        $user->setAdministrator($admin);

        $this->em->persist($user);
        $this->em->persist($admin);
        $this->em->flush();

        return $user;
    }

    // ================================================================
    // HELPER — créer une salle en BDD
    // ================================================================
    private function createRoom(string $name = 'Salle Test', int $capacity = 20): Room
    {
        $room = new Room();
        $room->setName($name);
        $room->setCapacity($capacity);

        $this->em->persist($room);
        $this->em->flush();

        return $room;
    }

    // ================================================================
    // HELPER — se connecter en tant qu'admin
    // ================================================================
    private function loginAs(User $user): void
    {
        $this->client->loginUser($user);
    }
    

    // ================================================================
    // TEST 1 — Dashboard accessible pour un admin
    // ================================================================
    public function testDashboardAccessibleForAdmin(): void
    {
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        $this->client->request('GET', '/admin/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.badge-admin');
    }

    // ================================================================
    // TEST 2 — Dashboard NON accessible sans connexion
    // ================================================================
    public function testDashboardRedirectsIfNotLogged(): void
    {
        $this->client->request('GET', '/admin/dashboard');

        // doit rediriger vers /login
        $this->assertResponseRedirects('/login');
    }

    // ================================================================
    // TEST 3 — Créer une salle avec succès
    // ================================================================
    public function testCreateRoomSuccess(): void
    {
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        $crawler = $this->client->request('GET', '/admin/rooms/new');
        $this->assertResponseIsSuccessful();

        $uniqueName = 'Salle PHPUnit ' . uniqid(); // ← nom unique à chaque test

        $form = $crawler->selectButton('Créer la salle')->form();
        $this->client->submit($form, [
            'room[name]'     => $uniqueName,
            'room[capacity]' => 25,
        ]);

        $this->assertResponseRedirects();

        $this->em->clear();
        $room = $this->em->getRepository(Room::class)->findOneBy(['name' => $uniqueName]);
        $this->assertNotNull($room);
        $this->assertEquals(25, $room->getCapacity());
    }

    // ================================================================
    // TEST 4 — Créer une salle avec nom déjà pris
    // ================================================================
    public function testCreateRoomDuplicateName(): void
    {
        $admin = $this->createAdminUser();
        $this->loginAs($admin);
        $this->createRoom('Salle Existante');

        $crawler = $this->client->request('GET', '/admin/rooms/new');

        $form = $crawler->selectButton('Créer la salle')->form();
        $form['room[name]']     = 'Salle Existante'; // ← nom déjà pris
        $form['room[capacity]']  = 10;
        

        $this->client->submit($form);

        // Doit rester sur la page avec message d'erreur
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.flash-error');
    }

    // ================================================================
    // TEST 5 — Supprimer une salle sans réservations
    // ================================================================
    public function testDeleteRoomWithoutReservations(): void
    {
        $admin = $this->createAdminUser();
        $this->loginAs($admin);
        $room = $this->createRoom('Salle suppr ' . uniqid());
        $roomId = $room->getId();

        // Aller sur la page de détail pour initialiser la session
        $crawler = $this->client->request('GET', '/admin/rooms/' . $roomId);
        $this->assertResponseIsSuccessful();

        // Extraire le token CSRF depuis le formulaire de suppression dans le HTML
        $csrfToken = $crawler->filter('input[name="_token"]')->first()->attr('value');

        $this->client->request('POST', '/admin/rooms/' . $roomId . '/delete', [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects('/admin/rooms');

        $this->em->clear();
        $deleted = $this->em->getRepository(Room::class)->find($roomId);
        $this->assertNull($deleted);
    }

    // ================================================================
    // TEST 6 — Supprimer une salle AVEC réservation future → impossible
    // ================================================================
    public function testDeleteRoomWithFutureReservationFails(): void
    {
        $admin = $this->createAdminUser();
        $this->loginAs($admin);
        $room = $this->createRoom('Salle Bloquée ' . uniqid());

        $reservation = new Reservation();
        $reservation->setRoom($room);
        $reservation->setUser($admin);
        $reservation->setReservationStart(new \DateTime('+1 day'));
        $reservation->setReservationEnd(new \DateTime('+1 day +2 hours'));
        $this->em->persist($reservation);
        $this->em->flush();

        // Faire une requête pour initialiser la session
        $this->client->request('GET', '/admin/dashboard');

        // Générer le token après initialisation de la session
        $tokenId   = 'delete_room_' . $room->getId();
        $csrfToken = $this->client->getContainer()
            ->get('security.csrf.token_manager')
            ->getToken($tokenId)
            ->getValue();

        $this->client->request('POST', '/admin/rooms/' . $room->getId() . '/delete', [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects('/admin/rooms/' . $room->getId());
        $this->client->followRedirect();
        $this->assertSelectorExists('.flash-error');

        $this->em->clear();
        $still = $this->em->getRepository(Room::class)->find($room->getId());
        $this->assertNotNull($still);
    }

    // ================================================================
    // TEST 7 — Supprimer avec token CSRF invalide
    // ================================================================
    public function testDeleteRoomInvalidCsrfToken(): void
    {
        $admin = $this->createAdminUser();
        $this->loginAs($admin);
        $room = $this->createRoom('Salle CSRF');

        $this->client->request('POST', '/admin/rooms/' . $room->getId() . '/delete', [
            '_token' => 'token_invalide_12345',
        ]);

        $this->assertResponseRedirects('/admin/rooms');
        $this->client->followRedirect();
        $this->assertSelectorExists('.flash-error');
    }

    // ================================================================
    // TEST 8 — Créer une classe avec succès
    // ================================================================
    public function testCreateClasseSuccess(): void
    {
        $admin = $this->createAdminUser();
        $this->loginAs($admin);

        $crawler = $this->client->request('GET', '/admin/classes/new');
        $this->assertResponseIsSuccessful();

        $uniqueName = 'BTS SIO Test ' . uniqid();

        $form = $crawler->selectButton('Créer la classe')->form();
        $this->client->submit($form, [
            'classe[name]' => $uniqueName,  // ← pas classe_type
        ]);

        $this->assertResponseRedirects();

        $this->em->clear();
        $classe = $this->em->getRepository(Classe::class)->findOneBy(['name' => $uniqueName]);
        $this->assertNotNull($classe);
    }

    // ================================================================
    // TEST 9 — Annuler une réservation future
    // ================================================================
    public function testCancelFutureReservation(): void
    {
        $admin = $this->createAdminUser();
        $this->loginAs($admin);
        $room = $this->createRoom('Salle Annul ' . uniqid());

        $reservation = new Reservation();
        $reservation->setRoom($room);
        $reservation->setUser($admin);
        $reservation->setReservationStart(new \DateTime('+2 days'));
        $reservation->setReservationEnd(new \DateTime('+2 days +1 hour'));
        $this->em->persist($reservation);
        $this->em->flush();

        // Initialiser la session
        $this->client->request('GET', '/admin/dashboard');

        $tokenId   = 'cancel_reservation_' . $reservation->getId();
        $csrfToken = $this->client->getContainer()
            ->get('security.csrf.token_manager')
            ->getToken($tokenId)
            ->getValue();

        $this->client->request('POST', '/admin/reservations/' . $reservation->getId() . '/cancel', [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects();

        $this->em->clear();
        $updated = $this->em->getRepository(Reservation::class)->find($reservation->getId());
        $this->assertEquals('canceled', $updated->getStatus());
    }

    // ================================================================
    // TEST 10 — Annuler une réservation déjà passée → impossible
    // ================================================================
    public function testCancelPastReservationFails(): void
    {
        $admin = $this->createAdminUser();
        $this->loginAs($admin);
        $room = $this->createRoom('Salle Pass ' . uniqid());

        $reservation = new Reservation();
        $reservation->setRoom($room);
        $reservation->setUser($admin);
        $reservation->setReservationStart(new \DateTime('-2 days'));
        $reservation->setReservationEnd(new \DateTime('-2 days +1 hour'));
        $this->em->persist($reservation);
        $this->em->flush();

        // Initialiser la session
        $this->client->request('GET', '/admin/dashboard');

        $tokenId   = 'cancel_reservation_' . $reservation->getId();
        $csrfToken = $this->client->getContainer()
            ->get('security.csrf.token_manager')
            ->getToken($tokenId)
            ->getValue();

        $this->client->request('POST', '/admin/reservations/' . $reservation->getId() . '/cancel', [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorExists('.flash-error');

        $this->em->clear();
        $notChanged = $this->em->getRepository(Reservation::class)->find($reservation->getId());
        $this->assertNotEquals('canceled', $notChanged->getStatus());
    }

    // ================================================================
    // Nettoyage après chaque test
    // ================================================================
    protected function tearDown(): void
    {
        parent::tearDown();
        // Symfony recrée le kernel entre les tests — pas besoin de cleanup manuel
    }
}