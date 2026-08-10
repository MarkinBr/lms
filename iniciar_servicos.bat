@echo off

:: --- CONFIGURAÇÃO DE CAMINHOS ---
:: Ajuste estas variaveis se nao estiverem no ambiente global
set "XAMPP_PATH=C:\xampp"
set "PROJETO_PATH=C:\xampp\htdocs\cets"

:: [1] Iniciando XAMPP (MySQL/Apache) de forma oculta
:: Usamos /min para nao pular na tela
if exist "%XAMPP_PATH%\mysql_start.bat" start /min "" "%XAMPP_PATH%\mysql_start.bat"
if exist "%XAMPP_PATH%\apache_start.bat" start /min "" "%XAMPP_PATH%\apache_start.bat"

:: Aguarda bancos subirem
timeout /t 5 /nobreak >nul

cd /d "%PROJETO_PATH%"

:: [2] Limpeza (Redirecionando output para nul para ser silencioso)
call php artisan config:clear >nul 2>&1
call php artisan cache:clear >nul 2>&1
call php artisan view:clear >nul 2>&1
call php artisan route:clear >nul 2>&1

:: [3] Storage
if not exist "%PROJETO_PATH%\storage" mkdir "%PROJETO_PATH%\storage" >nul 2>&1

:: [4] Servidores e Filas (Rodando Minimizados)
:: O servidor vai rodar minimizado na barra de tarefas
start /min "CETS_Laravel" php artisan serve --host 0.0.0.0 --port 8000

:: A fila roda minimizada
start /min "CETS_Queue" php artisan queue:work --tries=3

:: Aguarda servidores subirem
timeout /t 3 /nobreak >nul

:: [5] Abrir Navegador e URLs
start "" "http://localhost:8000/secretaria"


exit