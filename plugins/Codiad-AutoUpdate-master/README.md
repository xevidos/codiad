# WARNING

It is tested on Ubuntu 12.04 and Windows 2008R2 with Apache 2.x but may mess up your system.
All files during the upgrade are stored at /backup if there is some failure.

# Auto Update

This plugin hooks into the current update check and provides the possibility to automatically update your system. 

# Requirements

- Write Permission for webserver user for whole codiad directory
- Installed ZIP Extension for PHP
- Installed OPENSSL Extension for PHP
- Environment variable ```allow_url_fopen``` has been set to ```On```

# Installation

- Download the zip file and extract it to your plugins folder
- Enable this plugin in the plugins manager in Codiad

# Change Update URL

- Define UPDATEURL in your config.php
- UPDATEURL needs syntax like https://api.github.com/repos/Codiad/Codiad/tags
- Update Channel is only available if update.codiad.com is used

# WARNING

It is tested on Ubuntu 12.04 and Windows 2008R2 with Apache 2.x but may mess up your system.
All files during the upgrade are stored at /backup if there is some failure.
