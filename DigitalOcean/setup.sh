#!/bin/bash
mkdir /vagrant/
cd /vagrant/
tar xvf /tmp/puphpet.tar
tar xvf /tmp/puphpet-user.tar
cd /vagrant/puphpet
./shell/initial-setup.sh /vagrant/puphpet
./shell/install-ruby.sh /vagrant/puphpet
./shell/install-puppet.sh /vagrant/puphpet
rvm_reload_flag=1 source /usr/local/rvm/scripts/rvm
mkdir -p /etc/facter/facts.d
echo '{"vm_target_key":"digital_ocean"}' > /etc/facter/facts.d/info.json
cd /vagrant/puphpet/puppet
puppet apply manifest.pp --verbose --hiera_config /vagrant/puphpet/puppet/hiera.yaml --parser future --modulepath=/vagrant/puphpet/puppet/modules
cd /vagrant/puphpet
rsync -ra /vagrant/puphpet-user/exec-always/. /vagrant/puphpet/files/exec-always/
./shell/execute-files.sh exec-once exec-always 
