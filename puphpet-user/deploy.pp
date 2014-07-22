user { "web-deploy":
    shell => "/bin/bash",
    groups => ['www-data'],
    ensure => present,
    managehome => true,
}

cron { "web-deploy":
    command => "/usr/bin/php /home/web-deploy/deploy.php",
    user => "web-deploy",
}

file { "/home/web-deploy/deploy.php":
    ensure => "file",
    source => "/vagrant/puphpet-user/deploy.php",
}
file { "/home/web-deploy/.deploy-config.php":
    ensure => "file",
    source => "/vagrant/puphpet-user/deploy-config.php",
}
package { "php5-curl":
    ensure => installed,
}
package { "sendmail":
    ensure => installed,
}
