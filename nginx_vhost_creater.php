<?php  

	/**
	* Nginx virtual host creater class
	* @author Tushar Kant Verma
	*/
	class NginxVhost
	{
		public $_web_root, $_config_dir, $_domain_name;

		/**
		* Construct the class object with the web root path, nginx config dir path and the domain name passed
		*/
		public function __construct($web_root, $config_dir, $domain_name) {
			$this->_web_root = $web_root;
			$this->_config_dir = $config_dir;
			$this->_domain_name = $domain_name;
		}

		/**
		* Create config file
		*
		* @param string [$templateStr] [The config file of the new virtual host] 
		* @return boolean [true/false - upon creation]
		*/
		public function createConfigFile($templateStr = null) {
			if ($templateStr != null) {
				$filename = "{$this->_config_dir}/sites-available/{$this->_domain_name}.conf";
				$config_file = fopen($filename, "w") or die("Unable to open file, are you not logged in as a root user!");
				fwrite($config_file, $templateStr);
				fclose($config_file);
				return true;
			}
			return false;
		}

		/**
		* Create the website root directories and sub directories and assign them the permissions
		*
		* @param No Parameters
		* @return boolean [true/false - upon creation]
		*/
		public function createWebDirectories() {
			$root_path = "{$this->_web_root}/{$this->_domain_name}";
			if (mkdir($root_path, 0755, true)) { // Make web host directory and give permissions
				mkdir($root_path.'/public', 0755, true);
				mkdir($root_path.'/private', 0755, true);
				mkdir($root_path.'/log', 0755, true);
				mkdir($root_path.'/backup', 0755, true);
				return true;
			} else {
				return false;
			}
		}

		/**
		* Create symlink to enable site and assign necessary permissions to it.
		*
		* @param No Parameters
		* @return boolean [true/false - upon creation]
		*/
		public function createSymlinkAssignPermissions($nginx_user = 'www-data')
		{
			$config_file = "{$this->_config_dir}/sites-available/{$this->_domain_name}.conf";
			$target_link = "{$this->_config_dir}/sites-enabled/{$this->_domain_name}.conf";
			$root_path = "{$this->_web_root}/{$this->_domain_name}";
			if (symlink($config_file, $target_link)) {
				// Set User
				chown($root_path, $nginx_user);
				// Set Permission
				chmod($root_path.'/public', 755);
				// Check Result
				$stat = stat($root_path);
				echo "\nVirtual Host root path details..\n";
				print_r(posix_getpwuid($stat['uid']));
				echo "\n";
				return true;
			} else {
				return false;
			}
		}

		/**
		* Function to restart the Nginx.
		*
		* @param No Parameters
		* @return string [output of the command - service nginx reload]
		*/
		public function restartNginx() {
			$output = "";
			exec("/etc/init.d/nginx reload", $output);
			print_r($output);
		}

		/**
		* Function to create a file with index.php just to render phpinfo()
		*
		* @param No Parameters
		* @return Nothing
		*/
		public function createPHPInfoFile() {
			$filename = "{$this->_web_root}/{$this->_domain_name}/public/index.php";
			exec('echo "<?php phpinfo(); ?>" > '.$filename.'');
			chown($filename, 'www-data');
		}
	}

	// Checks for the parameters passed
	$DOMAIN = null;
	if (!isset($argv[1]) && empty($argv[1]) || !isset($argv[2]) && empty($argv[2])) {
		echo "\nPlease call with paramter viz 'create' or 'remove' and after that pass the name of the vhost you want!!\n";
		return false;
	} else {
		$case = $argv[1]; // Passed parameter "create" or "remove"; 
		$DOMAIN = $argv[2];  // Name of the domain for which vhost need to be created
	} 

	// Casual Check
	if ($DOMAIN == null) die("Pass domain name please..!!");

	$web_root = '/usr/share/nginx/html';
	$config_dir = '/etc/nginx/';
	// Create object of NginxVhost class
	$obj = new NginxVhost($web_root, $config_dir, $DOMAIN);

	echo "\nCreating an entry inside nginx sites-available directory..\n";

	$templateStr = "server {
	  listen   80; ## listen for ipv4; this line is default and implied
	  #listen   [::]:80 default_server ipv6only=on; ## listen for ipv6

	  root {$web_root}/{$DOMAIN}/public;
	  index index.php  index.html index.htm;

	  # Make site accessible from http://localhost/
	  server_name {$DOMAIN} www.{$DOMAIN};

	  location / {
	  	# First attempt to serve request as file, then
	    # as directory, then fall back to displaying a 404.
	  	try_files \$uri \$uri/ =404;
	  }

	  location ~ \.php$ {
	    try_files \$uri =404;

	    fastcgi_split_path_info ^(.+\.php)(/.+)$;
	    #NOTE: You should have 'cgi.fix_pathinfo = 0;' in php.ini

	    fastcgi_pass unix:/var/run/php5-fpm.sock;
	    fastcgi_index index.php;
	    include fastcgi_params;
	  }
	  location ~ /\.ht {
	    deny all;
	  }
	  access_log {$web_root}/{$DOMAIN}/log/access_log.txt;
	  error_log {$web_root}/{$DOMAIN}/log/error_log.txt error;
	}";

	echo "\n--------Configuration File---------\n";
	echo $templateStr;
	echo "\n---Configuration File ends here----\n";

	if ($case != null) {
		switch ($case) {
			case 'create':
				if ($obj->createConfigFile($templateStr)) {
					echo "Nginx vhost configuration file created...\nMaking web directories...\n";
					
					if ($obj->createWebDirectories()) {
						echo "Web Directories created..\n";
					} else {
						die("Web directories creation failed, please run this script as root user!!");
					}

					if ($obj->createSymlinkAssignPermissions()) {
						echo "Symlinking, site enabling & permission setup done...\n";
					} else {
						die("Not able to create symlink..!!!");
					}

					$obj->restartNginx();
					echo "Creating index.php file..\n";
					$obj->createPHPInfoFile();

					echo "Virtual host {$DOMAIN} has been created now point your IP to this domain on local goto /etc/hosts and edit it..!\nHappy Coding..\n";

				}
				break;
			
			case 'remove':
				# @TODO
				break;

			default:
				echo "Only create or remove paramters are allowed.";
				break;
		}	
	} else {
		die("Pass first parameter as 'create' or 'remove'");
	}

?>