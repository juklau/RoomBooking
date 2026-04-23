# Backup et Restauration — Serveur École

Auteur : Klaudia Juhasz
Contexte : TP Backup / Restauration — BTS SIO
Serveur : 37.64.159.66 (port 2222)
Projet sur le serveur : `/home/iris/slam/Roombooking_klaudia/`

---

## Architecture du projet sur le serveur

```
/home/iris/slam/Roombooking_klaudia/
  backup/
    backup_serveur.sh          # Script de sauvegarde
    restore_serveur.sh         # Script de restauration en production
    restore_serveur_isoled.sh  # Script de restauration isolée (prod intacte)
    BACKUP_RESTORE_SERVEUR.md  # Ce fichier
  backups/                     # Archives générées automatiquement
    roombooking_serveur_backup_YYYYMMDD_HHMMSS.tar.gz
    roombooking_serveur_backup_YYYYMMDD_HHMMSS.tar.gz.sha256
    backup_serveur_YYYYMMDD_HHMMSS.log
    cron_backup.log            # Log du cron uniquement
  restores/                    # Répertoire de restauration (créé à la demande)
```

---

## Conteneurs Docker en production

| Conteneur | Rôle |
|---|---|
| `roombooking-web` | PHP 8.3-FPM (Symfony) |
| `roombooking-nginx` | Reverse proxy Nginx |
| `roombooking-db` | MySQL 8.0 |

Volumes Docker :
- `roombooking_klaudia_mysql-data` — données MySQL
- `roombooking_klaudia_vendor_data` — dépendances Composer

URL de production : `https://room-klaudia.iris.a3n.fr`

---

## backup_serveur.sh

Sauvegarde complète de l'application. Les conteneurs restent UP pendant toute la durée.

### Ce qui est sauvegardé

| Étape | Contenu | Méthode |
|---|---|---|
| Base de données | Dump MySQL compressé `.sql.gz` | `mysqldump` via `docker exec` |
| Volumes Docker | `mysql-data` + `vendor_data` | Conteneur alpine temporaire (`:ro`) |
| Configuration | `docker-compose.prod.yml`, `.env`, `Dockerfile`, `composer.*`, `nginx`, `php.ini` | Copie filesystem |
| Code applicatif | `src/`, `public/`, `config/`, `templates/`, `migrations/`, `bin/` | Archive tar |
| Logs Docker | 3 conteneurs | `docker logs` |
| Archive finale | Tout compressé en `.tar.gz` + checksum SHA256 | `tar + sha256sum` |

### Usage

```bash
bash /home/iris/slam/Roombooking_klaudia/backup/backup_serveur.sh
```

Depuis la machine locale :

```bash
ssh -i ~/.ssh/serveurMediaSchool -p 2222 klaudia@37.64.159.66 \
  "bash /home/iris/slam/Roombooking_klaudia/backup/backup_serveur.sh"
```

### Rétention

Les archives de plus de 7 jours sont supprimées automatiquement à chaque exécution.

---

## Automatisation — Cron

Le backup s'exécute automatiquement tous les vendredis à 18h00.

### Voir le cron actif

```bash
ssh -i ~/.ssh/serveurMediaSchool -p 2222 klaudia@37.64.159.66 "crontab -l"
```

### Contenu du cron

```
PATH=/usr/local/bin:/usr/bin:/bin
0 18 * * 5 bash /home/iris/slam/Roombooking_klaudia/backup/backup_serveur.sh >> /home/iris/slam/Roombooking_klaudia/backups/cron_backup.log 2>&1
```

- `0 18 * * 5` : tous les vendredis à 18h00
- `PATH` : défini explicitement pour que `docker`, `gzip`, `sha256sum` soient accessibles
- La sortie du cron est redirigée dans `cron_backup.log`

### Modifier ou supprimer le cron

```bash
# Éditer
ssh -i ~/.ssh/serveurMediaSchool -p 2222 klaudia@37.64.159.66 "crontab -e"

# Supprimer toute la crontab
ssh -i ~/.ssh/serveurMediaSchool -p 2222 klaudia@37.64.159.66 "crontab -r"
```

---

## restore_serveur.sh — Restauration en production

Restaure un backup directement en production. Les conteneurs de prod sont arrêtés puis redémarrés.

### Impact

| Élément | Impact |
|---|---|
| Conteneurs de prod | Arrêtés puis redémarrés |
| Base de données | Supprimée et recréée depuis le dump |
| Volumes Docker | Vidés et remplacés par le contenu du backup |
| Fichiers source | Restaurés dans `restores/` |

### Usage interactif

```bash
ssh -i ~/.ssh/serveurMediaSchool -p 2222 klaudia@37.64.159.66 \
  "bash /home/iris/slam/Roombooking_klaudia/backup/restore_serveur.sh"
```

### Usage avec un fichier spécifique

```bash
ssh -i ~/.ssh/serveurMediaSchool -p 2222 klaudia@37.64.159.66 \
  "echo 'oui' | bash /home/iris/slam/Roombooking_klaudia/backup/restore_serveur.sh \
  --backup-file /home/iris/slam/Roombooking_klaudia/backups/roombooking_serveur_backup_YYYYMMDD_HHMMSS.tar.gz"
```

### Options

| Option | Description |
|---|---|
| `--backup-file PATH` | Fichier backup à restaurer (sinon liste interactive) |
| `--backup-dir PATH` | Répertoire des backups (défaut : `backups/`) |
| `--target-dir PATH` | Répertoire cible (défaut : `restores/`) |
| `-h, --help` | Aide |

---

## restore_serveur_isoled.sh — Restauration isolée

Restaure un backup dans un environnement totalement séparé. La production reste UP pendant toute la durée — aucune ressource de prod n'est modifiée.

### Ressources créées (distinctes des ressources de production)

| Ressource | Nom isolé |
|---|---|
| Conteneur web | `roombooking-restore-web` |
| Conteneur nginx | `roombooking-restore-nginx` |
| Conteneur db | `roombooking-restore-db` |
| Volume MySQL | `roombooking_restore_mysql-data` |
| Volume Composer | `roombooking_restore_vendor_data` |
| Réseau | `roombooking-restore-network` |
| Port local | `127.0.0.1:9092` (non accessible depuis l'extérieur) |
| Compose généré | `restores/docker-compose.restore.yml` |

### Usage

```bash
ssh -i ~/.ssh/serveurMediaSchool -p 2222 klaudia@37.64.159.66 \
  "echo 'oui' | bash /home/iris/slam/Roombooking_klaudia/backup/restore_serveur_isoled.sh \
  --backup-file /home/iris/slam/Roombooking_klaudia/backups/roombooking_serveur_backup_YYYYMMDD_HHMMSS.tar.gz"
```

### Vérifier que l'environnement isolé répond

```bash
ssh -i ~/.ssh/serveurMediaSchool -p 2222 klaudia@37.64.159.66 \
  "curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:9092"
```

Un code `302` confirme que l'application est opérationnelle (redirection Symfony vers login).

### Nettoyer après vérification

```bash
ssh -i ~/.ssh/serveurMediaSchool -p 2222 klaudia@37.64.159.66 \
  "docker compose -f /home/iris/slam/Roombooking_klaudia/restores/docker-compose.restore.yml down -v"
```

Le `-v` supprime les volumes isolés en même temps que les conteneurs.

---

## Lister les backups disponibles

```bash
ssh -i ~/.ssh/serveurMediaSchool -p 2222 klaudia@37.64.159.66 \
  "ls -lh /home/iris/slam/Roombooking_klaudia/backups/roombooking_serveur_backup_*.tar.gz"
```

---

## Connexion SSH au serveur

```
Host     : 37.64.159.66
Port     : 2222
User     : klaudia
Clé      : ~/.ssh/serveurMediaSchool
```

```bash
ssh -i ~/.ssh/serveurMediaSchool -p 2222 klaudia@37.64.159.66
```
