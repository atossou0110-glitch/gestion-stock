@echo off
setlocal EnableDelayedExpansion

:: ============================================
:: Script de Déploiement Sécurisé - Atelier Rangement
:: Empêche la copie non autorisée après publication
:: ============================================

set "PROJECT_NAME=AtelierRangement"
set "VERSION=1.0.0"
set "DEPLOY_DATE_FILE=deploy.lock"
set "XAMPP_ROOT=C:\xampp\htdocs"
set "TARGET_DIR=%XAMPP_ROOT%\gestion_stock"

:: Couleurs pour l'affichage
set "GREEN=[92m"
set "RED=[91m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "RESET=[0m"

echo %GREEN%============================================%RESET%
echo %GREEN%   Déploiement Sécurisé - %PROJECT_NAME%%RESET%
echo %GREEN%   Version: %VERSION%%RESET%
echo %GREEN%============================================%RESET%
echo.

:: Vérifier les privilèges administrateur
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo %RED%Erreur: Ce script nécessite des privilèges administrateur.%RESET%
    echo %YELLOW%Veuillez exécuter ce script en tant qu'administrateur.%RESET%
    echo.
    pause
    exit /b 1
)

:: Vérifier si XAMPP est installé
if not exist "%XAMPP_ROOT%" (
    echo %RED%Erreur: Dossier XAMPP htdocs non trouvé à %XAMPP_ROOT%%RESET%
    echo %YELLOW%Veuillez vérifier l'installation de XAMPP.%RESET%
    echo.
    pause
    exit /b 1
)

:: Vérifier si le dossier de destination existe déjà
if exist "%TARGET_DIR%\%DEPLOY_DATE_FILE%" (
    echo %RED%============================================%RESET%
    echo %RED%   ATTENTION: DÉPLOIEMENT DÉJÀ EFFECTUÉ !%RESET%
    echo %RED%============================================%RESET%
    echo.
    
    :: Lire la date de déploiement
    for /f "tokens=*" %%a in ('type "%TARGET_DIR%\%DEPLOY_DATE_FILE%"') do set "DEPLOY_INFO=%%a"
    
    echo %YELLOW%Information de déploiement:%RESET%
    echo %DEPLOY_INFO%
    echo.
    echo %RED%Ce projet a déjà été déployé et verrouillé.%RESET%
    echo %RED%La copie de fichiers après publication est INTERDITE.%RESET%
    echo.
    echo %YELLOW%Si vous devez mettre à jour le projet, contactez l'administrateur.%RESET%
    echo %YELLOW%Ou supprimez manuellement le fichier %DEPLOY_DATE_FILE% du dossier de destination.%RESET%
    echo.
    
    choice /C YN /M "Voulez-vous vraiment continuer (risque de sécurité) ?"
    if errorlevel 2 (
        echo %GREEN%Déploiement annulé.%RESET%
        pause
        exit /b 0
    )
    if errorlevel 1 (
        echo %YELLOW%Continuation avec risque de sécurité...%RESET%
        echo.
    )
)

:: Créer le dossier de destination s'il n'existe pas
if not exist "%TARGET_DIR%" (
    echo %BLUE%Création du dossier de destination...%RESET%
    mkdir "%TARGET_DIR%"
    if errorlevel 1 (
        echo %RED%Erreur lors de la création du dossier.%RESET%
        pause
        exit /b 1
    )
)

:: Sauvegarder la configuration existante si elle existe
if exist "%TARGET_DIR%\api\config\database.php" (
    echo %BLUE%Sauvegarde de la configuration existante...%RESET%
    if not exist "%TARGET_DIR%\backup" mkdir "%TARGET_DIR%\backup"
    copy "%TARGET_DIR%\api\config\database.php" "%TARGET_DIR%\backup\database.php.bak" >nul
    copy "%TARGET_DIR%\api\config\security.php" "%TARGET_DIR%\backup\security.php.bak" >nul 2>&1
    echo %GREEN%Configuration sauvegardée dans le dossier backup.%RESET%
    echo.
)

:: Copier les fichiers du projet
echo %BLUE%Copie des fichiers du projet...%RESET%
echo.

:: Copier tous les fichiers et dossiers sauf .git et fichiers sensibles
xcopy /E /I /Y /EXCLUDE:deploy_exclude.txt "%~dp0" "%TARGET_DIR%" >nul

if errorlevel 1 (
    echo %RED%Erreur lors de la copie des fichiers.%RESET%
    pause
    exit /b 1
)

:: Générer le fichier de verrouillage
echo %GREEN%Génération du fichier de verrouillage...%RESET%

set "TIMESTAMP=%DATE% %TIME%"
set "COMPUTER_NAME=%COMPUTERNAME%"
set "USER_NAME=%USERNAME%"

(
    echo ============================================
    echo FICHIER DE VERROUILLAGE - %PROJECT_NAME%
    echo ============================================
    echo Date de déploiement: %TIMESTAMP%
    echo Machine: %COMPUTER_NAME%
    echo Utilisateur: %USER_NAME%
    echo Version: %VERSION%
    echo.
    echo AVERTISSEMENT:
    echo Ce fichier indique que le projet a été déployé.
    echo Toute modification ou copie supplémentaire
    echo doit être validée par l'administrateur.
    echo ============================================
) > "%TARGET_DIR%\%DEPLOY_DATE_FILE%"

:: Créer un fichier README de sécurité
(
    echo SECURITE - GESTION DE STOCK ATELIER
    echo ====================================
    echo.
    echo Ce dossier contient une version PUBLIEE du projet.
    echo.
    echo REGLES IMPORTANTES:
    echo 1. Ne pas copier de nouveaux fichiers manuellement
    echo 2. Ne pas modifier les fichiers sans validation
    echo 3. Utiliser uniquement le script deploy.bat pour les mises à jour
    echo 4. Le fichier deploy.lock sert de preuve de déploiement
    echo.
    echo En cas de problème, contactez l'administrateur système.
    echo.
    echo Date de déploiement: %TIMESTAMP%
) > "%TARGET_DIR%\SECURITE_README.txt"

:: Vérifier l'intégrité des fichiers critiques
echo.
echo %BLUE%Vérification de l'intégrité...%RESET%

set "INTEGRITY_OK=1"

if not exist "%TARGET_DIR%\gestion_stock.html" (
    echo %RED%[ERREUR] fichier principal manquant%RESET%
    set "INTEGRITY_OK=0"
)

if not exist "%TARGET_DIR%\api\bootstrap.php" (
    echo %RED%[ERREUR] bootstrap API manquant%RESET%
    set "INTEGRITY_OK=0"
)

if not exist "%TARGET_DIR%\app.js" (
    echo %RED%[ERREUR] fichier JavaScript manquant%RESET%
    set "INTEGRITY_OK=0"
)

if %INTEGRITY_OK%==1 (
    echo %GREEN%[OK] Intégrité vérifiée%RESET%
) else (
    echo %RED%[ATTENTION] Certains fichiers peuvent manquer%RESET%
)

:: Afficher le résumé
echo.
echo %GREEN%============================================%RESET%
echo %GREEN%   DÉPLOIEMENT TERMINÉ AVEC SUCCÈS%RESET%
echo %GREEN%============================================%RESET%
echo.
echo %BLUE%Résumé:%RESET%
echo - Destination: %TARGET_DIR%
echo - Date: %TIMESTAMP%
echo - Machine: %COMPUTER_NAME%
echo - Utilisateur: %USER_NAME%
echo.
echo %YELLOW%IMPORTANT:%RESET%
echo Le fichier %DEPLOY_DATE_FILE% a été créé pour verrouiller ce déploiement.
echo Toute tentative de copie future sera détectée et bloquée.
echo.
echo %GREEN%Pour accéder à l'application:%RESET%
echo Ouvrez votre navigateur et allez à:
echo http://localhost/gestion_stock/gestion_stock.html
echo.
echo %GREEN%============================================%RESET%

pause
