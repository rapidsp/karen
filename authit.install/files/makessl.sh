#!/bin/bash

echo "Lets begin..."

DRVPTH=$1
INSTDRV=$2

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
	apt-get install libccid pcscd libpam-pkcs11 -y 

	while [[ $(fuser /var/lib/dpkg/lock) ]]; do
		echo 'Driver: dpkg is locked. Waiting...' 
		sleep 3s
	done

	# Ищем путь для библиотеки pkcs11
	str=$(dpkg-deb -c /tmp/$(basename $DRVPTH) |grep P11)
	libpkcs=${str:`expr index "$str" .`}

	echo ' '
	echo 'Installing driver: '$DRVPTH
	dpkg -i /tmp/$(basename $DRVPTH)

##### Для Росы
elif [[ $app_type = 'rosa' ]]; then
	echo 'Installing driver: '$DRVPTH
	rpm -ivh /tmp/$(basename $DRVPTH) 
	echo ' '
	echo Installing packages:
	urpmi ccid pcsc-lite pam_pkcs11  --auto --wait-lock 
	libpkcs=$(rpm -qpl /tmp/$(basename $DRVPTH) |grep P11)

fi	

# При установке выставляется разрешение 777. Меняем, чтобы pam.d не ругался
chmod 644 $libpkcs
 

# Set pam_pkcs11.conf
if [[ -f '/etc/pam_pkcs11/pam_pkcs11.conf' ]]; then
	mv /etc/pam_pkcs11/pam_pkcs11.conf /etc/pam_pkcs11/pam_pkcs11.conf.authit
fi

if [[ ! -d "/etc/pam_pkcs11" ]]; then
	mkdir /etc/pam_pkcs11
fi	


echo '#' > /etc/pam_pkcs11/pam_pkcs11.conf
echo '# Configuration file for pam_pkcs11 module by Authenticate It!' >> /etc/pam_pkcs11/pam_pkcs11.conf
echo '#' >> /etc/pam_pkcs11/pam_pkcs11.conf
echo 'pam_pkcs11 {' >> /etc/pam_pkcs11/pam_pkcs11.conf
echo 'nullok = true;' >> /etc/pam_pkcs11/pam_pkcs11.conf

echo '  #debug = true; ' >> /etc/pam_pkcs11/pam_pkcs11.conf
echo '  use_first_pass = false;' >> /etc/pam_pkcs11/pam_pkcs11.conf
echo '  try_first_pass = false;' >> /etc/pam_pkcs11/pam_pkcs11.conf
echo '  use_authtok = false;' >> /etc/pam_pkcs11/pam_pkcs11.conf
echo '  use_pkcs11_module = JaCarta;' >> /etc/pam_pkcs11/pam_pkcs11.conf
echo '	pkcs11_module JaCarta {  
       		module = '$libpkcs';
       		description = "JaCarta PKCS#11 module";
       		slot_num = 0;
       		support_threads = true;
       		ca_dir = /etc/ssl;
       		crl_dir = /etc/ssl/crl;
       		cert_policy = ca,signature; }' >> /etc/pam_pkcs11/pam_pkcs11.conf

echo '  use_mappers = digest, cn, pwent, uid, mail, subject, null;
  mapper_search_path = /lib/pam_pkcs11;

  mapper generic {
        debug = true;
        #module = /lib/pam_pkcs11/generic_mapper.so;
        module = internal;
        # ignore letter case on match/compare
        ignorecase = false;
        # Use one of "cn" , "subject" , "kpn" , "email" , "upn" , "uid" or "serial"
        cert_item  = cn;
        # Define mapfile if needed, else select "none"
        mapfile = file:///etc/pam_pkcs11/generic_mapping;
        # Decide if use getpwent() to map login
        use_getpwent = false;
  }

  mapper subject {
	debug = false;
	# module = /lib/pam_pkcs11/subject_mapper.so;
	module = internal;
	ignorecase = false;
	mapfile = file:///etc/pam_pkcs11/subject_mapping;
  }

  mapper openssh {
	debug = false;
	module = /lib/pam_pkcs11/openssh_mapper.so;
  }

  mapper opensc {
	debug = false;
	module = /lib/pam_pkcs11/opensc_mapper.so;
  }

  mapper pwent {
	debug = false;
	ignorecase = false;
	module = internal;
  }

  mapper null {
	debug = false;
	# module = /lib/pam_pkcs11/null_mapper.so;
	module = internal ;
	# select behavior: always match, or always fail
	default_match = false;
	# on match, select returned user
        default_user = nobody ;
  }


  # Assume common name (CN) to be the login
  mapper cn {
	debug = false;
	module = internal;
	# module = /lib/pam_pkcs11/cn_mapper.so;
	ignorecase = true;
	# mapfile = file:///etc/pam_pkcs11/cn_map;
	mapfile = "none";
  }

  # mail -  Compare email field from certificate
  mapper mail {
	debug = false;
	module = internal;
	# module = /lib/pam_pkcs11/mail_mapper.so;
	# Declare mapfile or
	# leave empty "" or "none" to use no map 
	mapfile = file:///etc/pam_pkcs11/mail_mapping;
	# Some certs store email in uppercase. take care on this
	ignorecase = true;
	# Also check that host matches mx domain
	# when using mapfile this feature is ignored
	ignoredomain = false;
  }

  # uid  - Maps Subject Unique Identifier field (if exist) to login
  mapper uid {
	debug = false;
	module = internal;
	# module = /lib/pam_pkcs11/uid_mapper.so;
	ignorecase = false;
	mapfile = "none";
  }

  mapper digest {
	debug = false;
	module = internal;
	# module = /lib/pam_pkcs11/digest_mapper.so;
	# algorithm used to evaluate certificate digest
        # Select one of:
	# "null","md2","md4","md5","sha","sha1","dss","dss1","ripemd160"
	algorithm = "sha1";
	mapfile = file:///etc/pam_pkcs11/digest_mapping;
	# mapfile = "none";
  }

}' >> /etc/pam_pkcs11/pam_pkcs11.conf

if [[ ! -d "/etc/ssl/crl" ]]; then
	mkdir /etc/ssl/crl
fi	

