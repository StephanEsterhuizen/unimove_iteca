# =====================================================================
#  Prep a production-ready zip of UniMove Res Essentials for upload
#  to InfinityFree. Removes sensitive / dev-only files.
# =====================================================================

$ErrorActionPreference = 'Stop'
$root  = Split-Path -Parent $PSScriptRoot
$src   = Join-Path $root 'unimove'
$stage = Join-Path $env:TEMP 'unimove-deploy'
$out   = Join-Path $root 'unimove-deploy.zip'

Write-Host "Cleaning staging area..."
if (Test-Path $stage) { Remove-Item -Recurse -Force $stage }
New-Item -ItemType Directory -Path $stage | Out-Null

Write-Host "Copying source..."
Copy-Item -Recurse "$src\*" $stage

# --- Files we DO NOT want on the live server ---
$forbidden = @(
    '_dropzone',
    'uploads\mail.log'
)

foreach ($f in $forbidden) {
    $target = Join-Path $stage $f
    if (Test-Path $target) {
        Write-Host "Removing $f"
        Remove-Item -Recurse -Force $target
    }
}

if (Test-Path $out) { Remove-Item $out }
Write-Host "Zipping to $out..."
Compress-Archive -Path "$stage\*" -DestinationPath $out -CompressionLevel Optimal

$size = (Get-Item $out).Length / 1KB
Write-Host ""
Write-Host "Done: $out  ($([math]::Round($size, 1)) KB)"
