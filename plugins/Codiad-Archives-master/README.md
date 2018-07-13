#Unpack archives

Unpack archives directly through the filemanager.

Supports:
- .zip
- .tar
- .rar
- .tar.bzip2
- .tar.gz

To support all types of archives you might have to install the packages for your server, for Ubunt/Debian for example:

```bash
apt-get install tar unzip bzip2 gzip unrar gcc phpize php-pear php5-dev
```

To install the php rar package

```bash
pecl -v install rar 
```
###Activation

To use the plugin you also need to activate it in the php.ini file on your server and put this on the top

```bash
[PHP]                                                           
zend_extension=/usr/local/lib/ioncube/ioncube_loader_lin_5.3.so  
zend_extension=/usr/local/lib/Zend/ZendGuardLoader.so         
zend_optimizer.optimization_level=15                          
zend_loader.enable=1                                          
zend_loader.disable_licensing=0                               
zend_loader.obfuscation_level_support=3                       
;extension=php_curl.dll                                       
;extension=curl.so                                             
extension=rar.so  
```

Please restart your Server

```bash
/etc/init.d/apache2 restart 
```

##Installation

- Download the zip file and extract it to your plugin folder.

##TODO

- ~~Extract .zip~~ -> ~~Extract subZip~~
- ~~Extract .gzip~~ -> ~~Extract subGZ~~
- ~~Extract .bzip2~~ -> ~~Extract subBzip2~~
- ~~Extract .rar~~ -> Extract subRar
- ~~Extract .tar~~ -> Extract subTar
- Extract .7z
- ~~Open and navigate inside~~ (select markup implementation)
- Extract subdirectory only
