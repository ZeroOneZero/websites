@echo off
echo You are about to update
pause
git add -A
echo That added the files to queue
pause
git commit -m "update to websites"
echo This added a commit message
pause
git push -u origin master
echo Congrats, you pushed it
pause