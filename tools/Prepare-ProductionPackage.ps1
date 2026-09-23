param(
    [string] $OutputDir = "build\production",
    [switch] $IncludeEnv
)

$ErrorActionPreference = 'Stop'

$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$output = Join-Path $root $OutputDir
$privateStage = Join-Path $output 'private'
$publicStage = Join-Path $output 'public'

if (Test-Path $output) {
    Remove-Item -LiteralPath $output -Recurse -Force
}

New-Item -ItemType Directory -Force -Path $privateStage, $publicStage | Out-Null

$privateItems = @(
    'app',
    'system',
    'vendor',
    'writable',
    'composer.json',
    'composer.lock',
    'preload.php',
    'spark',
    'LICENSE',
    '.htaccess'
)

foreach ($item in $privateItems) {
    $source = Join-Path $root $item
    if (Test-Path $source) {
        Copy-Item -LiteralPath $source -Destination $privateStage -Recurse -Force
    }
}

if ($IncludeEnv) {
    Copy-Item -LiteralPath (Join-Path $root '.env.servidor') -Destination (Join-Path $privateStage '.env') -Force
} else {
    Copy-Item -LiteralPath (Join-Path $root '.env.production.example') -Destination (Join-Path $privateStage '.env.production.example') -Force
}

$publicSource = Join-Path $root 'public'
Get-ChildItem -LiteralPath $publicSource -Force | ForEach-Object {
    Copy-Item -LiteralPath $_.FullName -Destination $publicStage -Recurse -Force
}

$cleanupDirs = @(
    'writable\debugbar',
    'writable\logs',
    'writable\session',
    'writable\cache'
)

foreach ($dir in $cleanupDirs) {
    $full = Join-Path $privateStage $dir
    if (Test-Path $full) {
        Get-ChildItem -LiteralPath $full -Force | Where-Object { $_.Name -ne 'index.html' } | Remove-Item -Recurse -Force
    }
}

$privateZip = Join-Path $output 'SIS_MASTER_HDC_private.zip'
$publicZip = Join-Path $output 'master_hdc_public.zip'

$tar = Get-Command tar.exe -ErrorAction SilentlyContinue

if ($null -eq $tar) {
    throw 'tar.exe is required to create deployment ZIP files.'
}

& $tar.Source -a -cf $privateZip -C $privateStage .
if ($LASTEXITCODE -ne 0) {
    throw "Could not create private package: $privateZip"
}

& $tar.Source -a -cf $publicZip -C $publicStage .
if ($LASTEXITCODE -ne 0) {
    throw "Could not create public package: $publicZip"
}

Write-Host "Private package: $privateZip"
Write-Host "Public package:  $publicZip"
Write-Host "Upload private contents to /home/cho66450/SIS_MASTER_HDC/"
Write-Host "Upload public contents to /home/cho66450/public_html/webapp/master_hdc/"
