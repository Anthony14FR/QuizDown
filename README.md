# Installation QuizDown

## Prérequis
- Docker & Docker Compose
- Make (Makefile pour automatiser les commandes)
- Symfony CLI

## Installation
```bash
git clone https://github.com/Anthony14FR/QuizDown
cd QuizDown
cp .env.dev .env
composer install
npm i && npm run dev
docker compose up -d
make reset
```

## Démarrage
```bash
symfony serve
```

## Accès
- Application: http://localhost:8000
- PhpMyAdmin: http://localhost:8081
    - User: symfony
    - Password: symfony

## Comptes
- Admin: admin@orus.com / Admin123
- User: user@orus.com / User123


# Schéma de la base de données
![Database Schema](./bdd.png)


# Cahier des Charges - QuizDown

## Objectif
Plateforme web de quiz interactifs permettant aux administrateurs de créer des quiz et aux utilisateurs d'y participer.

## Fonctionnalités

### Utilisateurs
- Inscription/Connexion avec validation email
- Profil
- Consultation historique des quiz passés
- Système de badges évolutifs
- Commentaires sur les quiz
- Création de quiz personnalisés
- Réponse aux quiz

### Quiz
- 3 types: Standard, Chronométré, Pénalités
- Questions à choix unique/multiple/vrai-faux
- Système de points configurable
- Catégories et tags
- Historique des scores
- Classement des quiz populaires

### Administration
- Gestion complète des quiz
- Modération des commentaires
- Gestion des utilisateurs
- Administration des badges
- Gestion des catégories/tags
- Génération de quiz via IA

## Aspects Techniques
- Framework Symfony
- Base de données MySQL
- Interface Twig/Tailwind/DaisyUI
- Docker pour l'environnement
- API OpenAI pour génération

## Contraintes
- Interface responsive
- Validation stricte des données
- Protection CSRF
- Rôles utilisateurs distincts
- Tests automatisés

