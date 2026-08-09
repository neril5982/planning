# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Portail interne pour "Gestion SARL Moncomble" (deux entités : Moncomble et RVM). Bâti en reprenant
le pattern éprouvé du projet sœur `piastrella-kpi/piastrella-dashboard` (PHP vanilla + JWT maison).

Modules actuels :
- **Portail** : login / logout / mot de passe oublié.
- **Gestion → Planning** : calendrier Gantt des chantiers (créneaux multiples par chantier, jours
  fériés français calculés automatiquement, week-ends/fériés non recouverts par les barres).
- **Administration → Paramètres** (admin uniquement) : CRUD des utilisateurs, dernière connexion.

## Stack

PHP 8.2 vanilla (zéro framework, zéro Composer), MySQL 8.0, Nginx (Alpine), php-fpm, phpMyAdmin,
Docker Compose. Frontend : pages PHP multi-page + JS vanilla + Tailwind CDN. Auth : JWT HS256
hand-rolled (pas de lib externe). Email : SMTP custom via `fsockopen`/STARTTLS, avec repli sur
`mail()` si `SMTP_HOST` est vide.

## Commandes courantes

```bash
# Démarrer / arrêter la stack
docker compose up -d
docker compose down            # données conservées
docker compose down -v         # supprime aussi le volume MySQL

# Rebuild après modification du Dockerfile
docker compose build php && docker compose up -d

# Logs
docker compose logs -f php
docker compose logs -f nginx

# Lint PHP (pas de PHP en local — passer par le conteneur)
docker compose exec php sh -c "find api public tools config.php -name '*.php' -exec php -l {} \;"

# Appliquer une migration de schéma sur la base déjà en cours (ne pas recréer le volume
# si des données réelles existent — voir sql/schema.sql pour le schéma cible)
set -a; source .env; set +a
docker compose exec -T db mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" <<'SQL'
ALTER TABLE ...
SQL

# Créer le premier compte admin
docker compose exec php php /var/www/html/tools/create_admin.php

# Shell PHP
docker compose exec php sh
```

Il n'y a pas de suite de tests automatisés pour l'instant. La vérification se fait par lint PHP
via le conteneur + tests navigateur headless (Playwright, chromium) lors des changements UI.

## Accès

| Service | URL |
|---|---|
| Portail | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |

`.env` (non versionné) contrôle toute la config — partir de `.env.example`. Les identifiants
MySQL (`DB_NAME`/`DB_USER`/`DB_PASS`/`MYSQL_ROOT_PASSWORD`) sont interpolés depuis `.env` dans
`docker-compose.yml` (service `db` + `phpmyadmin`) : les deux fichiers doivent rester cohérents.

## Architecture

**Requêtes** : Nginx route `/api/*` vers `api/router.php` (un seul point d'entrée FastCGI, quel
que soit le chemin réel), et sert tout le reste depuis `public/` en accès fichier direct
(`*.php` → php-fpm, sinon repli sur `login.php`).

**`api/router.php`** : table de routes `[méthode, regex, fichier, handler, besoin_auth,
rôle_min]` parcourue séquentiellement. Sur un match, le fichier handler est `require`-inclus et
la fonction appelée avec `(method, params_nommés_de_la_regex, user_ou_null)`. `user` vient de
`require_auth()` (`api/middleware/auth.php`) si `besoin_auth` est vrai.

**Auth stateless** : `api/helpers.php` contient l'implémentation JWT complète (encode/decode
HS256, aucune dépendance). Le token est renvoyé au client au login, stocké côté JS dans
`localStorage` **et** dupliqué dans un cookie `plan_token` (voir `public/assets/js/app.js`,
objet `Auth`). Le cookie permet aux pages PHP classiques (`public/includes/auth_check.php`) de
vérifier la session côté serveur sans appel API, pendant que les appels `fetch` vers `/api/*`
passent le token en header `Authorization: Bearer`. Le logout est un no-op côté serveur (JWT
stateless) — la déconnexion réelle se fait en supprimant le token côté client.
`users.last_login_at` est mis à jour à chaque login réussi (`api/auth/login.php`).

**Rôles** : `admin` / `direction` / `user`, rangés par `ROLE_RANK` dans
`api/middleware/auth.php`. `require_auth(?string $min_role)` protège les routes API ; côté page,
`parametres.php` fait le même contrôle manuellement (redirection si non-admin) puisque
`auth_check.php` ne vérifie que l'authentification, pas le rôle.

**Pages protégées** : incluent `require 'includes/auth_check.php';` en première ligne (après
avoir positionné `$current_page` pour la sidebar), qui lit le cookie `plan_token`, décode le JWT,
et redirige vers `/login.php?next=...` si absent/invalide.

**Sidebar** (`public/includes/sidebar.php`) : sections repliables définies dans `$_nav_sections`
(accordéon géré par JS générique dans `footer.php`, piloté par `data-nav-toggle`/`data-nav-open`
— pas besoin de coder un toggle par section). Un item admin-only (`$_nav_admin`) s'ajoute sous un
label "Administration" si `$current_user['role'] === 'admin'`. Pour brancher un nouveau module,
ajouter une entrée dans `$_nav_sections` (ou une nouvelle section) plutôt que de dupliquer le
pattern d'accordéon.

**Modal générique** : `openModal()`/`closeModal()`/`esc()` dans `app.js`, réutilisés par tous les
modules (`planning.js`, `parametres.js`). **Piège connu** : ne jamais injecter du JSON/texte
utilisateur brut dans un attribut `onclick` (ex. `onclick='fn(${JSON.stringify(obj)})'`) — un
caractère spécial (apostrophe...) casse le HTML généré et provoque un `SyntaxError` silencieux
côté client. Pattern correct : passer seulement l'`id`, et retrouver l'objet dans un cache module
(`usersCache`, `chantiers`) déjà chargé.

**Réinitialisation de mot de passe** : token aléatoire 32 octets + expiration 1h stockés sur
`users.reset_token`/`reset_token_expires_at`. `api/auth/forgot_password.php` répond toujours un
succès générique (anti-énumération d'email) qu'un compte existe ou non. Le même mécanisme sert à
l'activation de compte créé par un admin (`api/admin/users.php` → `handle_admin_users_create`).

**Convention JSON API** : toute réponse passe par `json_success()`/`json_error()`
(`api/helpers.php`) — enveloppe `{success, data, message, errors}`, jamais de sortie brute.

### Module Planning (chantiers)

- **Schéma** : `chantiers` (nom, ville, `entreprise` ENUM('Moncomble','RVM'), couleur) a une
  relation 1-N vers `chantier_creneaux` (date_debut/date_fin, cascade delete) — un chantier peut
  avoir plusieurs créneaux de dates disjoints.
- **API** (`api/chantiers.php`) : `chantier_out()` recharge toujours le chantier + ses créneaux
  depuis la base après écriture (pas de construction manuelle de la réponse). Create/update
  remplacent la totalité des créneaux (`save_creneaux()` : delete puis re-insert en transaction)
  plutôt que de diffuser un patch — plus simple, le nombre de créneaux par chantier reste faible.
  La liste (`handle_chantiers_list`) trie par `MIN(date_debut)` par chantier (JOIN sur
  `chantier_creneaux`), pas par `created_at` — piège si on modifie cette requête, l'affichage doit
  rester trié par date de début réelle du chantier.
- **Rendu Gantt** (`public/assets/js/planning.js`) : grille CSS Grid pure (pas de lib externe),
  colonnes = 1 jour, lignes = 1 chantier. Une barre est découpée en plusieurs segments DOM (un par
  plage de jours ouvrés consécutifs) pour laisser apparaître les week-ends/jours fériés en
  transparence au lieu d'être recouverts par la couleur du chantier. Jours fériés français
  calculés côté client (`frenchHolidays()`, algorithme de Gauss pour Pâques) — aucune dépendance
  externe, aucun appel API.
- **Logos entreprise** : deux jeux d'images dans `public/assets/img/` — `logoMoncomble.webp` /
  `logoRVM.webp` (logos complets avec texte, utilisés dans le sélecteur du formulaire) et
  `mini-Moncomble.png` / `mini-RVM.png` (pictogrammes carrés, utilisés dans les lignes du Gantt).
  Le conteneur du mini-logo a une **largeur fixe** (28px) même si les deux images n'ont pas le
  même ratio largeur/hauteur — sans ça le texte nom/ville ne s'aligne pas verticalement entre les
  lignes.

## Étendre le portail

Pour ajouter un module métier futur : nouvelle route dans `api/router.php`, nouveau handler dans
`api/`, nouvelle page PHP dans `public/` qui positionne `$current_page` puis inclut
`includes/auth_check.php`, entrée correspondante dans `$_nav_sections` de `sidebar.php`. Le
`ROLE_RANK` existe déjà pour restreindre par rôle via `require_auth('direction')` etc. — s'en
servir plutôt que de réinventer un contrôle d'accès.

## graphify

This project has a graphify knowledge graph at graphify-out/.

Rules:
- Before answering architecture or codebase questions, read graphify-out/GRAPH_REPORT.md for god nodes and community structure
- If graphify-out/wiki/index.md exists, navigate it instead of reading raw files
- For cross-module "how does X relate to Y" questions, prefer `graphify query "<question>"`, `graphify path "<A>" "<B>"`, or `graphify explain "<concept>"` over grep — these traverse the graph's EXTRACTED + INFERRED edges instead of scanning files
- After modifying code files in this session, run `graphify update .` to keep the graph current (AST-only, no API cost)
