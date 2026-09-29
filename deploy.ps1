#Requires -Version 5.1
<#
.SYNOPSIS
    Builds a clean, uploadable copy of the site for free shared hosting.

.DESCRIPTION
    Free shared hosts have no SSH, so there is no way to run composer or artisan
    on the server. Everything therefore has to be prepared here and uploaded as
    files. This script produces that prepared copy in one step.

    It never touches your working copy: the app is copied to a staging folder
    first and composer runs there, so your local vendor/ keeps its dev tools and
    your local .env is left alone.

.PARAMETER AppUrl
    The public address of the site, e.g. https://yourname.byet.org. Used for
    APP_URL, which is what image and asset URLs are built from.

.PARAMETER OutputDirectory
    Where to put the zip. Defaults to a 'deploy' folder next to the project.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File deploy.ps1 -AppUrl https://yourname.byet.org
#>

[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string] $AppUrl,

    # Resolved in the body rather than here: $PSScriptRoot is not yet set while
    # param defaults are evaluated.
    [string] $OutputDirectory
)

$ErrorActionPreference = 'Stop'

$root = $PSScriptRoot
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$staging = Join-Path $root '.deploy-staging'

if (-not $OutputDirectory) {
    $OutputDirectory = Join-Path (Split-Path -Parent $root) 'deploy'
}
$zip = Join-Path $OutputDirectory "portfolio-$stamp.zip"

function Write-Step($message) {
    Write-Host "==> $message" -ForegroundColor Cyan
}

function Fail($message) {
    Write-Host "FAILED: $message" -ForegroundColor Red
    exit 1
}

# --------------------------------------------------------------- preflight ---

Write-Step 'Checking prerequisites'

$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) { Fail 'php is not on PATH.' }

if ($AppUrl -notmatch '^https?://') {
    Fail "AppUrl must start with http:// or https:// (got '$AppUrl')."
}
$AppUrl = $AppUrl.TrimEnd('/')

# ------------------------------------------------------------- build assets ---

Write-Step 'Building front-end assets (npm run build)'
Push-Location $root
try {
    & npm run build
    if ($LASTEXITCODE -ne 0) { Fail 'npm run build failed. Fix that before deploying.' }
} finally {
    Pop-Location
}

# The Vite manifest is what Laravel reads to resolve asset() calls, so its
# absence is the single most common reason a deployed site renders unstyled.
# Vite moved it out of .vite/ in version 6, so both locations are accepted.
$manifest = @(
    (Join-Path $root 'public\build\manifest.json')
    (Join-Path $root 'public\build\.vite\manifest.json')
) | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $manifest) { Fail 'No Vite manifest in public/build. The build did not produce one.' }
Write-Host "    manifest ok ($(Split-Path $manifest -Leaf), $([Math]::Round((Get-Item $manifest).Length / 1KB)) KB)"

# ------------------------------------------------------------ stage a copy ---

Write-Step 'Copying the application to a staging folder'

if (Test-Path $staging) { Remove-Item -Recurse -Force $staging }
New-Item -ItemType Directory -Path $staging | Out-Null

# Copied, not moved, so a failure here cannot damage the working copy.
$copy = @(
    'app', 'bootstrap', 'config', 'database', 'lang', 'public', 'resources',
    'routes', 'storage', 'vendor', 'artisan', 'composer.json', 'composer.lock'
)
foreach ($item in $copy) {
    $source = Join-Path $root $item
    if (Test-Path $source) {
        Copy-Item -Recurse -Force $source (Join-Path $staging $item)
    }
}

# Vite's dev-server pointer would send the browser looking for a server that
# does not exist on shared hosting.
$hot = Join-Path $staging 'public\hot'
if (Test-Path $hot) { Remove-Item -Force $hot }

Write-Host '    trimming logs and caches'
Get-ChildItem (Join-Path $staging 'storage\logs') -File -ErrorAction SilentlyContinue |
    Where-Object { $_.Name -ne '.gitignore' } | Remove-Item -Force
foreach ($cacheDir in @('storage\framework\cache\data', 'storage\framework\sessions', 'storage\framework\views')) {
    Get-ChildItem (Join-Path $staging $cacheDir) -File -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -ne '.gitignore' } | Remove-Item -Force
}

# ------------------------------------------------------- production vendor ---

Write-Step 'Installing production dependencies only (composer install --no-dev)'
Push-Location $staging
try {
    & composer install --no-dev --optimize-autoloader --no-interaction --quiet
    if ($LASTEXITCODE -ne 0) { Fail 'composer install failed in the staging folder.' }
} finally {
    Pop-Location
}

# ------------------------------------------------------- one-time symlink ---

Write-Step 'Adding the one-time storage-link helper'

# Written into the upload bundle only, never committed to the app, so there is
# no window where a stray copy of it sits in the repository or in a dev build.
$linker = @'
<?php

/*
 * One-time helper for shared hosting, which has no SSH and therefore no way to
 * run `php artisan storage:link`.
 *
 * Put this file in the web root, open it once in a browser, then delete it. It
 * removes itself the moment it has run, and refuses to do anything if it has
 * already run, so a cached copy left lying around cannot re-create the link.
 */

$link = __DIR__.'/storage';
$target = dirname(__DIR__).'/storage/app/public';

$done = function (string $message, bool $ok) {
    echo '<h1>'.($ok ? 'Done' : 'Problem').'</h1><p>'.htmlspecialchars($message).'</p>';
    echo '<p><small>This file deletes itself. You can close this page.</small></p>';
    @unlink(__FILE__);
    exit($ok ? 0 : 1);
};

if (file_exists($link)) {
    $done('The link already exists, so nothing was changed.', true);
}

if (! is_dir($target)) {
    $done('Could not find '.$target.'. Did you upload the storage folder?', false);
}

if (! function_exists('symlink')) {
    $done('This host has disabled symlink(). Ask the host to link public/storage '
        .'to storage/app/public for you, or use a host that allows it.', false);
}

if (! @symlink($target, $link)) {
    $done('The host refused to create the link. Copy '.dirname(__DIR__).'/storage/app/public '
        .'into public/storage instead, and re-upload any images you add later.', false);
}

$done('The link was created. Images in storage/app/public now resolve.', true);
'@

[System.IO.File]::WriteAllText((Join-Path $staging 'public\storage-link.php'), $linker)
Write-Host '    public/storage-link.php (run once, then it deletes itself)'

# ------------------------------------------------------------- write the env ---

Write-Step 'Writing the production .env'

$appKey = (Select-String -Path (Join-Path $root '.env') -Pattern '^APP_KEY=(.+)$').Matches.Groups[1].Value
if (-not $appKey) { Fail 'Could not read APP_KEY from your local .env. It must be copied verbatim.' }

$envLines = @(
    'APP_NAME="Abdullah Ahmed"'
    'APP_ENV=production'
    "APP_KEY=$appKey"
    'APP_DEBUG=false'
    "APP_URL=$AppUrl"
    'APP_LOCALE=en'
    'APP_FALLBACK_LOCALE=en'
    'APP_FAKER_LOCALE=en_US'
    'APP_MAINTENANCE_DRIVER=file'
    ''
    'LOG_CHANNEL=stack'
    'LOG_LEVEL=error'
    ''
    'DB_CONNECTION=sqlite'
    ''
    'SESSION_DRIVER=database'
    'SESSION_LIFETIME=120'
    'SESSION_ENCRYPT=false'
    'SESSION_PATH=/'
    'SESSION_DOMAIN=null'
    ''
    'BROADCAST_CONNECTION=log'
    'FILESYSTEM_DISK=local'
    'QUEUE_CONNECTION=database'
    'CACHE_STORE=database'
    ''
    'MAIL_MAILER=log'
    'MAIL_FROM_ADDRESS="hello@example.com"'
    'MAIL_FROM_NAME="${APP_NAME}"'
    ''
    'VITE_APP_NAME="${APP_NAME}"'
)

# Written as UTF-8 without a BOM: a BOM ahead of the first line breaks PHP.
[System.IO.File]::WriteAllText(
    (Join-Path $staging '.env'),
    ($envLines -join "`n") + "`n"
)

Write-Host "    APP_URL  = $AppUrl"
Write-Host "    APP_ENV  = production, APP_DEBUG=false"

# ------------------------------------------------------------- sanity check ---

Write-Step 'Verifying the staged copy'

$database = Join-Path $staging 'database\database.sqlite'
if (-not (Test-Path $database)) { Fail 'database/database.sqlite is missing from the staging copy.' }
Write-Host ("    database: {0} KB (pre-migrated and seeded)" -f [Math]::Round((Get-Item $database).Length / 1KB))

$media = Get-ChildItem (Join-Path $staging 'storage\app\public') -Recurse -File -ErrorAction SilentlyContinue
if (-not $media) { Fail 'No media found in storage/app/public. The site would deploy with no images.' }
Write-Host ("    media:    {0} files" -f $media.Count)

$missing = Get-ChildItem (Join-Path $staging 'public\build') -Recurse -File -ErrorAction SilentlyContinue
if (-not $missing) { Fail 'public/build is empty in the staging copy.' }
Write-Host ("    assets:   {0} files" -f $missing.Count)

# --------------------------------------------------------------- make a zip ---

Write-Step 'Creating the zip'

New-Item -ItemType Directory -Force -Path $OutputDirectory | Out-Null
if (Test-Path $zip) { Remove-Item -Force $zip }

# The zip is assembled by hand for two reasons that both bite on a Linux host:
#
#  - .NET Framework's ZipFile.CreateFromDirectory writes Windows backslashes as
#    path separators, so 'public\index.php' arrives as one literal filename and
#    the whole site fails to boot. Entries are named explicitly here instead.
#  - public/storage is a symlink to storage/app/public. Following it would ship a
#    second, stale copy of every image, and the storage-link helper would then
#    find the directory already present, do nothing, and leave later admin
#    uploads invisible.
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$skip = @((Join-Path $staging 'public\storage').TrimEnd('\'))

$stream = [System.IO.File]::Open($zip, [System.IO.FileMode]::Create)
$archive = New-Object System.IO.Compression.ZipArchive($stream, [System.IO.Compression.ZipArchiveMode]::Create)

$entryCount = 0
try {
    $files = Get-ChildItem $staging -Recurse -File -Force | Where-Object {
        $full = $_.FullName
        -not ($skip | Where-Object { $full -eq $_ -or $full.StartsWith($_ + '\') })
    }

    foreach ($file in $files) {
        $relative = $file.FullName.Substring($staging.Length + 1).Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $archive,
            $file.FullName,
            $relative,
            [System.IO.Compression.CompressionLevel]::Optimal
        ) | Out-Null
        $entryCount++
    }
} finally {
    $archive.Dispose()
    $stream.Dispose()
}

Write-Host "    $entryCount entries, forward slashes, public/storage excluded"

Remove-Item -Recurse -Force $staging

$size = [Math]::Round((Get-Item $zip).Length / 1MB, 2)
Write-Host ''
Write-Host "Done: $zip ($size MB)" -ForegroundColor Green
Write-Host ''
Write-Host 'Upload the contents of that zip, not the folder itself:' -ForegroundColor Yellow
Write-Host "  - everything goes in your web root (often called public_html or htdocs)"
Write-Host "  - so artisan, app/ and vendor/ sit ALONGSIDE index.php, not inside it"
Write-Host "  - if your host only lets you upload into a subfolder, that folder is your web root"
Write-Host ''
Write-Host 'Still to do by hand, because these need the host:' -ForegroundColor Yellow
Write-Host '  1. Select PHP 8.3 in the host control panel'
Write-Host '  2. Make storage/ and bootstrap/cache/ writable (755, sometimes 775)'
Write-Host '  3. Create the public/storage link (deploy/storage-link.php, then delete it)'
Write-Host '  4. Change the admin password from the default'
