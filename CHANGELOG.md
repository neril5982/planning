# Changelog

Toutes les évolutions notables du portail sont documentées ici.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/) et le versionnage
[Semantic Versioning](https://semver.org/lang/fr/) (`MAJEUR.MINEUR.CORRECTIF`) :
- **MAJEUR** : changement structurant (ex. refonte d'un module).
- **MINEUR** : nouvelle fonctionnalité rétrocompatible (ex. nouveau module).
- **CORRECTIF** : correction de bug, ajustement visuel.

## [1.0.0] - 2026-08-09

### Ajouté

- **Portail** : connexion, déconnexion, mot de passe oublié / réinitialisation, gestion des rôles
  (`admin` / `direction` / `user`).
- **Gestion → Planning** : calendrier des chantiers en vue Gantt.
  - Création, modification, suppression d'un chantier.
  - Créneaux de dates multiples par chantier (un chantier peut apparaître en plusieurs blocs
    séparés dans le temps).
  - Jours fériés français calculés automatiquement, affichés avec libellé.
  - Les week-ends et jours fériés ne sont plus recouverts par la couleur des chantiers.
  - Rattachement de chaque chantier à une entreprise (**Moncomble** ou **RVM**), avec logo
    affiché sur chaque ligne du calendrier.
  - Palette de couleurs par défaut pour les chantiers, personnalisable.
- **Administration → Paramètres** (admin uniquement) : création, modification, suppression des
  comptes utilisateurs ; affichage de la date de dernière connexion.
- Numéro de version affiché dans le portail, avec ce changelog consultable directement dans
  l'application.

### Corrigé

- Suppression d'un utilisateur : une erreur JavaScript empêchait la suppression lorsque le nom
  contenait certains caractères spéciaux (ex. apostrophe).
- Tri du planning : les chantiers sont de nouveau triés par date de début (et non plus par date
  de création).
