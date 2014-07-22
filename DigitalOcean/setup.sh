#!/bin/bash
#tar xvf /tmp/puphpet.tar
cd /root/puphpet
./shell/initial-setup.sh /root/puphpet
./shell/install-ruby.sh /root/puphpet
./shell/install-puppet.sh /root/puphpet
