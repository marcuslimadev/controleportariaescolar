param(
  [string]$HostName = '',
  [int]$Port = 22,
  [string]$User = '',
  [string]$RemotePath = '',
  [string]$Message = 'deploy: atualizar SCP Escolar',
  [switch]$SkipGit
)
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$envFile = Join-Path $PSScriptRoot '.env'
if (Test-Path $envFile) {
  foreach ($line in Get-Content $envFile) {
    $line = $line.Trim()
    if (-not $line -or $line.StartsWith('#') -or -not $line.Contains('=')) { continue }
    $key, $value = $line.Split('=', 2)
    [Environment]::SetEnvironmentVariable($key.Trim(), $value.Trim().Trim('"').Trim("'"), 'Process')
  }
}
if ($env:SCP_SSH_HOST) { $HostName = $env:SCP_SSH_HOST }
if ($env:SCP_SSH_PORT) { $Port = [int]$env:SCP_SSH_PORT }
if ($env:SCP_SSH_USER) { $User = $env:SCP_SSH_USER }
if ($env:SCP_REMOTE_PATH) { $RemotePath = $env:SCP_REMOTE_PATH }
$BaseUrl = $env:SCP_BASE_URL
if (-not $HostName) { throw 'Defina SCP_SSH_HOST no arquivo .env local ou passe -HostName.' }
if (-not $User) { throw 'Defina SCP_SSH_USER no arquivo .env local ou passe -User.' }
if (-not $RemotePath) { throw 'Defina SCP_REMOTE_PATH no arquivo .env local ou passe -RemotePath.' }
if (-not $BaseUrl) { throw 'Defina SCP_BASE_URL no arquivo .env local.' }
if (-not $SkipGit) {
  if (-not (Test-Path .repo)) { git --git-dir=.repo --work-tree=. init }
  git --git-dir=.repo --work-tree=. add -A
  if (git --git-dir=.repo --work-tree=. diff --cached --quiet) { Write-Host 'Sem alterações para commit.' } else { git --git-dir=.repo --work-tree=. commit -m $Message }
  if (git --git-dir=.repo --work-tree=. remote get-url origin 2>$null) { git --git-dir=.repo --work-tree=. push origin HEAD }
}
php scripts/ci_check.php
if ($LASTEXITCODE) { throw 'Falha no CI local.' }
php scripts/unit_check.php
if ($LASTEXITCODE) { throw 'Falha nos testes unitários locais.' }
$password = $env:SCP_SSH_PASSWORD
if (-not $password) { throw 'Defina SCP_SSH_PASSWORD no arquivo .env local.' }
$stage = Join-Path $PSScriptRoot ('deploy-stage-' + [guid]::NewGuid())
$archive = "$stage.tar.gz"
New-Item -ItemType Directory -Path $stage | Out-Null
try {
  Copy-Item public,config,includes,database,scripts,app -Destination $stage -Recurse
  if (Test-Path composer.json) { Copy-Item composer.json "$stage/composer.json" -Force }
  if (Test-Path config/config.php) { Copy-Item config/config.php "$stage/config/config.php" -Force }
  tar -czf $archive -C $stage .
  $pi = [Diagnostics.ProcessStartInfo]::new()
  $pi.FileName = (Get-Command plink).Source
  $pi.UseShellExecute = $false
  $pi.RedirectStandardInput = $true
  $pi.ArgumentList.Add('-batch'); $pi.ArgumentList.Add('-P'); $pi.ArgumentList.Add("$Port")
  $pi.ArgumentList.Add('-l'); $pi.ArgumentList.Add($User); $pi.ArgumentList.Add('-pw'); $pi.ArgumentList.Add($password)
  $pi.ArgumentList.Add($HostName); $pi.ArgumentList.Add('cat > /tmp/scp-deploy.tar.gz')
  $proc = [Diagnostics.Process]::Start($pi)
  $stream = [IO.File]::OpenRead($archive)
  $stream.CopyTo($proc.StandardInput.BaseStream); $stream.Dispose(); $proc.StandardInput.Close(); $proc.WaitForExit()
  if ($proc.ExitCode) { throw 'Falha no upload.' }
  $cmd = "if [ -f '$RemotePath/config/config.php' ]; then cp '$RemotePath/config/config.php' /tmp/scp-config.php; fi && mkdir -p '$RemotePath/public_html' && find '$RemotePath/public_html' -mindepth 1 -maxdepth 1 -exec rm -rf {} + && rm -rf '$RemotePath/includes' '$RemotePath/database' '$RemotePath/scripts' '$RemotePath/config' && tar -xzf /tmp/scp-deploy.tar.gz -C '$RemotePath' && if [ -f /tmp/scp-config.php ]; then mv /tmp/scp-config.php '$RemotePath/config/config.php'; fi && cd '$RemotePath' && php scripts/migrate.php && cp -a '$RemotePath/public/.' '$RemotePath/public_html/' && rm -rf '$RemotePath/public' /tmp/scp-deploy.tar.gz && chmod -R u=rwX,go=rX '$RemotePath'"
  & plink -batch -P $Port -l $User -pw $password $HostName $cmd
  if ($LASTEXITCODE) { throw 'Falha na publicação remota.' }
  php scripts/http_smoke.php $BaseUrl
  Write-Host "Deploy concluído: $BaseUrl"
} finally {
  Remove-Item -LiteralPath $stage -Recurse -Force -ErrorAction SilentlyContinue
  Remove-Item -LiteralPath $archive -Force -ErrorAction SilentlyContinue
}
