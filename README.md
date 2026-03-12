# 🏫 RoomBooking

> Application web de réservation de salles — MediaSchool IRIS Nice  
> BTS SIO SLAM — Réalisation Professionnelle — Février 2026

---

## Présentation

**RoomBooking** est une application web permettant aux étudiants, coordinateurs (intervenants) et administrateurs de **MediaSchool IRIS Nice** de gérer la réservation des espaces de travail (salles de cours, salles de TP, box de projet) de façon autonome et sans conflit.

---

## Fonctionnalités

### 👤 Administrateur
- Tableau de bord global (stats temps réel)
- CRUD complet des salles + gestion des équipements (ManyToMany)
- CRUD des classes — ajout/retrait d'étudiants et de coordinateurs
- Création de comptes étudiants et coordinateurs, réinitialisation des mots de passe
- Réservation de salle pour n'importe quel utilisateur
- Annulation de toute réservation
- Filtre de disponibilité par date et créneau

### 🟢 Coordinateur (Intervenant)
- Consultation et gestion de ses classes
- Ajout/retrait d'étudiants dans ses classes
- Réservation de salle pour lui-même ou ses étudiants
- Annulation de ses propres réservations
- Filtre de disponibilité des salles

### 🔵 Étudiant
- Consultation des salles avec disponibilité temps réel
- Réservation de salle pour lui-même
- Annulation de ses propres réservations
- Vue de sa classe et de ses camarades

---

## Stack technique

| Couche            | Technologie                               |
|-------------------|-------------------------------------------|
| Backend           | Symfony 7 / PHP 8.2+                      |
| Base de données   | MySQL 8 + Doctrine ORM                    |
| Frontend          | Twig + Bootstrap 5 + CSS par rôle         |
| Authentification  | Symfony Security (firewalls, rôles, CSRF) |
| Conteneurisation  | Docker / docker-compose                   |

---

## Installation

### Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installé et en cours d'exécution
- PHP 8.2+ avec [Composer](https://getcomposer.org/)

### Lancement

```bash
# 1. Cloner le dépôt
git clone https://github.com/juklau/RoomBooking.git
cd roombooking

# 2. Copier le fichier d'environnement
cp .env-exemple .env.local
# Adapter les variables DB_NAME, DB_USER, DB_PASSWORD si nécessaire

# 3. Lancer les containers Docker
docker compose up -d

# 4. Installer les dépendances PHP
composer install

# 5. Créer la base de données et jouer les migrations
php bin/console doctrine:migrations:migrate

# 6. Ouvrir l'application dans le navigateur
# → http://localhost (port défini dans docker-compose.yml)
```

---

## Premier compte administrateur

Après installation, aucun compte admin n'existe. Suivez cette procédure :

**Étape 1** — S'inscrire via `/registration`

**Étape 2** — Récupérer l'id du compte créé :
```sql
SELECT id, email FROM user WHERE email = 'votre@email.com';
```

**Étape 3** — Insérer l'entrée administrateur :
```sql
INSERT INTO administrator (user_id) VALUES (1);
```

**Étape 4** — Se déconnecter et se reconnecter → le badge **Administrateur** apparaît.

---

## Comptes de démonstration

| Rôle | Email | Mot de passe |
|------|-------|-------------|
| Administrateur | admin@roombooking.fr | `Admin2026!` |
| Coordinateur | coordinateur@roombooking.fr | `Coord2026!` |
| Étudiant | etudiant@roombooking.fr | `Student2026!` |

> ⚠️ Ces comptes sont à créer manuellement après installation via la procédure ci-dessus.

---

## 📁 Structure du projet

```
roombooking/
├── src/
│   ├── Controller/
│   │   ├── AdminController.php        # Gestion admin complète
│   │   ├── CoordinatorController.php  # Espace coordinateur
│   │   ├── StudentController.php      # Espace étudiant
│   │   ├── AuthController.php         # Login / Logout / Inscription
│   │   └── HomeController.php         # Redirection selon rôle
│   ├── Entity/
│   │   ├── User.php
│   │   ├── Administrator.php
│   │   ├── Coordinator.php
│   │   ├── Student.php
│   │   ├── Classe.php
│   │   ├── Room.php
│   │   ├── Equipment.php
│   │   └── Reservation.php
│   ├── Form/
│   │   ├── AdminReservationType.php
│   │   ├── CoordinatorReservationType.php
│   │   ├── StudentReservationType.php
│   │   ├── RegistrationFormType.php
│   │   └── ...
│   └── Repository/
│       ├── RoomRepository.php         # findAllWithStats(), findAvailableForPeriod()
│       ├── ReservationRepository.php  # findUpcomingByUser(), countByUser()
│       └── ...
├── templates/
│   ├── admin/
│   ├── coordinator/
│   ├── student/
│   ├── auth/
│   └── partials/
├── public/
│   └── styles/
│       ├── admin.css
│       ├── coordinator.css
│       ├── student.css
│       ├── rooms.css
│       ├── app.css
│       ├── classes.css
│       └── login.css
├── migrations/
├── docker-compose.yml
└── .env
```

---

## Variables d'environnement

Configurer dans `.env.local` :

```dotenv
# Base de données
DB_NAME=roombooking
DB_USER=root
DB_PASSWORD=VotreMotDePasse!
DB_ROOT_PASSWORD=VotreMotDePasse!

# URL de la base de données Doctrine
DATABASE_URL="mysql://${DB_USER}:${DB_PASSWORD}@mysql:3306/${DB_NAME}?serverVersion=8.0&charset=utf8mb4"
```

---

## 🗄Base de données

### Réinitialiser complètement

```bash
docker compose down -v                       # Supprime les containers ET les données
docker compose up -d                         # Relance les containers
php bin/console doctrine:migrations:migrate  # Rejoue les migrations
```

### Générer le schéma SQL

```bash
php bin/console doctrine:schema:create --dump-sql
```

---

## Règles métier

| Règle                 | Détail                                                          |
|-----------------------|-----------------------------------------------------------------|
| Créneaux              | 08:00 → 20:00 par tranches de 30 min                            |
| Date minimale         | À partir de demain uniquement                                   |
| Conflit               | Impossible de créer deux réservations qui se chevauchent        |
| Annulation            | Soft delete — historique conservé, impossible si déjà commencée |
| Suppression de classe | Uniquement si elle ne contient plus aucun étudiant              |
| Retrait d'utilisateur | Détachement de la classe uniquement — compte conservé en BDD    |

---

## Sécurité

- Mots de passe hashés en **bcrypt**
- Tokens **CSRF** sur tous les formulaires de suppression/annulation
- Contrôle d'accès par rôle via `security.yaml` (`ROLE_ADMIN`, `ROLE_COORDINATOR`, `ROLE_USER`)
- Vérification côté serveur de la propriété des ressources (un coordinateur ne peut accéder qu'à ses propres classes et réservations)

---

## Commandes utiles

```bash
# Vider le cache Symfony
php bin/console cache:clear

# Lister les routes
php bin/console debug:router

# Vérifier l'état des migrations
php bin/console doctrine:migrations:status

# Hasher un mot de passe manuellement
php bin/console security:hash-password
```

---

## Documentation

| Document                      | Description                                      |
|-------------------------------|--------------------------------------------------|
| `docs/cahier_des_charges.pdf` | Expression du besoin, contexte, fonctionnalités  |
| `docs/guide_admin.pdf`        | Guide de prise en main administrateur            |
| `docs/guide_utilisateur.pdf`  | Guide de prise en main coordinateurs & étudiants |

---

## Auteur

**Klaudia Juhasz** — BTS SIO SLAM  
MediaSchool IRIS Nice — Février 2026  
Intervenant : Nicolas Choquet
