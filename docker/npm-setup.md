# Guide de configuration : exam.iticparis.com

Une fois que le déploiement est terminé, suivez ces étapes pour activer votre nom de domaine et le SSL (HTTPS).

## 1. Accès à l'interface
Connectez-vous à l'interface de gestion de **Nginx Proxy Manager** :
- **Adresse** : `http://VOTRE_IP_VPS:81`
- **Identifiants par défaut** :
  - Email : `admin@example.com`
  - Mot de passe : `changeme`

*Note : NPM vous demandera de changer ces identifiants dès la première connexion.*

## 2. Ajouter le nom de domaine
1. Allez dans l'onglet **"Hosts"** -> **"Proxy Hosts"**.
2. Cliquez sur **"Add Proxy Host"**.
3. **Domain Names** : `exam.iticparis.com`
4. **Scheme** : `http`
5. **Forward Hostname / IP** : `nginx` (le nom du service Docker interne)
6. **Forward Port** : `80`
7. Cochez **"Block Common Exploits"**.

## 3. Activer le SSL (HTTPS)
1. Toujours dans la fenêtre de configuration du Proxy Host, allez dans l'onglet **"SSL"**.
2. Dans le menu déroulant, sélectionnez **"Request a new SSL Certificate"**.
3. Cochez **"Force SSL"** et **"HTTP/2 Support"**.
4. Saisissez votre email pour Let's Encrypt.
5. Cochez **"I Agree to the Let's Encrypt Terms of Service"**.
6. Cliquez sur **"Save"**.

**Félicitations !** Votre application est maintenant accessible en HTTPS sur [https://exam.iticparis.com](https://exam.iticparis.com).
