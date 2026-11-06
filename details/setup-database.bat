@echo off
REM DRMS - Create database and run CodeIgniter migrations
setlocal
cd /d "%~dp0"

set MYSQL="c:\wamp64\bin\mysql\mysql8.0.31\bin\mysql.exe"
if not exist %MYSQL% (
    echo MySQL not found at %MYSQL%
    echo Edit this script if your WAMP MySQL path is different.
    exit /b 1
)

echo [1/4] Creating database drms_db...
%MYSQL% -u root < database\01_create_database.sql
if errorlevel 1 exit /b 1

echo [2/4] Running migrations...
php spark migrate --all
if errorlevel 1 exit /b 1

echo [3/5] Seeding demo data...
php spark db:seed DrmsDemoSeeder
if errorlevel 1 exit /b 1

echo [4/5] Creating test login accounts (all roles)...
php spark db:seed DrmsTestUsersSeeder
if errorlevel 1 exit /b 1

echo [5/5] Migration status:
php spark migrate:status

echo.
echo Done. Configure .env database.default.database = drms_db
echo Create admin: php spark shield:user create -n admin -e admin@drms.local -g admin
pause
