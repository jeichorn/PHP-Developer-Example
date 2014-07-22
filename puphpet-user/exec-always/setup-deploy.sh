#!/bin/bash

rvm_reload_flag=1 source /usr/local/rvm/scripts/rvm
puppet apply /vagrant/puphpet-user/deploy.pp
