# 🏫 RoomBooking

> Application web de réservation de salles — MediaSchool IRIS Nice  
> BTS SIO SLAM — Réalisation Professionnelle — Avril 2026

---

## Sommaire

- [Présentation](#présentation)
- [Fonctionnalités](#fonctionnalités)
- [Stack technique](#stack-technique)
- [Installation](#installation)
- [Premier compte administrateur](#premier-compte-administrateur)
- [Comptes de démonstration](#comptes-de-démonstration)
- [Structure du projet](#structure-du-projet)
- [Variables d'environnement](#variables-denvironnement)
- [Base de données](#base-de-données)
- [Backup & Restauration](#backup--restauration)
- [Règles métier](#règles-métier)
- [Sécurité](#sécurité)
- [Commandes utiles](#commandes-utiles)
- [Documentation](#documentation)
- [Auteur](#auteur)

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
- Réservation de salle pour n'importe quel utilisateur (avec sélection de la classe)
- Visualisation de toutes les réservations (tous utilisateurs confondus)
- Annulation de toute réservation
- Filtre de disponibilité par date et créneau
- Réinitialisation de mot de passe pour n'importe quel utilisateur

### 🟢 Coordinateur (Intervenant)
- Consultation et gestion de ses classes
- Ajout/retrait d'étudiants dans ses classes
- Réservation de salle pour lui-même ou ses étudiants (avec sélection de la classe)
- Annulation de ses propres réservations
- Filtre de disponibilité des salles

### 🔵 Étudiant
- Consultation des salles avec disponibilité temps réel
- Réservation de salle pour lui-même (avec sélection de la classe)
- Annulation de ses propres réservations
- Vue de sa classe et de ses camarades

### Fonctionnalités transverses
- Mot de passe oublié : envoi d'un lien de réinitialisation par email (token expirant à 15 minutes)
- Détection automatique des réservations passées : statut `passed` appliqué toutes les heures via cron

---

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | Symfony 7 / PHP 8.2+ |
| Base de données | MySQL 8 + Doctrine ORM |
| Frontend | Twig + Bootstrap 5 + CSS par rôle |
| Authentification | Symfony Security (firewalls, rôles, CSRF) |
| Conteneurisation | Docker / docker-compose |
| Envoi d'emails (local) | Mailtrap SMTP |
| Envoi d'emails (serveur) | Brevo API v3 (`symfony/brevo-mailer`) |

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
# → http://localhost:9084
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
|---|---|---|
| Administrateur | admin@roombooking.fr | `Admin2026!` |
| Coordinateur | coordinateur@roombooking.fr | `Coord2026!` |
| Étudiant | etudiant@roombooking.fr | `Student2026!` |

> ⚠️ Ces comptes sont à créer manuellement après installation via la procédure ci-dessus.

---

## Structure du projet

```
roombooking/
├── backup/
│   ├── backup.sh                      # Script de sauvegarde
│   └── restore.sh                     # Script de restauration
├── backups/                           # Archives générées automatiquement
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

# Envoi d'emails — Mailtrap (local)
MAILER_DSN=smtp://utilisateur:motdepasse@sandbox.smtp.mailtrap.io:2525

# Envoi d'emails — Brevo (serveur uniquement, ne pas committer la clé)
# MAILER_DSN=brevo+api://VOTRE_CLE_API@default
```

---

## Base de données

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

## Backup & Restauration

Les scripts `backup.sh` et `restore.sh` se trouvent dans le dossier `backup/`. Ils fonctionnent avec Docker Compose et ne nécessitent aucune configuration manuelle : les chemins sont résolus automatiquement.

### Ce qui est sauvegardé

| Catégorie | Contenu |
|---|---|
| **Base de données** | Dump complet MySQL (`.sql.gz`) via `mysqldump` |
| **Volumes Docker** | `roombooking_mysql-data` + `roombooking_vendor_data` |
| **Configuration** | `docker-compose.yml`, `.env`, `Dockerfile`, `composer.json`, `composer.lock`, `docker/nginx/default.conf`, `docker/php/php.ini` |
| **Code applicatif** | `public/`, `src/`, `config/`, `templates/`, `migrations/`, `bin/` |
| **Logs Docker** | Logs des 4 conteneurs RoomBooking |

### Lancer un backup

```bash
bash backup/backup.sh
```

Un backup réussi génère 3 fichiers dans `backups/` :

```
backups/
├── roombooking_backup_YYYYMMDD_HHMMSS.tar.gz         ← Archive compressée
├── roombooking_backup_YYYYMMDD_HHMMSS.tar.gz.sha256  ← Checksum SHA256
└── backup_YYYYMMDD_HHMMSS.log                        ← Log détaillé
```

> Les backups de plus de **7 jours** sont supprimés automatiquement à chaque exécution.

### Lancer une restauration

```bash
# Mode interactif — sélection guidée du backup à restaurer
bash backup/restore.sh

# Avec un backup spécifique
bash backup/restore.sh --backup-file ./backups/roombooking_backup_20250327_143000.tar.gz

# Mode développement — conserve le docker-compose.yml et .env actuels
bash backup/restore.sh --dev
```

Une fois la restauration terminée, l'application est accessible aux adresses habituelles :

| Service | URL |
|---|---|
| Application RoomBooking | http://localhost:9084 |
| phpMyAdmin | http://localhost:8084 |

### Options du script restore.sh

| Option | Description |
|---|---|
| `--backup-dir PATH` | Répertoire contenant les archives (défaut : `./backups`) |
| `--target-dir PATH` | Répertoire cible de restauration (défaut : `./restores`) |
| `--backup-file PATH` | Fichier backup précis à restaurer |
| `--dev` | Conserve `docker-compose.yml` et `.env` actuels |
| `-h`, `--help` | Afficher l'aide |

### Intégrité des archives

Chaque archive est vérifiée par checksum SHA256 avant extraction. Vérification manuelle possible :

```bash
cd backups/
sha256sum -c roombooking_backup_YYYYMMDD_HHMMSS.tar.gz.sha256
```

> Pour la documentation complète du système de backup (fonctionnement interne, erreurs fréquentes, ordre de restauration), consulter [`backup/BACKUP_RESTORE.md`](backup/BACKUP_RESTORE.md).

---

## Règles métier

| Règle | Détail |
|---|---|
| Créneaux | 08:00 → 20:00 par tranches de 30 min |
| Date minimale | À partir de la date et de l’heure actuelles (impossible de réserver dans le passé)  |
| Conflit | Impossible de créer deux réservations qui se chevauchent |
| Annulation | Soft delete — historique conservé, impossible si déjà commencée |
| Classe obligatoire | Chaque réservation est associée à une classe (sélection dans le formulaire) |
| Statut automatique | Les réservations dont la fin est passée passent automatiquement en `passed` (cron horaire) |
| Suppression de classe | Uniquement si elle ne contient plus aucun étudiant |
| Retrait d'utilisateur | Détachement de la classe uniquement — compte conservé en BDD |

---

## Sécurité

- Mots de passe hashés en **bcrypt**
- Politique de mot de passe renforcée : 12 caractères minimum, majuscule, minuscule, chiffre, caractère spécial
- Tokens **CSRF** sur tous les formulaires de suppression/annulation
- Réinitialisation de mot de passe par token signé expirant après 15 minutes (usage unique)
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

# Passer manuellement les réservations passées au statut "passed"
php bin/console app:reservations:update-status
```

### Cron serveur (réservations passées)

La commande est exécutée automatiquement toutes les heures sur le serveur :

```
0 * * * * docker exec roombooking-web php bin/console app:reservations:update-status >> /var/log/roombooking_cron.log 2>&1
```

---

## Documentation

| Document | Description |
|---|---|
| `docs/cahier_des_charges.pdf` | Expression du besoin, contexte, fonctionnalités |
| `docs/guide_admin.pdf` | Guide de prise en main administrateur |
| `docs/guide_utilisateur.pdf` | Guide de prise en main coordinateurs & étudiants |
| `backup/BACKUP_RESTORE.md` | Documentation complète du système de backup & restauration |

---

## Auteur

**Klaudia Juhasz** — BTS SIO SLAM  
MediaSchool IRIS Nice — Avril/Mai 2026  
Intervenant : Nicolas Choquet
