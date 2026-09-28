$ErrorActionPreference = 'Stop'

$logPath = Join-Path $PSScriptRoot '..\storage\logs\service-install.log'
Start-Transcript -Path $logPath -Force

try {
    $apacheService = Get-Service -Name 'Apache2.4' -ErrorAction SilentlyContinue
    if (-not $apacheService) {
        & 'C:\xampp\apache\bin\httpd.exe' -k install -n 'Apache2.4'
        if ($LASTEXITCODE -ne 0) {
            throw "No se pudo instalar Apache2.4 (codigo $LASTEXITCODE)."
        }
    }

    $mysqlService = Get-Service -Name 'mysql' -ErrorAction SilentlyContinue
    if (-not $mysqlService) {
        & 'C:\xampp\mysql\bin\mysqld.exe' --install mysql --defaults-file=C:\xampp\mysql\bin\my.ini
        if ($LASTEXITCODE -ne 0) {
            throw "No se pudo instalar MySQL (codigo $LASTEXITCODE)."
        }
    }

    Set-Service -Name 'mysql' -StartupType Automatic
    Set-Service -Name 'Apache2.4' -StartupType Automatic

    Start-Service -Name 'mysql'
    Start-Service -Name 'Apache2.4'

    Get-Service -Name 'Apache2.4', 'mysql' |
        Select-Object Name, Status, StartType |
        Format-Table -AutoSize
} finally {
    Stop-Transcript
}
