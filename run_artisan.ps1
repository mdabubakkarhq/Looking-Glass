$phpExe = 'C:\php-8.3\php.exe'
$projectDir = 'c:\Users\HostOrient\Documents\GitHub\Looking-Glass\controller'
Set-Location $projectDir

Write-Host "=== package:discover ==="
& $phpExe artisan package:discover 2>&1 | Out-String | Write-Host

Write-Host "=== key:generate ==="
& $phpExe artisan key:generate 2>&1 | Out-String | Write-Host

Write-Host "=== route:list ==="
& $phpExe artisan route:list 2>&1 | Out-String | Write-Host

Write-Host "DONE"
