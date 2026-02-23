<?php

namespace App\DataFixtures;

use App\Entity\Administrator;
use App\Entity\Classe;
use App\Entity\Coordinator;
use App\Entity\Equipment;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\Student;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // -------------------------------------------------------
        // CLASSES
        // -------------------------------------------------------
        $classeB1 = new Classe();
        $classeB1->setName('BTS SIO SLAM B1');
        $manager->persist($classeB1);

        $classeB2 = new Classe();
        $classeB2->setName('BTS SIO SLAM B2');
        $manager->persist($classeB2);

        // -------------------------------------------------------
        // SALLES
        // -------------------------------------------------------
        $room1 = new Room();
        $room1->setName('Salle 101');
        $room1->setCapacity(30);
        $manager->persist($room1);

        $room2 = new Room();
        $room2->setName('Salle TP 202');
        $room2->setCapacity(20);
        $manager->persist($room2);

        $room3 = new Room();
        $room3->setName('Box Projet A');
        $room3->setCapacity(6);
        $manager->persist($room3);

        // -------------------------------------------------------
        // ÉQUIPEMENTS
        // -------------------------------------------------------
        $eq1 = new Equipment();
        $eq1->setName('Projecteur');
        $eq1->setRoom($room1);
        $manager->persist($eq1);

        $eq2 = new Equipment();
        $eq2->setName('Tableau blanc');
        $eq2->setRoom($room1);
        $manager->persist($eq2);

        $eq3 = new Equipment();
        $eq3->setName('PC x20');
        $eq3->setRoom($room2);
        $manager->persist($eq3);

        $eq4 = new Equipment();
        $eq4->setName('Écran TV');
        $eq4->setRoom($room3);
        $manager->persist($eq4);

        // -------------------------------------------------------
        // ADMINISTRATEUR
        // -------------------------------------------------------
        $userAdmin = new User();
        $userAdmin->setEmail('admin@roombooking.fr');
        $userAdmin->setFirstname('Sophie');
        $userAdmin->setLastname('Martin');
        $userAdmin->setPassword($this->hasher->hashPassword($userAdmin, 'Admin1234!'));
        $manager->persist($userAdmin);

        $admin = new Administrator();
        $admin->setUser($userAdmin);
        $manager->persist($admin);

        // -------------------------------------------------------
        // COORDINATEURS
        // -------------------------------------------------------
        $userCoord1 = new User();
        $userCoord1->setEmail('coord1@roombooking.fr');
        $userCoord1->setFirstname('Pierre');
        $userCoord1->setLastname('Dupont');
        $userCoord1->setPassword($this->hasher->hashPassword($userCoord1, 'Coord1234!'));
        $manager->persist($userCoord1);

        $coord1 = new Coordinator();
        $coord1->setUser($userCoord1);
        $coord1->addClass($classeB1);
        $manager->persist($coord1);

        $userCoord2 = new User();
        $userCoord2->setEmail('coord2@roombooking.fr');
        $userCoord2->setFirstname('Marie');
        $userCoord2->setLastname('Leblanc');
        $userCoord2->setPassword($this->hasher->hashPassword($userCoord2, 'Coord1234!'));
        $manager->persist($userCoord2);

        $coord2 = new Coordinator();
        $coord2->setUser($userCoord2);
        $coord2->addClass($classeB2);
        $manager->persist($coord2);

        // -------------------------------------------------------
        // ÉTUDIANTS
        // -------------------------------------------------------
        $studentsData = [
            ['alice@roombooking.fr', 'Alice', 'Bernard', $classeB1],
            ['bob@roombooking.fr',   'Bob',   'Durand',  $classeB1],
            ['carol@roombooking.fr', 'Carol', 'Petit',   $classeB2],
        ];

        $studentUsers = [];
        foreach ($studentsData as [$email, $first, $last, $classe]) {
            $u = new User();
            $u->setEmail($email);
            $u->setFirstname($first);
            $u->setLastname($last);
            $u->setPassword($this->hasher->hashPassword($u, 'Student1234!'));
            $manager->persist($u);

            $s = new Student();
            $s->setUser($u);
            $s->setClasse($classe);
            $manager->persist($s);

            $studentUsers[] = $u;
        }

        // -------------------------------------------------------
        // RÉSERVATIONS
        // -------------------------------------------------------
        $res1 = new Reservation();
        $res1->setRoom($room1);
        $res1->setUser($userCoord1);
        $res1->setReservationStart(new \DateTime('2026-03-01 08:00:00'));
        $res1->setReservationEnd(new \DateTime('2026-03-01 10:00:00'));
        $manager->persist($res1);

        $res2 = new Reservation();
        $res2->setRoom($room2);
        $res2->setUser($studentUsers[0]);
        $res2->setReservationStart(new \DateTime('2026-03-02 14:00:00'));
        $res2->setReservationEnd(new \DateTime('2026-03-02 16:00:00'));
        $manager->persist($res2);

        $res3 = new Reservation();
        $res3->setRoom($room3);
        $res3->setUser($userAdmin);
        $res3->setReservationStart(new \DateTime('2026-03-03 10:00:00'));
        $res3->setReservationEnd(new \DateTime('2026-03-03 12:00:00'));
        $manager->persist($res3);

        $manager->flush();
    }
}