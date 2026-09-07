$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$toolDirectory = Join-Path $projectRoot '.tools'
$phpunitVersion = '12.5.0'
$archiveName = "phpunit-$phpunitVersion.phar"
$archivePath = Join-Path $toolDirectory $archiveName
# Published at https://phar.phpunit.de/ for this exact release.
$expected = '5d9c62d64bb0a9220c2a046e9aa1c382bb474204c44b8c2ebe5d9959cba82d9f'
New-Item -ItemType Directory -Force -Path $toolDirectory | Out-Null

if (-not (Test-Path -LiteralPath $archivePath)) {
    Invoke-WebRequest -UseBasicParsing -Uri "https://phar.phpunit.de/$archiveName" -OutFile $archivePath
}
$actual = (Get-FileHash -Algorithm SHA256 -LiteralPath $archivePath).Hash
if ($expected -notmatch '^[a-fA-F0-9]{64}$' -or $actual -ine $expected) {
    throw 'PHPUnit SHA256 verification failed. Check the cached archive in .tools.'
}

Push-Location $projectRoot
try {
    docker compose cp $archivePath "wordpress:/tmp/$archiveName"
    if ($LASTEXITCODE -ne 0) { throw 'Cannot copy PHPUnit into the running WordPress container.' }
    docker compose exec -T wordpress php /var/www/html/wp-content/plugins/love-fortune-core/tests/lint.php
    if ($LASTEXITCODE -ne 0) { throw 'PHP lint failed.' }
    docker compose exec -T wordpress php "/tmp/$archiveName" --configuration /var/www/html/wp-content/plugins/love-fortune-core/phpunit.xml.dist
    if ($LASTEXITCODE -ne 0) { throw 'PHPUnit failed.' }
} finally {
    Pop-Location
}
