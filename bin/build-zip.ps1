# Build production zip files for Rawnaq (Free) and Rawnaq Pro with normalized forward slashes & strict .distignore filtering
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$pluginsDir = "c:\Users\Manzur\Local Sites\rawnaq\app\public\wp-content\plugins"
$distDir    = Join-Path $pluginsDir "dist"
$desktop    = [Environment]::GetFolderPath('Desktop')

if (-not (Test-Path $distDir)) {
    New-Item -ItemType Directory -Force -Path $distDir | Out-Null
}

function Test-IsIgnored {
    param(
        [string]$RelPath,
        [string[]]$Patterns
    )
    $fileName = Split-Path $RelPath -Leaf
    foreach ($rawPat in $Patterns) {
        $pat = $rawPat.Trim().Replace('\', '/')
        if ([string]::IsNullOrWhiteSpace($pat) -or $pat.StartsWith('#')) {
            continue
        }
        $pat = $pat.TrimStart('/')

        # 1. Exact match on relative path or filename
        if ($RelPath -eq $pat -or $fileName -eq $pat) {
            return $true
        }

        # 2. Directory prefix match (e.g. docs, bin, assets/demo)
        if ($RelPath.StartsWith($pat + '/')) {
            return $true
        }

        # 3. Wildcard / Glob match (e.g. *.md, rawnaq-*-mockup.html, .git*)
        if ($fileName -like $pat -or $RelPath -like $pat -or $RelPath -like "*/$pat" -or $RelPath -like "$pat/*") {
            return $true
        }
    }
    return $false
}

function Create-NormalizedZipArchive {
    param(
        [string]$SourceDir,
        [string]$ZipDestination,
        [string]$RootPrefix,
        [string[]]$Patterns
    )

    if (Test-Path $ZipDestination) {
        Remove-Item -Path $ZipDestination -Force
    }

    $zipStream = [System.IO.File]::Open($ZipDestination, [System.IO.FileMode]::Create)
    $archive = New-Object System.IO.Compression.ZipArchive($zipStream, [System.IO.Compression.ZipArchiveMode]::Create)

    $count = 0
    Get-ChildItem -Path $SourceDir -Recurse | ForEach-Object {
        if (-not $_.PSIsContainer) {
            $rel = $_.FullName.Substring($SourceDir.Length).TrimStart('\', '/')
            $relForward = $rel.Replace('\', '/')
            
            if (-not (Test-IsIgnored -RelPath $relForward -Patterns $Patterns)) {
                $entryName = ($RootPrefix + '/' + $relForward).Replace('\', '/')
                $entry = $archive.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
                $entryStream = $entry.Open()
                $fileStream = [System.IO.File]::OpenRead($_.FullName)
                $fileStream.CopyTo($entryStream)
                $fileStream.Dispose()
                $entryStream.Dispose()
                $count++
            }
        }
    }

    $archive.Dispose()
    $zipStream.Dispose()

    $size = [Math]::Round((Get-Item $ZipDestination).Length / 1KB, 2)
    Write-Host "SUCCESS: Created $ZipDestination ($count files, ${size} KB)"
}

# 1. Build Rawnaq (Free) using .distignore
$freeSource    = Join-Path $pluginsDir "rawnaq"
$freeDistZip   = Join-Path $distDir "rawnaq-1.0.0.zip"
$freeDeskZip   = Join-Path $desktop "rawnaq-1.0.0.zip"
$distignorePath = Join-Path $freeSource ".distignore"

$freePatterns = @()
if (Test-Path $distignorePath) {
    $freePatterns = Get-Content $distignorePath
}
$freePatterns += @('.git*', '*.zip', 'dist*', 'tmp_*')

Create-NormalizedZipArchive -SourceDir $freeSource -ZipDestination $freeDistZip -RootPrefix 'rawnaq' -Patterns $freePatterns
Copy-Item -Path $freeDistZip -Destination $freeDeskZip -Force

# 2. Build Rawnaq Pro
$proSource   = Join-Path $pluginsDir "rawnaq-pro"
$proDistZip  = Join-Path $distDir "rawnaq-pro-1.0.0.zip"
$proDeskZip  = Join-Path $desktop "rawnaq-pro.zip"
$proPatterns = @('.git*', 'node_modules*', '.DS_Store', 'Thumbs.db', '*.log', '*.zip', 'dist*')

Create-NormalizedZipArchive -SourceDir $proSource -ZipDestination $proDistZip -RootPrefix 'rawnaq-pro' -Patterns $proPatterns
Copy-Item -Path $proDistZip -Destination $proDeskZip -Force
