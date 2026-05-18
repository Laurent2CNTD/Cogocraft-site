# CogoCraft — Site web

Site statique HTML/CSS/JS + webhook GitHub → déploiement auto sur push.

---

## Structure

```
cogocraft-site/
├── index.html
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   └── images/
│       ├── chantier1.jpg
│       ├── chantier1_thumb.jpg
│       ├── chantier2.jpg
│       ├── chantier2_thumb.jpg
│       ├── hikvision.jpg
│       └── hikvision_thumb.jpg
├── webhook.php
├── deploy.sh
└── README.md
```

---

## Mise en production (une seule fois)

### 1. Créer le repo GitHub

Crée un repo `cogocraft-site` sur https://github.com/new (public ou privé).

### 2. Pousser le code depuis ta machine locale

```bash
git clone <ce repo ou copie les fichiers>
cd cogocraft-site
git init
git remote add origin https://github.com/TON_USERNAME/cogocraft-site.git
git add .
git commit -m "Initial commit"
git push -u origin main
```

### 3. Sur la VM Oracle — préparer le déploiement

```bash
# Installer git si absent
sudo apt install -y git rsync php php-cli

# Donner les droits sudo à www-data pour le script deploy
echo "www-data ALL=(ALL) NOPASSWD: /bin/bash /var/www/cogocraft/deploy.sh" \
  | sudo tee /etc/sudoers.d/cogocraft

# Premier déploiement manuel
sudo bash /var/www/cogocraft/deploy.sh

# Permissions log
sudo touch /var/log/cogocraft-deploy.log
sudo chown www-data:www-data /var/log/cogocraft-deploy.log
```

### 4. Config Nginx (vérifier que c'est en place)

```nginx
server {
    listen 80;
    server_name cogocraft.com www.cogocraft.com;
    root /var/www/cogocraft;
    index index.html;

    location /webhook.php {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param WEBHOOK_SECRET "TON_SECRET_ICI";
    }

    location / {
        try_files $uri $uri/ =404;
    }
}
```

### 5. GitHub Webhook

Sur GitHub → Settings → Webhooks → Add webhook :
- **Payload URL** : `https://cogocraft.com/webhook.php`
- **Content type** : `application/json`
- **Secret** : le même que `WEBHOOK_SECRET` dans Nginx
- **Events** : Just the push event

### 6. Vérifier

```bash
# Logs de déploiement
tail -f /var/log/cogocraft-deploy.log
```

---

## Workflow quotidien

```bash
git add .
git commit -m "Update site"
git push
# → le site est mis à jour automatiquement en ~10s
```
