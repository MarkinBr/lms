@echo off
chcp 65001 >nul
color 0A
title GERENCIADOR CETS - XAMPP + Laravel

:: ===== CONFIGURACOES DO SISTEMA =====
set "PROJETO_PATH=C:\xampp\htdocs\cets"
set "XAMPP_PATH=C:\xampp"
set "NGROK_URL=https://incontrovertible-marlys-bemoaningly.ngrok-free.dev"
set "VERSION=v3.0 Pro"
set "LOG_PATH=%PROJETO_PATH%\logs\gerenciador"
set "BACKUP_PATH=%PROJETO_PATH%\backups"

:: Criar diretorios necessarios
if not exist "%LOG_PATH%" mkdir "%LOG_PATH%" >nul 2>&1
if not exist "%BACKUP_PATH%" mkdir "%BACKUP_PATH%" >nul 2>&1

:MENU
cls
echo.
echo    ================================================================
echo    ==                                                            ==
echo    ==              GERENCIADOR DO SISTEMA CETS                   ==
echo    ==                     %VERSION%                              ==
echo    ==                                                            ==
echo    ================================================================
echo.
echo    ----------------------------------------------------------------
echo      + CONTROLE PRINCIPAL
echo    ----------------------------------------------------------------
echo      [1]  INICIAR TUDO
echo      [2]  PARAR TUDO
echo      [3]  REINICIAR TUDO
echo      [4]  STATUS DO SISTEMA
echo    ----------------------------------------------------------------
echo      + FERRAMENTAS
echo    ----------------------------------------------------------------
echo      [5]  ABRIR CONSOLE
echo      [6]  ABRIR LINKS
echo      [7]  LIMPAR CACHE LARAVEL
echo      [8]  EXECUTAR MIGRATIONS
echo      [9]  EXECUTAR SEEDERS
echo    ----------------------------------------------------------------
echo      + MANUTENCAO
echo    ----------------------------------------------------------------
echo      [A]  BACKUP DO BANCO DE DADOS
echo      [B]  VER LOGS DO SISTEMA
echo      [C]  VERIFICAR COMPOSER/NPM
echo      [D]  LIMPAR LOGS ANTIGOS
echo      [E]  MODO MANUTENCAO ON/OFF
echo    ----------------------------------------------------------------
echo      + INFORMACOES
echo    ----------------------------------------------------------------
echo      [F]  INFORMACOES DO SISTEMA
echo      [G]  COPIAR NGROK URL
echo      [H]  CONFIGURACOES
echo    ----------------------------------------------------------------
echo      [0]  SAIR
echo    ----------------------------------------------------------------
echo.
set /p opcao="    ^> Escolha uma opcao: "

if /i "%opcao%"=="1" goto INICIAR
if /i "%opcao%"=="2" goto PARAR
if /i "%opcao%"=="3" goto REINICIAR
if /i "%opcao%"=="4" goto STATUS
if /i "%opcao%"=="5" goto CONSOLE
if /i "%opcao%"=="6" goto LINKS
if /i "%opcao%"=="7" goto CACHE
if /i "%opcao%"=="8" goto MIGRATIONS
if /i "%opcao%"=="9" goto SEEDERS
if /i "%opcao%"=="A" goto BACKUP
if /i "%opcao%"=="B" goto LOGS
if /i "%opcao%"=="C" goto VERIFICAR
if /i "%opcao%"=="D" goto LIMPAR_LOGS
if /i "%opcao%"=="E" goto MANUTENCAO
if /i "%opcao%"=="F" goto INFO
if /i "%opcao%"=="G" goto COPIAR_URL
if /i "%opcao%"=="H" goto CONFIG
if /i "%opcao%"=="0" goto SAIR
goto MENU

:: ================================================================
::  OPCAO 1: INICIAR O SISTEMA
:: ================================================================
:INICIAR
cls
call :LOG "INICIANDO SISTEMA"
echo.
echo    ================================================================
echo    ==         INICIANDO SISTEMA CETS                            ==
echo    ================================================================
echo.

:: Verificar se ja esta rodando
tasklist /FI "IMAGENAME eq php.exe" 2>NUL | find /I /N "php.exe" >NUL
if "%ERRORLEVEL%"=="0" (
    echo    [!] ATENCAO: Sistema ja esta rodando!
    echo.
    choice /C SN /M "    Deseja REINICIAR tudo"
    if errorlevel 2 goto MENU
    call :PARAR_SILENCIOSO
)

echo    [1/8] Iniciando MySQL e Apache (XAMPP)...
start /min "" "%XAMPP_PATH%\mysql_start.bat"
start /min "" "%XAMPP_PATH%\apache_start.bat"
call :PROGRESS_BAR 5
echo          [OK] Banco de dados online
call :LOG "MySQL e Apache iniciados"
echo.

cd /d "%PROJETO_PATH%"

echo    [2/8] Limpando caches do Laravel...
php artisan config:clear >nul 2>&1
php artisan cache:clear >nul 2>&1
php artisan view:clear >nul 2>&1
php artisan route:clear >nul 2>&1
echo          [OK] Cache limpo
call :LOG "Cache Laravel limpo"
echo.

echo    [3/8] Verificando permissoes...
if exist "%PROJETO_PATH%\storage" (
    echo          [OK] Diretorio storage OK
) else (
    echo          [!] Criando diretorio storage...
    mkdir "%PROJETO_PATH%\storage" >nul 2>&1
)
call :LOG "Permissoes verificadas"
echo.

echo    [4/8] Gerando chave da aplicacao (se necessario)...
php artisan key:generate --show >nul 2>&1
echo          [OK] Chave da aplicacao OK
echo.

echo    [5/8] Iniciando Servidor Web (artisan serve)...
start "CETS - Servidor Web" cmd /c "title CETS - Servidor Web & color 0B & php artisan serve --host 0.0.0.0 --port 8000"
timeout /t 2 >nul
echo          [OK] Servidor rodando em http://localhost:8000
call :LOG "Servidor Web iniciado"
echo.

echo    [6/8] Iniciando Processador de Fila (queue:work)...
start "CETS - Fila de Jobs" cmd /c "title CETS - Fila de Jobs & color 0E & php artisan queue:work --tries=3"
timeout /t 2 >nul
echo          [OK] Fila processando emails e PIX
call :LOG "Fila de jobs iniciada"
echo.

echo    [7/8] Iniciando Ngrok (Webhooks)...
start "CETS - Ngrok" cmd /c "title CETS - Ngrok & color 0D & ngrok http 8000"
timeout /t 3 >nul
echo          [OK] Ngrok ativo
call :LOG "Ngrok iniciado"
echo.

echo    [8/8] Abrindo Navegador...
timeout /t 2 >nul
start "" "http://localhost:8000"
start "" "%NGROK_URL%/admin"
start "" "http://localhost/phpmyadmin"
start "" "http://localhost:4040"
echo          [OK] Navegador aberto
call :LOG "Sistema iniciado completamente"
echo.

call :NOTIFICAR "Sistema CETS iniciado com sucesso!"

echo    ================================================================
echo    ==                                                            ==
echo    ==       [OK] SISTEMA INICIADO COM SUCESSO!                   ==
echo    ==                                                            ==
echo    ==  Site Local:    http://localhost:8000                      ==
echo    ==  Ngrok:         %NGROK_URL%                                ==
echo    ==  phpMyAdmin:    http://localhost/phpmyadmin                ==
echo    ==  Ngrok Panel:   http://localhost:4040                      ==
echo    ==                                                            ==
echo    ================================================================
echo.
pause
goto MENU

:: ================================================================
::  OPCAO 2: PARAR O SISTEMA
:: ================================================================
:PARAR
cls
call :LOG "PARANDO SISTEMA"
echo.
echo    ================================================================
echo    ==        PARANDO TODOS OS SERVICOS                          ==
echo    ================================================================
echo.
call :PARAR_SILENCIOSO
echo.
call :NOTIFICAR "Sistema CETS parado"
echo    ================================================================
echo    ==       [OK] TODOS OS SERVICOS DESLIGADOS                    ==
echo    ================================================================
echo.
pause
goto MENU

:: ================================================================
::  OPCAO 3: REINICIAR
:: ================================================================
:REINICIAR
cls
call :LOG "REINICIANDO SISTEMA"
echo.
echo    ================================================================
echo    ==          REINICIANDO SISTEMA                              ==
echo    ================================================================
echo.
call :PARAR_SILENCIOSO
echo.
echo    [*] Aguardando 3 segundos antes de reiniciar...
timeout /t 3 >nul
goto INICIAR

:: ================================================================
::  OPCAO 4: STATUS DO SISTEMA
:: ================================================================
:STATUS
cls
echo.
echo    ================================================================
echo    ==          STATUS DO SISTEMA CETS                           ==
echo    ================================================================
echo.

:: Verificar PHP
set PHP_COUNT=0
for /f %%a in ('tasklist /FI "IMAGENAME eq php.exe" 2^>NUL ^| find /C "php.exe"') do set PHP_COUNT=%%a
if %PHP_COUNT% GTR 0 (
    echo    [+] PHP: ATIVO (%PHP_COUNT% processos^)
) else (
    echo    [-] PHP: INATIVO
)

:: Verificar Apache
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe" >NUL
if "%ERRORLEVEL%"=="0" (
    echo    [+] Apache: ATIVO
) else (
    echo    [-] Apache: INATIVO
)

:: Verificar MySQL
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe" >NUL
if "%ERRORLEVEL%"=="0" (
    echo    [+] MySQL: ATIVO
) else (
    echo    [-] MySQL: INATIVO
)

:: Verificar Ngrok
tasklist /FI "IMAGENAME eq ngrok.exe" 2>NUL | find /I /N "ngrok.exe" >NUL
if "%ERRORLEVEL%"=="0" (
    echo    [+] Ngrok: ATIVO
) else (
    echo    [-] Ngrok: INATIVO
)

echo.
echo    ================================================================
echo    Uso de Memoria:
echo.
tasklist /FI "IMAGENAME eq php.exe" /FO TABLE 2>NUL | findstr "php.exe"
echo.
echo    ================================================================
echo.
pause
goto MENU

:: ================================================================
::  OPCAO 5: ABRIR CONSOLE
:: ================================================================
:CONSOLE
cls
call :LOG "Console aberto"
echo.
echo    [*] Abrindo terminal na pasta do projeto...
echo.
cd /d "%PROJETO_PATH%"
start "Terminal CETS" cmd /k "title Terminal CETS & color 0C & echo =============================== & echo    TERMINAL CETS - PRONTO PARA USO & echo =============================== & echo. & echo Diretorio: %PROJETO_PATH% & echo. & dir"
goto MENU

:: ================================================================
::  OPCAO 6: ABRIR LINKS
:: ================================================================
:LINKS
cls
echo.
echo    [*] Abrindo links do sistema...
echo.
start "" "http://localhost:8000"
echo    [OK] Site Local
start "" "%NGROK_URL%/admin"
echo    [OK] Ngrok (Admin)
start "" "http://localhost/phpmyadmin"
echo    [OK] phpMyAdmin
start "" "http://localhost:4040"
echo    [OK] Ngrok Dashboard
echo.
echo    Todos os links foram abertos!
echo.
timeout /t 3 >nul
goto MENU

:: ================================================================
::  OPCAO 7: LIMPAR CACHE
:: ================================================================
:CACHE
cls
call :LOG "Limpeza de cache iniciada"
echo.
echo    ================================================================
echo    ==          LIMPANDO CACHE DO LARAVEL                        ==
echo    ================================================================
echo.
cd /d "%PROJETO_PATH%"
echo    Limpando Config Cache...
php artisan config:clear
echo    [OK]
echo.
echo    Limpando Application Cache...
php artisan cache:clear
echo    [OK]
echo.
echo    Limpando View Cache...
php artisan view:clear
echo    [OK]
echo.
echo    Limpando Route Cache...
php artisan route:clear
echo    [OK]
echo.
echo    Limpando Compiled Classes...
php artisan clear-compiled
echo    [OK]
echo.
echo    Otimizando sistema...
php artisan optimize
echo    [OK]
echo.
call :LOG "Cache limpo com sucesso"
echo    ================================================================
echo    ==       [OK] CACHE LIMPO COM SUCESSO!                        ==
echo    ================================================================
echo.
pause
goto MENU

:: ================================================================
::  OPCAO 8: MIGRATIONS
:: ================================================================
:MIGRATIONS
cls
echo.
echo    ================================================================
echo    ==          EXECUTAR MIGRATIONS                              ==
echo    ================================================================
echo.
cd /d "%PROJETO_PATH%"
echo    [!] ATENCAO: Isso pode modificar o banco de dados!
echo.
choice /C SN /M "    Deseja continuar"
if errorlevel 2 goto MENU
echo.
echo    Executando migrations...
php artisan migrate
echo.
call :LOG "Migrations executadas"
echo    [OK] Migrations concluidas!
echo.
pause
goto MENU

:: ================================================================
::  OPCAO 9: SEEDERS
:: ================================================================
:SEEDERS
cls
echo.
echo    ================================================================
echo    ==          EXECUTAR SEEDERS                                 ==
echo    ================================================================
echo.
cd /d "%PROJETO_PATH%"
echo    [!] ATENCAO: Isso pode adicionar dados ao banco!
echo.
choice /C SN /M "    Deseja continuar"
if errorlevel 2 goto MENU
echo.
echo    Executando seeders...
php artisan db:seed
echo.
call :LOG "Seeders executados"
echo    [OK] Seeders concluidos!
echo.
pause
goto MENU

:: ================================================================
::  OPCAO A: BACKUP
:: ================================================================
:BACKUP
cls
echo.
echo    ================================================================
echo    ==          BACKUP DO BANCO DE DADOS                         ==
echo    ================================================================
echo.

:: Obter data/hora para nome do arquivo
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set BACKUP_FILE=%BACKUP_PATH%\backup_%datetime:~0,8%_%datetime:~8,6%.sql

echo    Local do backup: %BACKUP_FILE%
echo.
echo    Criando backup do banco de dados...
echo.

:: Configuracoes do MySQL (AJUSTE CONFORME SUA CONFIGURACAO)
set MYSQL_USER=root
set MYSQL_PASS=
set DB_NAME=cets

"%XAMPP_PATH%\mysql\bin\mysqldump.exe" -u %MYSQL_USER% --password=%MYSQL_PASS% %DB_NAME% > "%BACKUP_FILE%" 2>nul

if exist "%BACKUP_FILE%" (
    echo    [OK] Backup criado com sucesso!
    echo    Tamanho: 
    dir "%BACKUP_FILE%" | findstr "backup_"
    call :LOG "Backup criado: %BACKUP_FILE%"
) else (
    echo    [!] Erro ao criar backup!
    echo    Verifique as configuracoes do MySQL
)

echo.
echo    Backups disponiveis:
dir /B "%BACKUP_PATH%\backup_*.sql" 2>nul
echo.
pause
goto MENU

:: ================================================================
::  OPCAO B: VER LOGS
:: ================================================================
:LOGS
cls
echo.
echo    ================================================================
echo    ==          LOGS DO SISTEMA                                  ==
echo    ================================================================
echo.
echo    [1] Ver Log do Gerenciador
echo    [2] Ver Log do Laravel (ultimas 50 linhas)
echo    [3] Ver Log de Erros do Laravel
echo    [4] Voltar ao Menu
echo.
set /p log_opcao="    Escolha: "

if "%log_opcao%"=="1" (
    cls
    echo    LOG DO GERENCIADOR:
    echo    ================================================================
    if exist "%LOG_PATH%\gerenciador.log" (
        type "%LOG_PATH%\gerenciador.log"
    ) else (
        echo    Nenhum log encontrado.
    )
    echo.
    pause
    goto LOGS
)

if "%log_opcao%"=="2" (
    cls
    cd /d "%PROJETO_PATH%"
    if exist "storage\logs\laravel.log" (
        echo    ULTIMAS 50 LINHAS DO LOG LARAVEL:
        echo    ================================================================
        powershell -command "Get-Content 'storage\logs\laravel.log' -Tail 50"
    ) else (
        echo    Nenhum log do Laravel encontrado.
    )
    echo.
    pause
    goto LOGS
)

if "%log_opcao%"=="3" (
    cls
    cd /d "%PROJETO_PATH%"
    if exist "storage\logs\laravel.log" (
        echo    ERROS NO LOG DO LARAVEL:
        echo    ================================================================
        findstr /I "ERROR Exception" "storage\logs\laravel.log"
    ) else (
        echo    Nenhum log do Laravel encontrado.
    )
    echo.
    pause
    goto LOGS
)

goto MENU

:: ================================================================
::  OPCAO C: VERIFICAR DEPENDENCIAS
:: ================================================================
:VERIFICAR
cls
echo.
echo    ================================================================
echo    ==          VERIFICAR DEPENDENCIAS                           ==
echo    ================================================================
echo.

cd /d "%PROJETO_PATH%"

echo    Verificando PHP...
php -v
echo.

echo    Verificando Composer...
composer --version
echo.

echo    Verificando Node.js...
node -v
echo.

echo    Verificando NPM...
npm -v
echo.

echo    ================================================================
echo    Deseja instalar/atualizar dependencias?
echo    [1] Composer Install
echo    [2] Composer Update
echo    [3] NPM Install
echo    [4] NPM Update
echo    [5] Voltar
echo.
set /p dep_opcao="    Escolha: "

if "%dep_opcao%"=="1" (
    echo    Executando composer install...
    composer install
    pause
    goto VERIFICAR
)
if "%dep_opcao%"=="2" (
    echo    Executando composer update...
    composer update
    pause
    goto VERIFICAR
)
if "%dep_opcao%"=="3" (
    echo    Executando npm install...
    npm install
    pause
    goto VERIFICAR
)
if "%dep_opcao%"=="4" (
    echo    Executando npm update...
    npm update
    pause
    goto VERIFICAR
)

goto MENU

:: ================================================================
::  OPCAO D: LIMPAR LOGS ANTIGOS
:: ================================================================
:LIMPAR_LOGS
cls
echo.
echo    ================================================================
echo    ==          LIMPAR LOGS ANTIGOS                              ==
echo    ================================================================
echo.
echo    [!] Isso vai deletar logs com mais de 7 dias!
echo.
choice /C SN /M "    Deseja continuar"
if errorlevel 2 goto MENU

cd /d "%PROJETO_PATH%"
echo.
echo    Removendo logs antigos do Laravel...
forfiles /P "storage\logs" /M *.log /D -7 /C "cmd /c del @path" 2>nul

echo    Removendo logs antigos do gerenciador...
forfiles /P "%LOG_PATH%" /M *.log /D -7 /C "cmd /c del @path" 2>nul

call :LOG "Logs antigos removidos"
echo.
echo    [OK] Logs antigos removidos!
echo.
pause
goto MENU

:: ================================================================
::  OPCAO E: MODO MANUTENCAO
:: ================================================================
:MANUTENCAO
cls
echo.
echo    ================================================================
echo    ==          MODO MANUTENCAO                                  ==
echo    ================================================================
echo.
cd /d "%PROJETO_PATH%"

:: Verificar se esta em manutencao
if exist "storage\framework\down" (
    echo    [!] Sistema atualmente em MODO MANUTENCAO
    echo.
    choice /C SN /M "    Deseja DESATIVAR o modo manutencao"
    if errorlevel 2 goto MENU
    php artisan up
    echo.
    echo    [OK] Modo manutencao DESATIVADO!
    call :LOG "Modo manutencao desativado"
) else (
    echo    [OK] Sistema atualmente ONLINE
    echo.
    choice /C SN /M "    Deseja ATIVAR o modo manutencao"
    if errorlevel 2 goto MENU
    php artisan down --retry=60
    echo.
    echo    [!] Modo manutencao ATIVADO!
    call :LOG "Modo manutencao ativado"
)

echo.
pause
goto MENU

:: ================================================================
::  OPCAO F: INFORMACOES
:: ================================================================
:INFO
cls
echo.
echo    ================================================================
echo    ==          INFORMACOES DO SISTEMA                           ==
echo    ================================================================
echo.
cd /d "%PROJETO_PATH%"

echo    Versao do Gerenciador: %VERSION%
echo    Diretorio do Projeto: %PROJETO_PATH%
echo    XAMPP: %XAMPP_PATH%
echo    URL Ngrok: %NGROK_URL%
echo.
echo    ================================================================
echo    VERSOES:
echo    ================================================================
echo.
php -v | findstr "PHP"
echo.
php artisan --version
echo.
composer --version | findstr "Composer"
echo.
node -v
npm -v
echo.
echo    ================================================================
echo    ESPACO EM DISCO:
echo    ================================================================
echo.
cd /d "%PROJETO_PATH%"
dir | findstr "bytes"
echo.
pause
goto MENU

:: ================================================================
::  OPCAO G: COPIAR URL
:: ================================================================
:COPIAR_URL
cls
echo.
echo    [*] Copiando URL do Ngrok...
echo.
echo %NGROK_URL% | clip
echo    [OK] URL copiada para a area de transferencia!
echo.
echo    %NGROK_URL%
echo.
timeout /t 2 >nul
goto MENU

:: ================================================================
::  OPCAO H: CONFIGURACOES
:: ================================================================
:CONFIG
cls
echo.
echo    ================================================================
echo    ==          CONFIGURACOES DO SISTEMA                         ==
echo    ================================================================
echo.
echo    CONFIGURACOES ATUAIS:
echo    ================================================================
echo.
echo    Projeto: %PROJETO_PATH%
echo    XAMPP: %XAMPP_PATH%
echo    Ngrok: %NGROK_URL%
echo    Logs: %LOG_PATH%
echo    Backups: %BACKUP_PATH%
echo.
echo    ================================================================
echo.
echo    [1] Editar Configuracoes Manualmente
echo    [2] Testar Conexao com Banco
echo    [3] Ver Arquivo .env
echo    [4] Voltar
echo.
set /p cfg_opcao="    Escolha: "

if "%cfg_opcao%"=="1" (
    notepad "%~f0"
    goto CONFIG
)

if "%cfg_opcao%"=="2" (
    cd /d "%PROJETO_PATH%"
    echo.
    echo    Testando conexao com banco de dados...
    php artisan db:show
    echo.
    pause
    goto CONFIG
)

if "%cfg_opcao%"=="3" (
    cd /d "%PROJETO_PATH%"
    if exist ".env" (
        notepad ".env"
    ) else (
        echo    [!] Arquivo .env nao encontrado!
        pause
    )
    goto CONFIG
)

goto MENU

:: ================================================================
::  OPCAO 0: SAIR
:: ================================================================
:SAIR
cls
echo.
echo    ================================================================
echo    ==                                                            ==
echo    ==                  ATE LOGO!                                 ==
echo    ==                                                            ==
echo    ==           Obrigado por usar o Gerenciador CETS            ==
echo    ==                                                            ==
echo    ================================================================
echo.
call :LOG "Sistema encerrado pelo usuario"

echo    [!] Deseja parar todos os servicos antes de sair?
echo.
choice /C SN /M "    "
if errorlevel 2 goto SAIR_FINAL
call :PARAR_SILENCIOSO

:SAIR_FINAL
echo.
echo    Fechando em 3 segundos...
timeout /t 3 >nul
exit

:: ================================================================
::  FUNCOES AUXILIARES
:: ================================================================

:: Funcao: Barra de Progresso
:PROGRESS_BAR
set /a total=%1
set /a step=%total%/5
echo          [                    ] 0%%
timeout /t %step% >nul
echo          [####                ] 20%%
timeout /t %step% >nul
echo          [########            ] 40%%
timeout /t %step% >nul
echo          [############        ] 60%%
timeout /t %step% >nul
echo          [################    ] 80%%
timeout /t %step% >nul
echo          [####################] 100%%
goto :eof

:: Funcao: Registrar Log
:LOG
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set timestamp=%datetime:~0,4%-%datetime:~4,2%-%datetime:~6,2% %datetime:~8,2%:%datetime:~10,2%:%datetime:~12,2%
echo [%timestamp%] %~1 >> "%LOG_PATH%\gerenciador.log"
goto :eof

:: Funcao: Notificacao do Windows
:NOTIFICAR
powershell -Command "& {Add-Type -AssemblyName System.Windows.Forms; Add-Type -AssemblyName System.Drawing; $notify = New-Object System.Windows.Forms.NotifyIcon; $notify.Icon = [System.Drawing.SystemIcons]::Information; $notify.Visible = $true; $notify.ShowBalloonTip(5000, 'CETS Manager', '%~1', [System.Windows.Forms.ToolTipIcon]::Info)}" 2>nul
goto :eof

:: Funcao: Parar Servicos Silenciosamente
:PARAR_SILENCIOSO
echo    [*] Parando processos PHP (Artisan, Fila)...
taskkill /F /IM php.exe /T >nul 2>&1
timeout /t 1 >nul
echo          [OK] PHP encerrado
call :LOG "PHP encerrado"
echo.
echo    [*] Parando Servidor Apache...
taskkill /F /IM httpd.exe /T >nul 2>&1
timeout /t 1 >nul
echo          [OK] Apache encerrado
call :LOG "Apache encerrado"
echo.
echo    [*] Parando Banco MySQL...
taskkill /F /IM mysqld.exe /T >nul 2>&1
timeout /t 1 >nul
echo          [OK] MySQL encerrado
call :LOG "MySQL encerrado"
echo.
echo    [*] Parando Ngrok...
taskkill /F /IM ngrok.exe /T >nul 2>&1
echo          [OK] Ngrok encerrado
call :LOG "Ngrok encerrado"
goto :eof