param(
    [Parameter(Mandatory = $true)]
    [string] $InputSql,

    [string] $OutputSql
)

$ErrorActionPreference = 'Stop'

$source = (Resolve-Path $InputSql).Path
if (-not $OutputSql) {
    $dir = Split-Path $source -Parent
    $name = [IO.Path]::GetFileNameWithoutExtension($source)
    $OutputSql = Join-Path $dir "$name.hosting.sql"
}

$content = Get-Content -LiteralPath $source -Raw

$content = $content `
    -replace 'utf8mb4_0900_ai_ci', 'utf8mb4_general_ci' `
    -replace 'utf8mb4_0900_as_ci', 'utf8mb4_general_ci' `
    -replace 'utf8mb4_0900_bin', 'utf8mb4_bin' `
    -replace 'CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci', 'CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'

Set-Content -LiteralPath $OutputSql -Value $content -Encoding UTF8

Write-Host "SQL compatible creado en: $OutputSql"
