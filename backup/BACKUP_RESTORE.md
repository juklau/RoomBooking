# Backup & Restauration — RoomBooking

> **Contexte** : TP Backup / Restauration — BTS SIO  
> **Auteur** : Klaudia Juhasz
> **Stack** : PHP 8.3-FPM + Nginx + MySQL 8 + Docker Compose

---

## Sommaire

- [Structure des fichiers](#structure-des-fichiers)
- [Prérequis](#prérequis)
- [Backup](#backup)
  - [Ce qui est sauvegardé](#ce-qui-est-sauvegardé)
  - [Lancer un backup](#lancer-un-backup)
  - [Résultat d'un backup](#résultat-dun-backup)
  - [Rétention automatique](#rétention-automatique)
- [Restauration](#restauration)
  - [Ce qui est restauré](#ce-qui-est-restauré)
  - [Lancer une restauration](#lancer-une-restauration)
  - [Options disponibles](#options-disponibles)
  - [Mode développement](#mode-développement)
- [Fonctionnement interne](#fonctionnement-interne)
  - [Intégrité SHA256](#intégrité-sha256)
  - [Volumes Docker](#volumes-docker)
  - [Ordre de restauration](#ordre-de-restauration)
- [Erreurs fréquentes](#erreurs-fréquentes)

---

## Structure des fichiers

```
roombooking/
├── backup/
│   ├── backup.sh       ← Script de sauvegarde
│   └── restore.sh      ← Script de restauration
├── backups/            ← Dossier créé automatiquement
│   ├── roombooking_backup_20250327_143000.tar.gz
│   ├── roombooking_backup_20250327_143000.tar.gz.sha256
│   └── backup_20250327_143000.log
└── restores/           ← Dossier cible de restauration (par défaut)
```

---

## Prérequis

Avant d'utiliser les scripts, vérifier que les éléments suivants sont présents et fonctionnels :

| Prérequis | Vérification |
|---|---|
| Docker en cours d'exécution | `docker info` |
| Conteneur MySQL actif | `docker ps \| grep roombooking-db` |
| `sha256sum` disponible | `sha256sum --version` |
| Git Bash (Windows) | Requis pour `MSYS_NO_PATHCONV=1` |

> Les scripts se lancent **depuis n'importe quel répertoire** : les chemins sont résolus automatiquement à partir de l'emplacement du script.

---

## Backup

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
# Depuis la racine du projet
bash backup/backup.sh

# Ou depuis n'importe où en chemin absolu
bash /chemin/vers/roombooking/backup/backup.sh
```

Le script effectue les opérations suivantes dans l'ordre :

1. Vérification des prérequis (Docker, conteneur MySQL, `sha256sum`)
2. Dump de la base de données MySQL
3. Sauvegarde des volumes Docker
4. Sauvegarde des fichiers de configuration
5. Sauvegarde du code applicatif Symfony
6. Sauvegarde des logs Docker
7. Création de l'archive compressée `.tar.gz`
8. Calcul et écriture du checksum SHA256
9. Vérification de l'intégrité de l'archive
10. Nettoyage des backups de plus de 7 jours

### Résultat d'un backup

Un backup réussi génère **3 fichiers** dans `backups/` :

```
roombooking_backup_YYYYMMDD_HHMMSS.tar.gz         ← Archive compressée
roombooking_backup_YYYYMMDD_HHMMSS.tar.gz.sha256  ← Checksum SHA256
backup_YYYYMMDD_HHMMSS.log                        ← Log détaillé
```

Structure interne de l'archive :

```
roombooking_backup_YYYYMMDD_HHMMSS/
├── database/
│   └── roombooking.sql.gz
├── volumes/
│   ├── roombooking_mysql-data.tar.gz
│   └── roombooking_vendor_data.tar.gz
├── config/
│   ├── docker-compose.yml
│   ├── .env
│   ├── Dockerfile
│   ├── composer.json
│   ├── composer.lock
│   └── docker/
│       ├── nginx/default.conf
│       └── php/php.ini
├── applications/
│   ├── public.tar.gz
│   ├── src.tar.gz
│   ├── config.tar.gz
│   ├── templates.tar.gz
│   ├── migrations.tar.gz
│   └── bin.tar.gz
└── logs/
    ├── roombooking-web.log
    ├── roombooking-nginx.log
    ├── roombooking-db.log
    └── roombooking-phpmyadmin.log
```

### Rétention automatique

Les backups de plus de **7 jours** sont supprimés automatiquement à chaque exécution.

Pour modifier la durée de rétention, éditer la variable en haut de `backup.sh` :

```bash
RETENTION_DAYS=7  # Modifier ici
```

---

## Restauration

### Ce qui est restauré

| Catégorie | Comportement |
|---|---|
| **Configuration** | Écrase les fichiers existants (avec sauvegarde dans `/tmp/`) |
| **Code applicatif** | Extrait les archives dans le répertoire cible |
| **Volumes Docker** | Vide puis réinjecte les données dans les volumes |
| **Base de données** | Supprime et recrée la base, puis importe le dump SQL |

### Lancer une restauration

```bash
# Mode interactif (recommandé) — pose les questions nécessaires
bash backup/restore.sh

# Avec options en ligne de commande
bash backup/restore.sh --backup-dir ./backups --target-dir ./restores

# Avec un fichier backup spécifique
bash backup/restore.sh --backup-file ./backups/roombooking_backup_20250327_143000.tar.gz
```

En mode interactif, le script demande :

1. Le répertoire des backups (appuyer sur Entrée pour utiliser la valeur par défaut)
2. Le répertoire cible de restauration
3. La sélection du backup parmi la liste disponible (du plus récent au plus ancien)
4. Une confirmation avant d'écraser les données

### Options disponibles

| Option | Description | Défaut |
|---|---|---|
| `--backup-dir PATH` | Répertoire contenant les archives de backup | `./backups` |
| `--target-dir PATH` | Répertoire où restaurer les fichiers | `./restores` |
| `--backup-file PATH` | Fichier backup précis à restaurer (ignore la liste) | — |
| `--dev` | Mode développement (voir ci-dessous) | désactivé |
| `-h`, `--help` | Afficher l'aide | — |

### Mode développement

Le flag `--dev` permet de restaurer le projet **sans écraser** le `docker-compose.yml` et le `.env` actuels. Utile pour restaurer les données et le code sur un environnement de dev qui a sa propre configuration.

```bash
bash backup/restore.sh --dev
```

En mode `--dev` :
- `docker-compose.yml` et `.env` sont pris depuis le répertoire du script (`backup/`)
- Tous les autres fichiers de configuration sont restaurés normalement

---

## Fonctionnement interne

### Intégrité SHA256

À chaque backup, un checksum SHA256 est calculé sur l'archive `.tar.gz` et stocké dans un fichier `.sha256` :

```
a3f9d1c2e4b7...  roombooking_backup_20250327_143000.tar.gz
```

À la restauration, ce checksum est vérifié automatiquement avant d'extraire quoi que ce soit. Si l'archive est corrompue, le script s'arrête avec une erreur.

Vérification manuelle possible :

```bash
cd backups/
sha256sum -c roombooking_backup_20250327_143000.tar.gz.sha256
```

### Volumes Docker

Les volumes sont sauvegardés via un conteneur Alpine temporaire qui monte le volume en lecture seule et crée une archive `tar.gz` :

```
Volume Docker → conteneur Alpine → tar.gz dans backup/volumes/
```

À la restauration, le même mécanisme fonctionne en sens inverse : le volume est vidé, puis le contenu de l'archive est réinjecté.

Les deux volumes sauvegardés sont :

| Volume | Contenu |
|---|---|
| `roombooking_mysql-data` | Fichiers de données MySQL (`/var/lib/mysql`) |
| `roombooking_vendor_data` | Dépendances Composer (`/var/www/html/vendor`) |

### Ordre de restauration

L'ordre est important et intentionnel :

```
1. restore_config         → fichiers de configuration disponibles pour Docker
2. restore_applications   → code Symfony extrait dans le répertoire cible
3. restore_volumes        → volumes Docker réinjectés (vendor avant démarrage PHP)
4. restore_database       → MySQL démarré seul, base droppée/recréée, dump importé
5. start_services         → tous les services démarrés ensemble
```

---

## Erreurs fréquentes

| Erreur | Cause probable | Solution |
|---|---|---|
| `Le conteneur 'roombooking-db' n'existe pas` | Docker non démarré ou projet jamais lancé | `docker compose up -d` puis relancer |
| `Le fichier dump est vide` | MySQL pas encore prêt au moment du dump | Attendre et relancer, ou augmenter le délai dans le script |
| `Échec de la vérification d'intégrité` | Archive corrompue ou transfert incomplet | Refaire un backup ou vérifier le fichier source |
| `Permissions insuffisantes` | Répertoire cible appartenant à root | Choisir l'option 1 (correction automatique) ou lancer avec `sudo` |
| `Aucun backup trouvé dans ./backups` | Mauvais `--backup-dir` ou backups expirés | Vérifier le chemin ou refaire un backup |
| Volume `roombooking_vendor_data` vide | Composer n'a jamais tourné dans le conteneur | Lancer `docker compose exec web composer install` après restauration |

> En cas d'erreur pendant le backup, le répertoire temporaire est automatiquement supprimé par le `trap ERR` et les conteneurs sont arrêtés proprement.

---

## Accès après restauration

Une fois la restauration terminée, l'application est accessible aux adresses suivantes :

| Service | URL |
|---|---|
| Application RoomBooking | http://localhost:9084 |
| phpMyAdmin | http://localhost:8084 |
