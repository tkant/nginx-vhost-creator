# nginx-vhost-creator

Usage of the nginx vhost creator script :
1. Login as root user, or the user who have access to edit the nginx configuration files and modify permissions to access to web root directory.

2. Execute the php script by using php-cli - for example :
php nginx_vhost_creater.php create examplesite.com

3. Edit the host file which is located at /etc/hosts to point an IP to this newly created vhost. for example, I had mapped this domain to my localhost :
127.0.0.1 	examplesite.com

4. Now point your browser to the this newly created host. For ex.

http://examplesite.com

5. You can see the phpinfo() output on the screen.

6. Thats it!! Enjoy
