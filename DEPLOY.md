# Deploy su hosting OVH

Procedura per pubblicare/aggiornare **solaryatravel** sull'hosting condiviso OVH.

- **Dominio:** www.solaryatravel.com (registrato su Aruba, DNS → OVH)
- **Hosting:** OVH condiviso, host SSH `ssh.cluster100.hosting.ovh.net`, utente `solarys`
- **PHP server:** 8.4 (il progetto richiede `^8.3`). Binario: `/usr/local/php8.4/bin/php`
  (in shell non interattiva `php`/`php8.4` **non** sono nel PATH → usa il path assoluto).
- **Laravel:** 13
- **Root del progetto sul server:** `~/www`
- **Il dominio serve da:** `www/public`

Accesso SSH:

```bash
ssh solarys@ssh.cluster100.hosting.ovh.net
# autenticazione con password (oppure chiave in ~/.ssh/authorized_keys)
```

---

## 1. Primo deploy (installazione iniziale)

> Questa sezione serve **solo al setup iniziale** di un nuovo server. Per i rilasci
> di tutti i giorni si usa l'**autodeploy** descritto nella sezione 2.

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

### 1.8 Cron (reminder email + scadenze)

I reminder (24h/48h, saldo acconto, bonifico) e la scadenza delle prenotazioni
non pagate dipendono da due comandi artisan. Su OVH (hosting condiviso) il cron:
- ha frequenza **minima oraria** (non si può ogni minuto);
- nel pannello accetta come comando **solo un percorso di file** (niente argomenti
  né spazi), quindi NON si può usare `artisan schedule:run`.

Per questo ci sono due **wrapper PHP** in `cron/` che lanciano i comandi. Nel pannello
OVH → Hosting → Cron job, crea **due interventi programmati** (lingua PHP 8.4, frequenza
ogni ora), con questi percorsi (relativi alla home, senza `..`):

```
www/cron/send-reminders.php
www/cron/expire-unpaid.php
```

I comandi sono idempotenti (ogni email parte una sola volta, flag `*_sent_at`) e usano
finestre ampie, quindi il passo orario è sufficiente. Senza questi cron, i reminder e
le scadenze non partono.

### 1.9 Pagamenti: acconto, bonifico, penali

Configurabili da **Admin → Impostazioni → Prenotazioni**:
- **Penali di cancellazione**: % rimborso per fasce di giorni (default 14gg→70%, 7gg→50%, sotto→0%).
- **Acconto**: toggle + percentuale (default 50%) + ore entro cui pagare il saldo.
- **Bonifico**: toggle + dati IBAN. Le prenotazioni con bonifico restano in *Attesa bonifico*
  finché un admin non clicca "Conferma incasso bonifico" nella scheda prenotazione.

Default: acconto e bonifico **disattivi** → comportamento invariato finché non si attivano.
⚠️ I rimborsi su carta sono **reali** su Stripe: testare con chiavi di test prima del live.

---

## 2. Aggiornamenti successivi (deploy automatico)

Il deploy è **automatico** via GitHub Actions: ogni push sul branch **`production`**
si connette in SSH al server ed esegue `deploy.sh`. Lo sviluppo avviene su `main`.

### Flusso di rilascio

```bash
# sviluppi su main come sempre, poi quando vuoi rilasciare in produzione:
git checkout production
git merge main
git push origin production      # ← fa partire l'autodeploy (~10s)
git checkout main               # torni a sviluppare
```

Avvio manuale alternativo: tab **Actions → "Deploy in produzione (OVH)" → Run workflow**,
oppure direttamente sul server `cd ~/www && ./deploy.sh`.

### Cosa fa `deploy.sh` (versionato nel repo)

1. `git fetch` + `git reset --hard origin/production` (allinea ESATTAMENTE al remote)
2. `composer install` **solo se** `composer.lock` è cambiato
3. `migrate --force` **solo se** ci sono migration pendenti
4. `livewire:publish --assets` (asset Livewire statici, vedi 1.5)
5. Ricrea il symlink storage **relativo** se manca (vedi 1.4)
6. `config:clear` + `route:clear` + `view:clear` + `event:clear`

⚠️ `deploy.sh` **non** lancia `config:cache`/`optimize` di proposito: con un `.env`
malformato causerebbe un 500 a tappeto (vedi Troubleshooting).

⚠️ `git reset --hard` sovrascrive le modifiche locali ai file tracciati: **non editare
codice direttamente sul server**, passa sempre dal repo. `.env`, `vendor/` e `storage/`
sono gitignored e non vengono toccati.

### Componenti dell'infrastruttura di deploy

- **`.github/workflows/deploy.yml`** — trigger su push a `production`, usa `appleboy/ssh-action`.
- **GitHub → Settings → Secrets and variables → Actions**: `OVH_SSH_HOST`,
  `OVH_SSH_USER`, `OVH_SSH_KEY` (chiave privata dedicata Actions→OVH).
- **GitHub → Settings → Deploy keys**: "OVH produzione (solarys)" — chiave pubblica
  del server (`~/.ssh/github_deploy.pub`), read-only, per il `git pull` dal server.
- **Server** `~/.ssh/config`: blocco `Host github.com` con `IdentityFile ~/.ssh/github_deploy`.
- **Server** `git config core.fileMode false` (evita diff spuri sui permessi).

### Primo deploy / ripristino auth git (se il fetch dà "Permission denied (publickey)")

```bash
cd ~/www
# rimuovi eventuali sshCommand residui che puntano a chiavi in /tmp:
git config --local --unset core.sshCommand 2>/dev/null || true
# verifica che il pull funzioni:
git ls-remote --heads origin
```

---

## 3. Troubleshooting

| Sintomo | Causa probabile | Fix |
|---|---|---|
| Pagina bianca / 500 | manca `vendor/` | `php composer.phar install --no-dev --optimize-autoloader` |
| 500 dopo aver copiato i file | config cache vecchia | `php artisan config:clear` + `rm -f bootstrap/cache/config.php` |
| **500 su TUTTO il sito dopo `config:clear`** (anche da Admin → Deploy) + log `Failed to parse dotenv file` | `.env` **malformato** (es. una riga di comando shell incollata dentro il file). Finché la config era cachata l'errore restava nascosto; `config:clear` torna a leggere il `.env` e lo smaschera. | Controlla righe non conformi: `grep -nvE '^\s*#\|^\s*$\|^[A-Za-z_][A-Za-z0-9_]*=' .env` → correggile (backup prima: `cp -p .env .env.bak`), poi `php artisan config:clear`. ⚠️ Non rilanciare `config:cache`/`optimize` finché il `.env` non è pulito. |
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
