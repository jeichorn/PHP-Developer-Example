#!/bin/bash
mkdir /vagrant/
cd /vagrant/
tar xvf /tmp/puphpet.tar
cd /vagrant/puphpet
./shell/initial-setup.sh /vagrant/puphpet
./shell/install-ruby.sh /vagrant/puphpet
./shell/install-puppet.sh /vagrant/puphpet
mkdir -p /etc/facter/facts.d
echo '{"vm_target_key":"digital_ocean"}' > /etc/facter/facts.d/info.json
cd /vagrant/puphpet/puppet
puppet apply manifest.pp --verbose --hiera_config /vagrant/puphpet/puppet/hiera.yaml --parser future --modulepath=/vagrant/puphpet/puppet/modules
