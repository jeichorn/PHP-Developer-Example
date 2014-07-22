<?php

require __DIR__.'/vendor/autoload.php';

// Docs at https://github.com/toin0u/DigitalOceanV2
use DigitalOceanV2\Adapter\BuzzAdapter;
use DigitalOceanV2\DigitalOceanV2;


// look for a config file in our home dir, otherwise look for a config.php in the local path
$home = getenv('HOME');
if (file_exists("$home/.DigitalOcean.php"))
	$config = include "$home/.DigitalOcean.php";
else
	$config = include "config.php";
	
$adapter = new BuzzAdapter($config['token']);

$do = new DigitalOceanV2($adapter);

$defaults = [
	'size' => '1024mb',
	'region' => 'nyc2',
	'image' => null,
	'name' => 'awesome',
	'backups' => false,
	'ipv6' => false,
	'privateNetworking' => false,
	'sshKeys' => [],
];
foreach($defaults as $k => $v)
{
	if (empty($config[$k]))
		$config[$k] = $v;
}
if (!empty($config['ssh-identity']))
{
	$config['sshKeys'][] = file_get_contents(str_replace('~', $home, $config['ssh-identity'].'.pub'));
}

	
$dropletApi = $do->droplet();

// see if the server is already running
$droplets = $dropletApi->getAll();

foreach($droplets as $possible)
{
	if ($possible->name == $config['name'])
	{
		$droplet = $possible;
		break;
	}
}

if (empty($droplet))
{
	// lets create our server

	// lookup the image
	$imageApi = $do->image();

	try
	{	
		$image = $imageApi->getBySlug($config['image']);
	}
	catch(Exception $e)
	{
		// we get an exception on a bad slug name
	}

	if (empty($image))
	{
		echo "Set image in your config, use slug value from the options below\n";
		foreach($imageApi->getAll() as $image)
		{
			echo "$image->name - $image->slug\n";
		}
	}
	
	$keys = [];
	$keysApi = $do->key();
	foreach($config['sshKeys'] as $keyString)
	{
		if (preg_match('/^([^ ]+\s+[^ ]+)\s*(.*)$/', $keyString, $match))
		{
			try
			{
				$key = $keysApi->getByFingerprint($match[1]);
			}
			catch(Exception $e)
			{
				// 404 throws an exception
			}
			
			if (empty($key))
				$key = $keysApi->create($match[2], $match[1]);
			
			$keys[] = $key->id;
		}
	}

	$droplet = $dropletApi->create(
		$config['name'],
		$config['region'],
		$config['size'],
		$config['image'],
		$config['backups'],
		$config['ipv6'],
		$config['privateNetworking'],
		$keys
	);
}

// show info on the current droplet
echo "name: $droplet->name - $droplet->id\n";
echo "status: {$droplet->status}\n";
echo "networking\n";
if (!empty($droplet->networks))
{
	foreach($droplet->networks as $network)
	{
        if ($network->type == "public")
            $serverIp = $network->ipAddress;
		echo "  $network->type: $network->ipAddress\n";
	}
}
else
{
	echo "Initializing\n";
}
echo "region: {$droplet->region->name} - {$droplet->region->slug}\n";
echo "image: {$droplet->image->name} - {$droplet->image->slug}\n";
echo "size: {$droplet->size->slug} cpu({$droplet->size->vcpus}) memory({$droplet->size->memory}) disk({$droplet->size->disk})\n";

if (!empty($argv[1]))
{
    $baseDir = realpath(__DIR__.'/../');

    // if we are in a real shell we could do this in one pipe, or maybe use rsync
    //  but lets use a temp file instead so we work in the shell that github ships
	echo "Bootstrapping server\n";

    chdir($baseDir);
    passthru("scp ./DigitalOcean/setup.sh root@$serverIp:");
    passthru("tar cf puphpet.tar puphpet");
    passthru("scp puphpet.tar root@$serverIp:/tmp/puphpet.tar");
    passthru("ssh root@$serverIp 'sh -c /root/setup.sh'");

}
