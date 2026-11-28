param(
    [string] $Destination = 'C:/cron-php-zts'
)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
$parallelVersion = '1.2.16'
$downloadRoot = 'https://downloads.php.net/~windows'

$phpVersion = & php.exe -r 'echo PHP_VERSION;'
if ($LASTEXITCODE -ne 0 -or $phpVersion -notmatch '^\d+\.\d+\.\d+$') {
    throw 'Could not determine the version of the base PHP runtime.'
}
$series = '{0}.{1}' -f ([version] $phpVersion).Major, ([version] $phpVersion).Minor
$buildInfo = & php.exe -i | Select-String '^PHP Extension Build => API\d+,(?:NTS|TS),(VS\d+)$'
if ($LASTEXITCODE -ne 0 -or -not $buildInfo) {
    throw 'Could not determine the compiler used by the base PHP runtime.'
}
$compiler = $buildInfo.Matches[0].Groups[1].Value.ToLowerInvariant()

$phpDirectory = (New-Item -ItemType Directory -Force -Path $Destination).FullName
$phpFile = "php-$phpVersion-Win32-$compiler-x64.zip"
$phpArchive = Join-Path $phpDirectory $phpFile
try {
    Invoke-WebRequest -UseBasicParsing "$downloadRoot/releases/$phpFile" -OutFile $phpArchive
} catch {
    Invoke-WebRequest -UseBasicParsing "$downloadRoot/releases/archives/$phpFile" -OutFile $phpArchive
}
Expand-Archive -LiteralPath $phpArchive -DestinationPath $phpDirectory
Remove-Item -LiteralPath $phpArchive

$parallelArchive = Join-Path $phpDirectory 'parallel.zip'
$parallelFile = "php_parallel-$parallelVersion-$series-ts-$compiler-x64.zip"
Invoke-WebRequest -UseBasicParsing "$downloadRoot/pecl/releases/parallel/$parallelVersion/$parallelFile" -OutFile $parallelArchive
Add-Type -AssemblyName System.IO.Compression.FileSystem
$parallelZip = [IO.Compression.ZipFile]::OpenRead($parallelArchive)
try {
    [IO.Compression.ZipFileExtensions]::ExtractToFile($parallelZip.GetEntry('php_parallel.dll'), (Join-Path $phpDirectory 'ext/php_parallel.dll'))
    [IO.Compression.ZipFileExtensions]::ExtractToFile($parallelZip.GetEntry('pthreadVC3.dll'), (Join-Path $phpDirectory 'pthreadVC3.dll'))
} finally {
    $parallelZip.Dispose()
}
Remove-Item -LiteralPath $parallelArchive

$ini = @(
    ('extension_dir="{0}/ext"' -f ($phpDirectory -replace '\\', '/'))
    'extension=curl'
    'extension=fileinfo'
    'extension=mbstring'
    'extension=openssl'
    'extension=sockets'
    'extension=xsl'
    'extension=zip'
)
$ini | Set-Content -Encoding ascii (Join-Path $phpDirectory 'php.ini')
$env:PHPRC = $phpDirectory
$env:PHP_INI_SCAN_DIR = (New-Item -ItemType Directory -Force -Path (Join-Path $phpDirectory 'conf.d')).FullName
'extension=parallel' | Set-Content -Encoding ascii (Join-Path $env:PHP_INI_SCAN_DIR 'parallel.ini')
Invoke-WebRequest -UseBasicParsing 'https://getcomposer.org/download/latest-2.x/composer.phar' -OutFile (Join-Path $phpDirectory 'composer.phar')

& (Join-Path $phpDirectory 'php.exe') --ri parallel
if ($LASTEXITCODE -ne 0) {
    throw 'The native PHP runtime could not load ext-parallel.'
}
