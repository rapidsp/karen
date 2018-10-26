#!/bin/bash

_pkgname=$1

is_inst=$(dpkg -s $_pkgname . 2>/dev/null)

#echo $(echo $is_inst | grep 'installed')
 
if [[ ! $(echo $is_inst | grep 'installed') ]]; then 
	echo '0' > do_next	
	echo $_pkgname is not installed!!!
else 
	echo $_pkgname' 			- OK!'
fi

