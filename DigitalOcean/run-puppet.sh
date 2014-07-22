#!/bin/bash
rvm_reload_flag=1 source /usr/local/rvm/scripts/rvm
cd /vagrant/puphpet/puppet
puppet apply manifest.pp --verbose --hiera_config /vagrant/puphpet/puppet/hiera.yaml --parser future --modulepath=/vagrant/puphpet/puppet/modules
