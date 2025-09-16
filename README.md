# 🚒 Kuizu - Système de Quiz pour Jeunes Sapeurs-Pompiers

Kuizu est une plateforme interactive de quiz en temps réel conçue spécialement pour la formation des jeunes sapeurs-pompiers. Inspirée de Kahoot, elle permet aux instructeurs de créer des quiz interactifs et aux participants de jouer en temps réel avec leurs appareils mobiles.

## 🌟 Fonctionnalités

### Pour les Administrateurs
- ✅ Création et gestion de quiz avec questions à choix multiples ou vrai/faux
- ✅ Interface intuitive pour ajouter/modifier/supprimer des questions
- ✅ Génération automatique de QR codes pour chaque quiz
- ✅ Lancement de sessions de jeu en temps réel
- ✅ Contrôle des questions pendant la session (timer, passage à la suivante)
- ✅ Visualisation des participants connectés en temps réel
- ✅ Verrouillage/déverrouillage des quiz pour contrôler l'accès

### Pour les Joueurs
- ✅ Inscription et connexion avec système de "se souvenir de moi"
- ✅ Participation aux quiz via code de session ou QR code
- ✅ Interface de jeu responsive et mobile-friendly
- ✅ Timer visuel pour chaque question
- ✅ Système de points avec bonus de rapidité
- ✅ Classement en temps réel pendant les sessions
- ✅ Historique personnel avec statistiques détaillées
- ✅ Graphiques de progression (Chart.js)

### Fonctionnalités Techniques
- ✅ Base de données MySQL complète avec relations
- ✅ APIs REST pour la communication AJAX
- ✅ Système d'authentification sécurisé avec cookies persistants
- ✅ Interface responsive compatible mobile et desktop
- ✅ Mise à jour en temps réel (polling toutes les 2 secondes)
- ✅ Gestion des sessions de jeu avec états multiples
- ✅ Calcul automatique des scores et statistiques

## 🛠️ Installation

### Prérequis
- MAMP/XAMPP/WAMP ou serveur web avec PHP 7.4+
- MySQL 5.7+ ou MariaDB 10.3+
- Navigateur web moderne

### Étapes d'installation

1. **Cloner ou télécharger le projet**
   ```bash
   git clone [URL_DU_REPO] kuizu
   # ou décompresser l'archive dans votre dossier web
   ```

2. **Configuration de la base de données**
   - Ouvrir phpMyAdmin ou votre gestionnaire MySQL
   - Importer le fichier `database/schema.sql`
   - Ou exécuter les commandes SQL du fichier manuellement

3. **Configuration de l'application**
   - Ouvrir `config/database.php`
   - Modifier les paramètres de connexion selon votre environnement :
   ```php
   private $host = 'localhost';
   private $db_name = 'kuizu_db';
   private $username = 'root';
   private $password = 'root'; // Votre mot de passe MySQL
   private $port = '8889'; // Port MAMP par défaut, 3306 pour XAMPP
   ```

4. **Démarrer les services**
   - Démarrer Apache et MySQL via MAMP/XAMPP
   - Accéder à l'application via `http://localhost:8888/kuizu/` (MAMP) ou `http://localhost/kuizu/` (XAMPP)

## 🚀 Utilisation

### Première connexion
**Compte administrateur par défaut :**
- Email : `admin@sapeurs-pompiers.fr`
- Mot de passe : `password`

### Workflow typique

1. **L'administrateur :**
   - Se connecte et crée un quiz
   - Ajoute des questions avec leurs réponses
   - Lance une session de jeu
   - Partage le code de session ou le QR code

2. **Les joueurs :**
   - S'inscrivent ou se connectent
   - Rejoignent la session avec le code ou QR code
   - Participent au quiz en temps réel
   - Consultent leurs résultats et statistiques

## 📁 Structure du Projet

```
kuizu/
├── assets/                 # Ressources statiques
│   ├── css/               # Feuilles de style
│   │   ├── style.css      # Styles généraux
│   │   ├── admin.css      # Interface admin
│   │   ├── player.css     # Interface joueur
│   │   ├── auth.css       # Pages d'authentification
│   │   ├── landing.css    # Page d'accueil
│   │   ├── session.css    # Sessions de jeu
│   │   └── game.css       # Interface de jeu temps réel
│   └── js/                # Scripts JavaScript
│       ├── admin.js       # Fonctionnalités admin
│       ├── player.js      # Fonctionnalités joueur
│       ├── auth.js        # Authentification
│       ├── landing.js     # Page d'accueil
│       └── question-form.js # Création de questions
├── admin/                 # Interface administrateur
│   ├── dashboard.php      # Tableau de bord admin
│   ├── quiz_create.php    # Création de quiz
│   ├── quiz_questions.php # Gestion des questions
│   ├── question_create.php # Création de questions
│   ├── session_create.php # Création de sessions
│   └── session_manage.php # Gestion des sessions temps réel
├── player/                # Interface joueur
│   ├── dashboard.php      # Tableau de bord joueur
│   ├── join_session.php   # Rejoindre une session
│   ├── game_session.php   # Interface de jeu temps réel
│   └── history.php        # Historique et statistiques
├── auth/                  # Authentification
│   ├── login.php          # Connexion
│   ├── register.php       # Inscription
│   ├── logout.php         # Déconnexion
│   └── check_auth.php     # Middleware d'authentification
├── api/                   # APIs REST
│   ├── quiz_actions.php   # Actions sur les quiz
│   ├── question_actions.php # Actions sur les questions
│   ├── game_session.php   # Gestion des sessions
│   └── player_response.php # Réponses des joueurs
├── classes/               # Classes PHP
│   ├── User.php           # Gestion des utilisateurs
│   ├── Quiz.php           # Gestion des quiz
│   ├── Question.php       # Gestion des questions
│   └── GameSession.php    # Gestion des sessions de jeu
├── config/                # Configuration
│   └── database.php       # Configuration BDD
├── database/              # Base de données
│   └── schema.sql         # Structure et données initiales
└── index.php              # Page d'accueil
```

## 🎯 Fonctionnalités Avancées

### Système de Points
- Points de base par question (configurables)
- Bonus de rapidité (jusqu'à 50% de bonus)
- Calcul automatique des scores totaux

### QR Codes
- Génération automatique via API QR Server
- URL directe vers la page de connexion avec redirection automatique
- Affichage dans l'interface admin avec possibilité de téléchargement

### Temps Réel
- Mise à jour automatique des participants
- Synchronisation des questions entre admin et joueurs
- Classement en direct pendant les sessions

### Statistiques
- Historique complet des parties
- Graphiques d'évolution des scores (Chart.js)
- Statistiques par quiz et globales
- Taux de réussite et temps de réponse

## 🔧 Configuration Avancée

### Personnalisation des Couleurs
Modifier les variables CSS dans `assets/css/style.css` :
```css
:root {
    --primary-color: #dc2626;    /* Rouge sapeurs-pompiers */
    --secondary-color: #f59e0b;  /* Orange/jaune */
    --success-color: #10b981;    /* Vert */
    /* ... */
}
```

### Paramètres de Session
Dans `config/database.php` :
```php
ini_set('session.cookie_lifetime', 86400 * 7); // 7 jours
ini_set('session.gc_maxlifetime', 86400 * 7);
```

### Fréquence de Mise à Jour
Dans les fichiers JavaScript, modifier l'intervalle de polling :
```javascript
setInterval(function() {
    // Mise à jour
}, 2000); // 2 secondes par défaut
```

## 🐛 Dépannage

### Problèmes Courants

**Erreur de connexion à la base de données**
- Vérifier les paramètres dans `config/database.php`
- S'assurer que MySQL est démarré
- Vérifier que la base de données `kuizu_db` existe

**Les sessions ne fonctionnent pas**
- Vérifier que les cookies sont activés
- S'assurer que le serveur web a les permissions d'écriture
- Vérifier la configuration des sessions PHP

**Les QR codes ne s'affichent pas**
- Vérifier la connexion internet (utilise une API externe)
- Contrôler les paramètres de firewall/proxy

**Interface non responsive**
- Vider le cache du navigateur
- Vérifier que les fichiers CSS sont bien chargés
- Tester sur différents navigateurs

## 🔒 Sécurité

- Mots de passe hashés avec `password_hash()` PHP
- Protection CSRF sur les formulaires
- Validation et échappement des données utilisateur
- Sessions sécurisées avec tokens de "se souvenir de moi"
- Vérification des permissions pour chaque action

## 📱 Compatibilité

### Navigateurs Supportés
- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+

### Appareils
- Desktop (Windows, Mac, Linux)
- Tablettes (iPad, Android)
- Smartphones (iOS, Android)

## 🤝 Contribution

Pour contribuer au projet :
1. Fork le repository
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Créer une Pull Request

## 📄 Licence

Ce projet est développé pour les jeunes sapeurs-pompiers dans un cadre éducatif.

## 🆘 Support

Pour toute question ou problème :
- Consulter la section dépannage ci-dessus
- Vérifier les logs d'erreur PHP
- Tester avec les comptes de démonstration

---

**Développé avec ❤️ pour la formation des jeunes sapeurs-pompiers**
