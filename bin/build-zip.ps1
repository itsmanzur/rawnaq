# Build production zip files for Rawnaq (Free) and Rawnaq Pro
$pluginsDir = "c:\Users\Manzur\Local Sites\rawnaq\app\public\wp-content\plugins"
$distDir    = Join-Path $pluginsDir "dist"

if (Test-Path $distDir) {
    Remove-Item -Recurse -Force $distDir
}
New-Item -ItemType Directory -Force -Path $distDir | Out-Null

# 1. Build Rawnaq (Free)
$freeSource = Join-Path $pluginsDir "rawnaq"
$freeStage  = Join-Path $distDir "rawnaq"
New-Item -ItemType Directory -Force -Path $freeStage | Out-Null

Get-ChildItem -Path $freeSource -Recurse | ForEach-Object {
    $rel = $_.FullName.Substring($freeSource.Length + 1)
    if ($rel -match '^\.git' -or $rel -match '^\.gitignore' -or $rel -match '^\.distignore' -or $rel -match '\.md$' -or $rel -match '^docs' -or $rel -match '^bin' -or $rel -match '^rawnaq-.*-mockup\.html' -or $rel -match '^assets[\\/]demo' -or $rel -match '^dist') {
        return
    }
    $target = Join-Path $freeStage $rel
    if ($_.PSIsContainer) {
        if (-not (Test-Path $target)) {
            New-Item -ItemType Directory -Force -Path $target | Out-Null
        }
    } else {
        $parent = Split-Path $target -Parent
        if (-not (Test-Path $parent)) {
            New-Item -ItemType Directory -Force -Path $parent | Out-Null
        }
        Copy-Item -Path $_.FullName -Destination $target -Force
    }
}

$freeZip = Join-Path $distDir "rawnaq-1.0.0.zip"
Compress-Archive -Path $freeStage -DestinationPath $freeZip -Force
Remove-Item -Recurse -Force $freeStage
Write-Host "SUCCESS: Free plugin zip created at $freeZip"

# 2. Build Rawnaq Pro
$proSource = Join-Path $pluginsDir "rawnaq-pro"
$proStage  = Join-Path $distDir "rawnaq-pro"
New-Item -ItemType Directory -Force -Path $proStage | Out-Null

Get-ChildItem -Path $proSource -Recurse | ForEach-Object {
    $rel = $_.FullName.Substring($proSource.Length + 1)
    if ($rel -match '^\.git' -or $rel -match '^\.gitignore' -or $rel -match '^dist') {
        return
    }
    $target = Join-Path $proStage $rel
    if ($_.PSIsContainer) {
        if (-not (Test-Path $target)) {
            New-Item -ItemType Directory -Force -Path $target | Out-Null
        }
    } else {
        $parent = Split-Path $target -Parent
        if (-not (Test-Path $parent)) {
            New-Item -ItemType Directory -Force -Path $parent | Out-Null
        }
        Copy-Item -Path $_.FullName -Destination $target -Force
    }
}

$proZip = Join-Path $distDir "rawnaq-pro-1.0.0.zip"
Compress-Archive -Path $proStage -DestinationPath $proZip -Force
Remove-Item -Recurse -Force $proStage
Write-Host "SUCCESS: Pro plugin zip created at $proZip"
