RewriteEngine On
Options -Indexes
<?php if (!$use_webroot):?>
RewriteCond %{REQUEST_FILENAME} !static/ [NC]
<?php endif?>
RewriteRule ^(.*)$ index.php?q=$1 [L,QSA]
