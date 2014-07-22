<?php
ob_start();
$home = getenv('HOME');
$config = require "$home/.deploy-config.php";
if (!file_exists("$home/logs"))
	mkdir("$home/logs");

$LOGFILE = "$home/logs/deploy-log-".date('Ymd').".log";
$DEPLOYED = false;
$FORCE = false;
if (!empty($argv[1]) && $argv[1] == 'force')
	$FORCE = true;

set_exception_handler(function ($e) use($config, $LOGFILE) {
echo "Failure\n$e";
$contents = ob_get_contents();
ob_end_flush();
$host = gethostname();
if ($config['email'])
	mail($config['email'], "Deploy failure on $host", $contents);
file_put_contents($LOGFILE, $contents, FILE_APPEND);
});


// we are going this is a no dependency way to make the initial bootstrap deployment easier
echo date('Y-m-d H:i:s T')." - Deploy start {$config['github-user']}/{$config['github-repo']} to {$config['deploy-dir']}\n";

// get the newest release from github
$url = "https://api.github.com/repos/{$config['github-user']}/{$config['github-repo']}/releases";

// newest release are first so we only need to filter pre-release if eneded
$releases = gitHubApi($url, $config);
foreach($releases as $release)
{
    if ($config['no-pre-release'] && $release->prerelease)
        continue;

    break;
}

echo date('Y-m-d H:i:s T')." - Found a release $release->tag_name, $release->url\n";
echo date('Y-m-d H:i:s T')." - $release->name\n";

chdir($config['deploy-dir']);
if (!file_exists($config['deploy-dir']."/.git"))
{
    echo date('Y-m-d H:i:s T')." - No git repo found doing initial clone\n";
    $clone = "https://{$config['github-user']}:{$config['token']}@github.com/{$config['github-user']}/{$config['github-repo']}.git";

    // if you aren't using /var/www this is pretty safe
    // if you are permission issues will hurt you, since apache puts stuff i /var/www/html
    //run_cmd("rm -rf {$config['deploy-dir']}");
    //run_cmd("git clone ".escapeshellarg($clone).' .');

    // create a git repo in an existing dir ... good stuff
    run_cmd("git init");
    run_cmd("git remote add origin $clone");
    run_cmd("git fetch");
    run_cmd("git checkout --track origin/master");
}

// check if need to do deploy
$tag = $release->tag_name;
exec("git status", $output, $code);
if ($code == 0 && strstr($output[0], $tag) && !$FORCE)
{
	echo date('Y-m-d H:i:s T') . " - No deploy needed\n";
	
}
else
{
	echo date('Y-m-d H:i:s T') . " - Deploying\n";
	run_cmd("git fetch");
	run_cmd("git checkout $tag");

	run_cmd("composer install");
}
echo date('Y-m-d H:i:s T') . " - Complete\n";

$contents = ob_get_contents();
ob_end_flush();
$host = gethostname();
if ($config['email'] && $DEPLOYED)
	mail($config['email'], "Deploy success on $host", $contents);

file_put_contents($LOGFILE, $contents, FILE_APPEND);


function gitHubApi($url, $config)
{
    $c = curl_init($url);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($c, CURLOPT_USERPWD, $config['token'].':x-oauth-basic');
    curl_setopt($c, CURLOPT_USERAGENT, "PHP Deploy Script 1.0");

    $json = curl_exec($c);
    $info = curl_getinfo($c);
    if ($info['http_code'] != 200)
    {
        throw new Exception("Github API error: $info[http_code] $json");
    }
    return json_decode($json);
}

function run_cmd($cmd)
{
    passthru($cmd, $code);
    if ($code != 0)
    {
        throw new Exception("$cmd failed with code $code");
    }
}

