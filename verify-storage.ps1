param([string]$env = "production")

Write-Host "== Disk config ==" -ForegroundColor Cyan
cloud tinker $env --% --code="echo json_encode(config('filesystems.disks.s3'), JSON_PRETTY_PRINT);" -n

Write-Host "`n== Write/read round trip ==" -ForegroundColor Cyan
cloud tinker $env --% --code="try { Storage::disk('s3')->put('healthcheck.txt', 'ok-' . now()); echo Storage::disk('s3')->get('healthcheck.txt'); } catch (\Throwable `$e) { echo get_class(`$e).': '.`$e->getMessage(); }" -n

Write-Host "`n== Media disk resolution ==" -ForegroundColor Cyan
cloud tinker $env --% --code="echo config('media-library.disk_name');" -n
