# Build production zip files for Rawnaq (Free) and Rawnaq Pro with normalized forward slashes
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$pluginsDir = "c:\Users\Manzur\Local Sites\rawnaq\app\public\wp-content\plugins"
$distDir    = Join-Path $pluginsDir "dist"
$desktop    = [Environment]::GetFolderPath('Desktop')

if (-not (Test-Path $distDir)) {
    New-Item -ItemType Directory -Force -Path $distDir | Out-Null
}

function Create-NormalizedZipArchive {
    param(
        [string]$SourceDir,
        [string]$ZipDestination,
        [string]$RootPrefix,
        [string[]]$ExcludePatterns
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
            
            $skip = $false
            foreach ($pat in $ExcludePatterns) {
                if ($relForward -like $pat -or $relForward -like "*/$pat*" -or $relForward -like "$pat/*" -or (Split-Path $relForward -Leaf) -like $pat) {
                    $skip = $true
                    break
                }
            }

            if (-not $skip) {
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

# 1. Build Rawnaq (Free)
$freeSource   = Join-Path $pluginsDir "rawnaq"
$freeDistZip  = Join-Path $distDir "rawnaq-1.0.0.zip"
$freeDeskZip  = Join-Path $desktop "rawnaq-1.0.0.zip"
$freeExcludes = @('.git*', 'node_modules*', 'package*.json', 'webpack.config.js', '.DS_Store', 'Thumbs.db', '*.log', 'tests*', 'phpunit*', '*.zip', 'docs*', 'bin*', 'rawnaq-*-mockup.html', 'assets/demo*', 'dist*')

Create-NormalizedZipArchive -SourceDir $freeSource -ZipDestination $freeDistZip -RootPrefix 'rawnaq' -ExcludePatterns $freeExcludes
Copy-Item -Path $freeDistZip -Destination $freeDeskZip -Force

# 2. Build Rawnaq Pro
$proSource   = Join-Path $pluginsDir "rawnaq-pro"
$proDistZip  = Join-Path $distDir "rawnaq-pro-1.0.0.zip"
$proDeskZip  = Join-Path $desktop "rawnaq-pro.zip"
$proExcludes = @('.git*', 'node_modules*', '.DS_Store', 'Thumbs.db', '*.log', '*.zip', 'dist*')

Create-NormalizedZipArchive -SourceDir $proSource -ZipDestination $proDistZip -RootPrefix 'rawnaq-pro' -ExcludePatterns $proExcludes
Copy-Item -Path $proDistZip -Destination $proDeskZip -Force
