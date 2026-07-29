#!/usr/bin/env bash
#
# Deploy di produzione per Solarya Travel su OVH condiviso.
# Eseguito da GitHub Actions (workflow .github/workflows/deploy.yml) via SSH,
# oppure manualmente:  cd ~/www && ./deploy.sh
#
# Idempotente: composer e migrate girano SOLO se serve (lock cambiato /
# migration pendenti). Sempre: pull + clear cache + asset Livewire + symlink.
#
set -euo pipefail

# --- Config ---------------------------------------------------------------
APP_DIR="$HOME/www"
BRANCH="production"
PHP="/usr/local/php8.4/bin/php"          # su OVH php/php8.4 non sono nel PATH non-interattivo
COMPOSER="$PHP composer.phar"

cd "$APP_DIR"

echo "▶ Deploy avviato $(date '+%Y-%m-%d %H:%M:%S')"
echo "▶ Branch: $BRANCH"

# --- 1. Aggiorna il codice ------------------------------------------------
# Hash PRIMA del pull, per capire cosa è cambiato:
#   - composer.lock → sono cambiate le dipendenze (serve install)
#   - composer.json → può essere cambiata la sezione autoload (files/psr-4):
#     in quel caso il lock resta identico ma l'autoloader in vendor/ è STALE.
#     Senza rigenerarlo le funzioni degli helper (tdb(), locale_route(), …)
#     risultano undefined e il sito va in 500 su ogni pagina.
LOCK_BEFORE="$(md5sum composer.lock 2>/dev/null | awk '{print $1}' || true)"
JSON_BEFORE="$(md5sum composer.json 2>/dev/null | awk '{print $1}' || true)"

git fetch --prune origin
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"        # allinea esattamente al remote (niente conflitti su file locali)

echo "▶ Ora su commit: $(git log -1 --oneline)"

# --- 2. Dipendenze / autoloader ------------------------------------------
LOCK_AFTER="$(md5sum composer.lock 2>/dev/null | awk '{print $1}' || true)"
JSON_AFTER="$(md5sum composer.json 2>/dev/null | awk '{print $1}' || true)"

if [ "$LOCK_BEFORE" != "$LOCK_AFTER" ] || [ ! -d vendor ]; then
    echo "▶ composer.lock cambiato → composer install"
    $COMPOSER install --no-dev --optimize-autoloader --no-interaction
elif [ "$JSON_BEFORE" != "$JSON_AFTER" ]; then
    # Lock invariato ma json modificato: basta il dump dell'autoloader,
    # molto più rapido di un install completo.
    echo "▶ composer.json cambiato (lock invariato) → dump-autoload"
    $COMPOSER dump-autoload --no-dev --optimize --no-interaction
else
    echo "▷ composer.json/lock invariati → salto composer"
fi

# --- 2b. VERIFICA che l'autoloader risolva davvero gli helper --------------
# Il confronto di hash sopra non basta: deploy.sh viene eseguito nella versione
# che il server aveva PRIMA del pull, quindi un miglioramento del controllo
# entra in vigore solo dal deploy successivo. Se un rilascio aggiunge un file a
# "autoload.files" (gli helper tdb(), locale_route(), season_label()), il primo
# deploy dopo la modifica può saltare il dump e mandare il sito in 500 su OGNI
# pagina. Qui la verifica è sul FATTO, non sulle ipotesi: se una funzione attesa
# non esiste, l'autoloader viene rigenerato.
MISSING_HELPERS="$($PHP -r '
require "vendor/autoload.php";
$missing = [];
foreach (["tdb", "locale_route", "season_label"] as $fn) {
    if (! function_exists($fn)) { $missing[] = $fn; }
}
echo implode(",", $missing);
' 2>/dev/null || echo "errore")"

if [ -n "$MISSING_HELPERS" ]; then
    echo "▶ Helper non risolti ($MISSING_HELPERS) → rigenero l'autoloader"
    $COMPOSER dump-autoload --no-dev --optimize --no-interaction
else
    echo "▷ Autoloader OK: tutti gli helper sono risolti"
fi

# --- 3. Migration (solo se ce ne sono di pendenti) ------------------------
if $PHP artisan migrate:status 2>/dev/null | grep -q "Pending"; then
    echo "▶ Migration pendenti → migrate --force"
    $PHP artisan migrate --force
else
    echo "▷ Nessuna migration pendente"
fi

# --- 4. Asset Livewire statici (fix HTTP/2 OVH) ---------------------------
$PHP artisan livewire:publish --assets >/dev/null

# --- 5. Symlink storage RELATIVO (OVH non attraversa /home in assoluto) ----
if [ ! -L public/storage ] || [ "$(readlink public/storage)" != "../storage/app/public" ]; then
    echo "▶ Ricreo symlink storage relativo"
    rm -f public/storage
    ln -s ../storage/app/public public/storage
fi

# --- 5b. Symlink storage della cartella b2b/ (canale agenzie) -------------
# La cartella b2b/ (secondo document root del sottodominio b2b.solaryatravel.com)
# arriva da git con index.php, .htaccess e i symlink degli asset (build, assets,
# fonts, images, vendor → ../public/*, già committati come symlink mode 120000).
# L'UNICO symlink mancante è storage, gitignorato come public/storage: lo creiamo
# qui, RELATIVO (OVH non attraversa /home in assoluto, vedi punto 5).
if [ -d b2b ]; then
    if [ ! -L b2b/storage ] || [ "$(readlink b2b/storage)" != "../storage/app/public" ]; then
        echo "▶ Ricreo symlink b2b/storage relativo"
        rm -rf b2b/storage
        ln -s ../storage/app/public b2b/storage
    fi
fi

# --- 6. Cache: clear poi rebuild ------------------------------------------
# NB: niente config:cache automatico (un .env malformato causerebbe 500 a tappeto).
$PHP artisan config:clear
$PHP artisan route:clear
$PHP artisan view:clear
$PHP artisan event:clear || true

echo "✓ Deploy completato $(date '+%Y-%m-%d %H:%M:%S')"
