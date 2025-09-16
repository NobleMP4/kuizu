# 📋 Guide d'Installation - Kuizu

Ce guide vous accompagne pas à pas pour installer et configurer Kuizu sur votre serveur local.

## 🔧 Prérequis

### Logiciels requis
- **MAMP** (Mac) ou **XAMPP** (Windows/Linux) ou **WAMP** (Windows)
- **Navigateur web moderne** (Chrome, Firefox, Safari, Edge)
- **Éditeur de texte** (optionnel, pour personnalisation)

### Versions recommandées
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur (ou MariaDB 10.3+)
- Apache 2.4 ou supérieur

## 📦 Installation

### Étape 1 : Préparation de l'environnement

#### Avec MAMP (Mac)
1. Télécharger et installer MAMP depuis [mamp.info](https://www.mamp.info/)
2. Démarrer MAMP et vérifier que les services Apache et MySQL sont actifs
3. Noter le port MySQL (par défaut 8889 pour MAMP)

#### Avec XAMPP (Windows/Linux)
1. Télécharger et installer XAMPP depuis [apachefriends.org](https://www.apachefriends.org/)
2. Démarrer le panneau de contrôle XAMPP
3. Démarrer Apache et MySQL
4. Le port MySQL par défaut est 3306

### Étape 2 : Installation des fichiers

1. **Placer les fichiers du projet**
   - Copier le dossier `kuizu` dans le répertoire web :
     - MAMP : `/Applications/MAMP/htdocs/kuizu/`
     - XAMPP : `C:\xampp\htdocs\kuizu\` (Windows) ou `/opt/lampp/htdocs/kuizu/` (Linux)

2. **Vérifier la structure**
   ```
   htdocs/kuizu/
   ├── admin/
   ├── player/
   ├── auth/
   ├── api/
   ├── assets/
   ├── classes/
   ├── config/
   ├── database/
   └── index.php
   ```

### Étape 3 : Configuration de la base de données

#### Création de la base de données

**Option A : Via phpMyAdmin (recommandé)**
1. Ouvrir phpMyAdmin :
   - MAMP : `http://localhost:8888/phpMyAdmin/`
   - XAMPP : `http://localhost/phpmyadmin/`

2. Cliquer sur "Nouvelle base de données"
3. Nom : `kuizu_db`
4. Interclassement : `utf8mb4_unicode_ci`
5. Cliquer "Créer"

6. Sélectionner la base `kuizu_db`
7. Aller dans l'onglet "Importer"
8. Choisir le fichier `database/schema.sql`
9. Cliquer "Exécuter"

**Option B : Via ligne de commande**
```bash
# Se connecter à MySQL
mysql -u root -p

# Créer et utiliser la base
CREATE DATABASE kuizu_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kuizu_db;

# Importer le schéma
SOURCE /chemin/vers/kuizu/database/schema.sql;
```

### Étape 4 : Configuration de l'application

1. **Ouvrir le fichier de configuration**
   - Éditer `config/database.php`

2. **MAMP - Configuration typique :**
   ```php
   private $host = 'localhost';
   private $db_name = 'kuizu_db';
   private $username = 'root';
   private $password = 'root';
   private $port = '8889';
   ```

3. **XAMPP - Configuration typique :**
   ```php
   private $host = 'localhost';
   private $db_name = 'kuizu_db';
   private $username = 'root';
   private $password = '';  // Pas de mot de passe par défaut
   private $port = '3306';
   ```

4. **Sauvegarder le fichier**

### Étape 5 : Test de l'installation

1. **Accéder à l'application**
   - MAMP : `http://localhost:8888/kuizu/`
   - XAMPP : `http://localhost/kuizu/`

2. **Vérifier la page d'accueil**
   - La page d'accueil doit s'afficher correctement
   - Pas d'erreurs PHP visibles

3. **Tester la connexion admin**
   - Cliquer sur "Se connecter"
   - Email : `admin@sapeurs-pompiers.fr`
   - Mot de passe : `password`

## ✅ Vérification de l'installation

### Tests à effectuer

1. **Connexion admin** ✓
   - Se connecter avec le compte admin par défaut
   - Accéder au tableau de bord admin

2. **Création de compte joueur** ✓
   - S'inscrire avec un nouveau compte
   - Se connecter en tant que joueur

3. **Création de quiz** ✓
   - Créer un nouveau quiz depuis l'interface admin
   - Ajouter quelques questions

4. **Session de jeu** ✓
   - Lancer une session depuis l'admin
   - Rejoindre la session avec le compte joueur

## 🔧 Dépannage

### Erreurs courantes

#### "Erreur de connexion à la base de données"
**Cause :** Paramètres de connexion incorrects
**Solution :**
1. Vérifier que MySQL est démarré
2. Contrôler les paramètres dans `config/database.php`
3. Tester la connexion via phpMyAdmin

#### "Page blanche" ou erreur 500
**Cause :** Erreur PHP
**Solution :**
1. Activer l'affichage des erreurs PHP :
   ```php
   // Ajouter en haut de index.php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
2. Consulter les logs d'erreur Apache
3. Vérifier les permissions des fichiers

#### "La base de données n'existe pas"
**Cause :** Base non créée ou mal nommée
**Solution :**
1. Recréer la base `kuizu_db` via phpMyAdmin
2. Réimporter le fichier `database/schema.sql`
3. Vérifier le nom dans `config/database.php`

#### Les sessions ne fonctionnent pas
**Cause :** Configuration des sessions PHP
**Solution :**
1. Vérifier que les cookies sont activés dans le navigateur
2. Contrôler les permissions du dossier de sessions PHP
3. Redémarrer Apache

#### Les styles ne se chargent pas
**Cause :** Problème de chemin ou de permissions
**Solution :**
1. Vérifier que le dossier `assets/` est accessible
2. Contrôler les chemins dans les fichiers PHP
3. Vider le cache du navigateur

### Logs utiles

**MAMP :**
- Erreurs Apache : `/Applications/MAMP/logs/apache_error.log`
- Erreurs MySQL : `/Applications/MAMP/logs/mysql_error_log.err`

**XAMPP :**
- Erreurs Apache : `xampp/apache/logs/error.log`
- Erreurs MySQL : `xampp/mysql/data/mysql_error.log`

## 🎯 Optimisations (optionnel)

### Performance
1. **Activer la compression gzip**
   - Ajouter dans `.htaccess` :
   ```apache
   <IfModule mod_deflate.c>
       AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript application/json
   </IfModule>
   ```

2. **Cache des ressources**
   ```apache
   <IfModule mod_expires.c>
       ExpiresActive On
       ExpiresByType text/css "access plus 1 month"
       ExpiresByType application/javascript "access plus 1 month"
   </IfModule>
   ```

### Sécurité
1. **Changer le mot de passe admin par défaut**
2. **Configurer HTTPS en production**
3. **Limiter l'accès aux fichiers sensibles**

## 📞 Support

Si vous rencontrez des difficultés :

1. **Vérifier la section dépannage** ci-dessus
2. **Consulter les logs d'erreur** de votre serveur
3. **Tester avec les comptes de démonstration**
4. **Vérifier la configuration** pas à pas

### Informations système utiles
- Version PHP : `<?php echo phpversion(); ?>`
- Extensions PHP chargées : `<?php print_r(get_loaded_extensions()); ?>`
- Configuration MySQL : Accessible via phpMyAdmin

## 🎉 Installation terminée !

Votre installation de Kuizu est maintenant prête. Vous pouvez :

1. **Créer vos premiers quiz** depuis l'interface admin
2. **Inviter des participants** à s'inscrire
3. **Lancer des sessions de jeu** interactives
4. **Consulter les statistiques** de progression

**Bon quiz ! 🚒**
