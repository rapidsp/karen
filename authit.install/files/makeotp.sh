#!/bin/bash

radserver=$1
radpass=$2

r_info=$(cat /etc/*release)

if [[ $(echo $r_info | grep 'ID=debian') ]]; then
	echo It is Debian
	app_type='deb'
elif [[ $(echo $r_info | grep 'ID=astra') ]]; then	
	echo It is Astralinux 
	app_type='deb'
elif [[ $(echo $r_info | grep 'ID=rosa') ]]; then	
	echo It is Rosa	
	app_type='rosa'
else
	echo Unsupported release, exit. 
	exit
fi

###### Для деб-дистрибутивов
if [[ $app_type = 'deb' ]]; then

	# Проверка dpkg на блокировку..........

	while [[ $(fuser /var/lib/dpkg/lock) ]]; do
		echo 'Packages: dpkg is locked. Waiting...' 
		sleep 3s
	done

	# Install packages
	echo Installing packages:
	apt-get install libpam-radius-auth -y 

##### Для Росы
elif [[ $app_type = 'rosa' ]]; then
	echo 'Installing driver: '$DRVPTH
	rpm -ivh /tmp/$(basename $DRVPTH) 
	echo ' '
	echo Installing packages:
	urpmi ccid pcsc-lite pam_pkcs11  --auto --wait-lock 
	libpkcs=$(rpm -qpl /tmp/$(basename $DRVPTH) |grep P11)

fi	

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
if [[ $forcelogon = '1' ]]; then
	authforce='required'
else
	authforce='sufficient'
fi	

echo 'auth '$authforce' /lib/security/pam_radius_auth.so' | cat - /etc/pam.d/$pamd > temp && mv temp /etc/pam.d/$pamd 


### Setting radius config

if [[ ! -f '/etc/pam_radius_auth.conf ' ]]; then
	touch /etc/pam_radius_auth.conf 
fi	
echo $radserver' '$radpass' 3' >> /etc/pam_radius_auth.conf

echo ' '
echo Готово!


