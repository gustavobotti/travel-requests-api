@echo off
echo Initializing Git repository...
git init

echo.
echo Adding remote origin...
git remote add origin git@github.com:gustavobotti/travel-requests-api.git

echo.
echo Adding all files to git...
git add .

echo.
echo Creating initial commit...
git commit -m "Initial commit: Laravel Travel Requests API"

echo.
echo Renaming branch to main...
git branch -M main

echo.
echo Pushing to GitHub...
git push -u origin main

echo.
echo Done! Your project is now on GitHub.
pause

