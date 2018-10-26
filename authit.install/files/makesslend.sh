#!/bin/bash

forcelogon=$1

echo Выполнение pkcs11_make_hash_link для /etc/ssl/ ...
pkcs11_make_hash_link /etc/ssl/ 
echo Выполнение pkcs11_make_hash_link для /etc/ssl/crl ...
pkcs11_make_hash_link /etc/ssl/crl/ 

r_info=$(cat /etc/*release)

if [[ $(echo $r_info | grep 'ID=rosa') ]]; then	
	pamd='system-auth'
else
	pamd='common-auth'	
fi	

# Setting pam.d

if [[ -f '/etc/pam.d/'$pamd ]]; then
	cp /etc/pam.d/$pamd /etc/pam.d/$pamd.authit
fi

echo Настрока двухфакторного входа...
if [ $forcelogon = '1' ]; then
	authforce='required'
else
	authforce='sufficient'
fi	

echo 'auth '$authforce' pam_pkcs11.so config_file=/etc/pam_pkcs11/pam_pkcs11.conf' | cat - /etc/pam.d/$pamd > temp && mv temp /etc/pam.d/$pamd 
echo ' '
echo Готово!
