# Deploy su hosting OVH

Procedura per pubblicare/aggiornare **solaryatravel** sull'hosting condiviso OVH.

- **Dominio:** www.solaryatravel.com (registrato su Aruba, DNS → OVH)
- **Hosting:** OVH condiviso, cluster `ssh02.cluster100.gra.hosting.ovh.net`, utente `solarys`
- **PHP server:** 8.4 (il progetto richiede `^8.3`)
- **Laravel:** 13
- **Root del progetto sul server:** `~/www`
- **Il dominio serve da:** `www/public`

Accesso SSH:

```bash
ssh solarys@ssh02.cluster100.gra.hosting.ovh.net
# autenticazione con password
```

---

## 1. Primo deploy (installazione iniziale)

I file vanno caricati in `~/www` (via Git o FTP). **Non** si copiano da locale: `vendor/` e `storage/app/public/`.

### 1.1 Dipendenze Composer

`composer` non è disponibile come comando diretto su OVH. Va scaricato una volta:

```bash
cd ~/www
wget https://getcomposer.org/installer -O - | php   # crea composer.phar
php composer.phar install --no-dev --optimize-autoloader
```

### 1.2 File .env di produzione

Il `.env` **non** va copiato dal locale. Valori critici da impostare sul server:

```env
APP_ENV=production
APP_DEBUG=false              # MAI true online: espone errori e credenziali
APP_URL=https://www.solaryatravel.com

DB_CONNECTION=mysql
DB_HOST=...                  # i dati del DB OVH (dal pannello)
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

⚠️ **APP_KEY**: NON va rigenerata se si migra un DB con dati cifrati. Mantenere la stessa chiave.

Dopo ogni modifica al `.env`:

```bash
php artisan config:clear
php artisan view:clear
```

### 1.3 Immagini caricate (storage/app/public)

Questa cartella è in `.gitignore`, quindi non arriva via Git. Va caricata a parte.
Dal **Mac** (terminale locale, non SSH):

```bash
cd /Users/claudio/DEVELOPMENT/solaryatravel
scp -r storage/app/public/* solarys@ssh02.cluster100.gra.hosting.ovh.net:~/www/storage/app/public/
```

Poi sul server sistema i permessi (scp può copiarli sbagliati):

```bash
chmod -R 755 storage/app/public
find storage/app/public -type f -exec chmod 644 {} \;
```

### 1.4 Symlink storage → public (PUNTO CRITICO)

⚠️ **NON usare `php artisan storage:link`** su questo hosting: crea un symlink con percorso
**assoluto** (`/home/solarys/www/storage/app/public`), e Apache non può attraversare la home
`/home/solarys` (permessi `drwx---r-x` imposti da OVH, non modificabili) → le immagini danno **403**.

Creare invece un symlink **relativo**:

```bash
cd ~/www
rm -f public/storage
ln -s ../storage/app/public public/storage
# verifica: deve risultare  public/storage -> ../storage/app/public
ls -la public/storage
```

### 1.5 Asset Livewire come file statici (PUNTO CRITICO)

⚠️ Su OVH, la rotta dinamica `/livewire/livewire.min.js` (servita da PHP) sotto
HTTP/2 con molti stream concorrenti causa `ERR_HTTP2_PROTOCOL_ERROR` nel browser
→ Livewire non si carica → i componenti (form di prenotazione) e gli `onclick`
che dipendono da JS della pagina si rompono.

Soluzione: servire lo script Livewire come **file statico** da `public/vendor/livewire/`.
`AppServiceProvider::serveLivewireAssetsAsStaticFile()` punta automaticamente lo
`<script src>` a quel percorso **se il file esiste**. Va quindi pubblicato:

```bash
php artisan livewire:publish --assets
# verifica: deve esistere public/vendor/livewire/livewire.min.js
ls -la public/vendor/livewire/
```

Da rieseguire dopo ogni aggiornamento di Livewire (vedi sotto).

### 1.6 Struttura cartelle storage

Se cartelle standard di `storage` non sono state copiate (es. via git), ricrearle:

```bash
mkdir -p storage/logs storage/framework/cache/data storage/framework/views storage/framework/sessions storage/app/public
chmod -R 775 storage bootstrap/cache
```

### 1.7 Sessione e cookie

Per il login in produzione (HTTPS dietro proxy OVH), nel `.env`:

```env
SESSION_DRIVER=database          # richiede la tabella sessions (php artisan migrate)
SESSION_SECURE_COOKIE=true
APP_URL=https://www.solaryatravel.com
```

---

## 2. Aggiornamenti successivi (deploy di nuove versioni)

```bash
cd ~/www
# 1. aggiorna i file (git pull / FTP)
php composer.phar install --no-dev --optimize-autoloader   # se cambiate dipendenze
php artisan livewire:publish --assets                      # ripubblica asset Livewire statici
php artisan migrate --force                                # se ci sono nuove migration
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

Se hai rilanciato `storage:link` per errore, ripristina il link relativo (vedi 1.4).

---

## 3. Troubleshooting

| Sintomo | Causa probabile | Fix |
|---|---|---|
| Pagina bianca / 500 | manca `vendor/` | `php composer.phar install --no-dev --optimize-autoloader` |
| 500 dopo aver copiato i file | config cache vecchia | `php artisan config:clear` + `rm -f bootstrap/cache/config.php` |
| Immagini 403 | symlink storage **assoluto** (vedi 1.4) | ricreare il symlink **relativo** |
| Immagini 404 | file non caricati o `APP_URL` errato | verificare `storage/app/public` e `APP_URL` |
| `ERR_HTTP2_PROTOCOL_ERROR` su `livewire.min.js` + funzioni JS "is not defined" | asset Livewire serviti via PHP sotto HTTP/2 | `php artisan livewire:publish --assets` (vedi 1.5) |
| Login non persiste (torna al login) in coming soon | il middleware bloccava il POST di login | risolto: `ComingSoonMiddleware` filtra per path, non per nome rotta |
| `storage/logs` mancante / niente log | cartelle storage non copiate | ricrearle (vedi 1.6) |
| Errori non visibili | log | `tail -n 50 storage/logs/laravel.log` |

### .htaccess

La root `~/www/.htaccess` reindirizza tutto a `public/`. Deve proteggere `.env` e `.git`, ma
**non** deve bloccare `storage/` (una regola `RewriteRule ^storage/.* - [F,L]` blocca le immagini):

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^\.env - [F,L]
    RewriteRule ^\.git - [F,L]
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### Test rapido di un'immagine (dal Mac)

```bash
curl -sI "https://www.solaryatravel.com/storage/tours/<id>/<file>"
# atteso: HTTP/2 200, content-type: image/...
```
