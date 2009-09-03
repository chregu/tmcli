#!/bin/bash
#that's nt totally secure...
F=`security find-generic-password  -a 18A791C8-4E98-4672-A640-C014D777AF34 -g 2>&1 | grep password: | cut -d '"'  -f 2` 
echo -n $F | /usr/bin/hdiutil attach  -noautofsck -noverify "$1" -stdinpass  

