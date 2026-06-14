# =====================================================================
#  Recursively upload the unimove/ folder to InfinityFree via FTP.
#  Creates directories as needed; overwrites existing files.
# =====================================================================

$ErrorActionPreference = 'Stop'

$ftpHost   = 'ftp://ftpupload.net'
$ftpUser   = 'if0_42090921'
$ftpPass   = 'StephanUNIMOVE2'
$ftpRemote = '/htdocs'

$root      = Split-Path -Parent $PSScriptRoot
$src       = Join-Path $root 'unimove'

# Files we don't want on the server
$skip = @(
    '_dropzone',
    'uploads\mail.log'
)

function Test-Skip([string]$relPath) {
    foreach ($s in $skip) {
        if ($relPath -like "*$s*") { return $true }
    }
    return $false
}

function New-FtpDirectory([string]$remotePath) {
    $uri = "$ftpHost$remotePath"
    try {
        $req = [System.Net.FtpWebRequest]::Create($uri)
        $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $req.Method      = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $req.UsePassive  = $true
        $resp = $req.GetResponse()
        $resp.Close()
        Write-Host "  + mkdir $remotePath"
    } catch [System.Net.WebException] {
        # 550 = already exists, ignore
        if ($_.Exception.Message -notmatch '550') { throw }
    }
}

function Send-FtpFile([string]$localFile, [string]$remotePath) {
    $uri = "$ftpHost$remotePath"
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $req.Method      = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $req.UseBinary   = $true
    $req.UsePassive  = $true
    $req.KeepAlive   = $false

    $bytes = [System.IO.File]::ReadAllBytes($localFile)
    $req.ContentLength = $bytes.Length
    $stream = $req.GetRequestStream()
    $stream.Write($bytes, 0, $bytes.Length)
    $stream.Close()
    $resp = $req.GetResponse()
    $resp.Close()
}

# Walk all files
$total = 0
$dirs  = @{}

Get-ChildItem -Path $src -Recurse -File | ForEach-Object {
    $relPath = $_.FullName.Substring($src.Length + 1).Replace('\', '/')
    if (Test-Skip $relPath) {
        Write-Host "  - skip $relPath"
        return
    }

    # Create parent directories if needed
    $remoteDir = "$ftpRemote/$(Split-Path $relPath -Parent)".Replace('\', '/').TrimEnd('/')
    if ($remoteDir -ne $ftpRemote -and -not $dirs.ContainsKey($remoteDir)) {
        # Build up directory path piece by piece
        $parts = Split-Path $relPath -Parent
        $parts = $parts -split '\\' | Where-Object { $_ }
        $cur   = $ftpRemote
        foreach ($p in $parts) {
            $cur = "$cur/$p"
            if (-not $dirs.ContainsKey($cur)) {
                New-FtpDirectory $cur
                $dirs[$cur] = $true
            }
        }
    }

    $remotePath = "$ftpRemote/$relPath"
    Write-Host "  > upload $relPath  ($($_.Length) bytes)"
    Send-FtpFile $_.FullName $remotePath
    $total++
}

Write-Host ""
Write-Host "Done: $total files uploaded to $ftpHost$ftpRemote/"
