# Script de Déploiement Sécurisé - Gestion de Stock

## Fichiers Créés

### 1. `deploy.bat`
Script principal de déploiement qui :
- ✅ Vérifie les privilèges administrateur
- ✅ Détecte si un déploiement a déjà été effectué
- ✅ Bloque la copie non autorisée après publication
- ✅ Crée un fichier de verrouillage (`deploy.lock`)
- ✅ Sauvegarde la configuration existante
- ✅ Vérifie l'intégrité des fichiers
- ✅ Génère un rapport de déploiement

### 2. `deploy_exclude.txt`
Liste des fichiers et dossiers à exclure lors de la copie :
- `.git` (historique version)
- Fichiers de configuration sensible
- Fichiers temporaires
- Sauvegardes

## Fonctionnement

### Premier Déploiement
```batch
deploy.bat
```
Le script va :
1. Créer le dossier dans `C:\xampp\htdocs\gestion_stock`
2. Copier tous les fichiers du projet
3. Générer le fichier `deploy.lock` avec date et informations
4. Créer `SECURITE_README.txt` avec les règles

### Tentative de Deuxième Déploiement
Si quelqu'un essaie de copier manuellement ou relance le script :
- ⚠️ **Alerte rouge** : "DÉPLOIEMENT DÉJÀ EFFECTUÉ !"
- Affichage des informations du premier déploiement
- Demande de confirmation explicite pour continuer
- Option d'annulation recommandée

## Fichier de Verrouillage (`deploy.lock`)

Contient :
```
============================================
FICHIER DE VERROUILLAGE - AtelierRangement
============================================
Date de déploiement: [DATE HEURE]
Machine: [NOM_ORDINATEUR]
Utilisateur: [NOM_UTILISATEUR]
Version: 1.0.0

AVERTISSEMENT:
Ce fichier indique que le projet a été déployé.
Toute modification ou copie supplémentaire
doit être validée par l'administrateur.
============================================
```

## Sécurité

### Protections Incluses
1. **Détection de déploiement** : Vérifie la présence de `deploy.lock`
2. **Traçabilité** : Enregistre date, machine et utilisateur
3. **Confirmation requise** : Demande validation avant second déploiement
4. **Sauvegarde automatique** : Préserve la configuration existante
5. **Vérification d'intégrité** : Contrôle les fichiers critiques

### Comment Contourner (Administrateurs Uniquement)
Si une mise à jour légitime est nécessaire :
1. Supprimer manuellement `deploy.lock` du dossier de destination
2. OU répondre "Oui" à l'avertissement (déconseillé)
3. Utiliser une procédure de mise à jour validée

## Personnalisation

Modifier ces variables dans `deploy.bat` :
```batch
set "PROJECT_NAME=AtelierRangement"
set "VERSION=1.0.0"
set "XAMPP_ROOT=C:\xampp\htdocs"
set "TARGET_DIR=%XAMPP_ROOT%\gestion_stock"
```

## Utilisation Recommandée

1. **Développeur** : Modifie les fichiers dans le dossier source
2. **Administrateur** : Exécute `deploy.bat` pour publier
3. **Utilisateurs** : Accèdent via `http://localhost/gestion_stock/`
4. **Maintenance** : Utiliser uniquement `deploy.bat` pour les mises à jour

## URL d'Accès Après Déploiement

```
http://localhost/gestion_stock/gestion_stock.html
```

## Notes Importantes

⚠️ **Ne jamais copier manuellement** des fichiers dans le dossier de destination
⚠️ **Toujours utiliser** `deploy.bat` pour les déploiements
⚠️ **Conserver** le fichier `deploy.lock` comme preuve de déploiement
⚠️ **Exécuter en tant qu'administrateur** pour fonctionner correctement
