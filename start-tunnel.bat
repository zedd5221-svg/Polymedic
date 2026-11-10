@echo off
setlocal EnableExtensions
title PolyMedic - Cloudflare Tunnel

rem ============================================================
rem  Starts Apache + MySQL (if needed) and a Cloudflare quick
rem  tunnel, then opens the public PolyMedic link in the browser.
rem  Press any key in this window to stop the tunnel.
rem ============================================================

set "APP_PATH=/polymedic/public/"
for %%I in ("%~dp0..\..") do set "XAMPP=%%~fI"
set "LOG=%TEMP%\polymedic-tunnel.log"

rem ---------- find cloudflared ----------
set "CF="
if exist "%~dp0tools\cloudflared.exe" set "CF=%~dp0tools\cloudflared.exe"
if not defined CF for %%X in (cloudflared.exe) do if not "%%~$PATH:X"=="" set "CF=%%~$PATH:X"
if not defined CF if exist "D:\Downloads\cloudflared-windows-amd64.exe" set "CF=D:\Downloads\cloudflared-windows-amd64.exe"
if not defined CF if exist "D:\projects\roblox\cloudflared-windows-amd64.exe" set "CF=D:\projects\roblox\cloudflared-windows-amd64.exe"
if not defined CF (
    echo [X] cloudflared was not found.
    echo     Put cloudflared.exe in "%~dp0tools\" and run this again.
    goto :fail
)

rem ---------- Apache ----------
call :isListening 80
if errorlevel 1 (
    echo [..] Starting Apache...
    start "Apache (PolyMedic)" /min /d "%XAMPP%" "%XAMPP%\apache\bin\httpd.exe"
    call :waitPort 80 Apache || goto :fail
) else (
    echo [OK] Apache is running.
)

rem ---------- MySQL ----------
call :isListening 3306
if errorlevel 1 (
    echo [..] Starting MySQL...
    start "MySQL (PolyMedic)" /min /d "%XAMPP%" "%XAMPP%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP%\mysql\bin\my.ini" --standalone
    call :waitPort 3306 MySQL || goto :fail
) else (
    echo [OK] MySQL is running.
)

rem ---------- tunnel ----------
echo [..] Starting Cloudflare tunnel...
if exist "%LOG%" del "%LOG%"
set "CFPID="
for /f %%P in ('powershell -NoProfile -Command "(Start-Process -FilePath $env:CF -ArgumentList 'tunnel','--no-autoupdate','--url','http://localhost:80','--logfile',('\"' + $env:LOG + '\"') -WindowStyle Minimized -PassThru).Id"') do set "CFPID=%%P"
if not defined CFPID (
    echo [X] Could not start cloudflared.
    goto :fail
)

set "URL="
for /f "usebackq delims=" %%U in (`powershell -NoProfile -Command "$end=(Get-Date).AddSeconds(60); while((Get-Date) -lt $end){ if(Test-Path $env:LOG){ $m=Select-String -Path $env:LOG -Pattern 'https://[a-z0-9-]+\.trycloudflare\.com' | Select-Object -First 1; if($m){ $m.Matches[0].Value; exit } }; Start-Sleep -Milliseconds 500 }"`) do set "URL=%%U"
if not defined URL (
    echo [X] The tunnel did not give a link within 60 seconds. Check the internet connection.
    taskkill /PID %CFPID% /F >nul 2>&1
    goto :fail
)
set "SITE=%URL%%APP_PATH%"

rem A brand-new tunnel link takes a few seconds to exist in DNS. Check it through
rem Cloudflare's DNS (1.1.1.1) only: asking the router too early makes it cache
rem "not found" and the link then fails on every device on this Wi-Fi for minutes.
echo [..] Waiting for the link to come online...
powershell -NoProfile -Command "$h=([uri]$env:SITE).Host; $end=(Get-Date).AddSeconds(90); while((Get-Date) -lt $end){ try { $ip=(Resolve-DnsName $h -Server 1.1.1.1 -Type A -DnsOnly -ErrorAction Stop | Where-Object IPAddress | Select-Object -First 1).IPAddress; if($ip){ $c = & curl.exe -s -o NUL -w '%%{http_code}' --max-time 10 --resolve ($h + ':443:' + $ip) $env:SITE; if($c -eq '200'){ exit 0 } } } catch {}; Start-Sleep -Seconds 2 }; exit 1"
if errorlevel 1 echo [!] The link is not answering yet. Wait a minute, then refresh the page.
ipconfig /flushdns >nul 2>&1

<nul set /p "=%SITE%" | clip
start "" "%SITE%"

echo.
echo  ============================================================
echo    PolyMedic is online:
echo.
echo    %SITE%
echo.
echo    (copied to clipboard - open it on any device)
echo  ============================================================
echo.
echo  Keep this window open during the presentation.
echo  Press any key here to STOP the tunnel.
pause >nul

taskkill /PID %CFPID% /F >nul 2>&1
echo Tunnel stopped. Apache and MySQL are still running.
ping -n 4 127.0.0.1 >nul
exit /b 0

rem ============================================================
:isListening
netstat -ano | findstr /c:":%1 " | findstr /c:"LISTENING" >nul
exit /b %errorlevel%

:waitPort
for /l %%N in (1,1,20) do (
    call :isListening %1 && (echo [OK] %2 is running.& exit /b 0)
    ping -n 2 127.0.0.1 >nul
)
echo [X] %2 did not start. Start it from the XAMPP Control Panel and try again.
exit /b 1

:fail
echo.
pause
exit /b 1
