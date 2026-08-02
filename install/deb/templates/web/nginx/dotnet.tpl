#=========================================================================#
# NexviaCP .NET Core (ASP.NET / Kestrel) Reverse Proxy Template           #
# Designed for .NET 8, 9, and 10 Web APIs and Web Applications (Port 5000)#
#=========================================================================#

server {
	listen      %ip%:%proxy_port%;
	server_name %domain_idn% %alias_idn%;
	error_log   /var/log/%web_system%/domains/%domain%.error.log error;

	include %home%/%user%/conf/web/%domain%/nginx.forcessl.conf*;

	location ~ /\.(?!well-known\/|file) {
		deny all;
		return 404;
	}

	location / {
		# %app_port% is the per-domain dynamic port assigned by the Node/.NET
		# app manager. Each app gets a unique port so multiple .NET sites can
		# coexist on the same server without collision.
		proxy_pass http://127.0.0.1:%app_port%;
		proxy_http_version 1.1;
		proxy_set_header Upgrade $http_upgrade;
		proxy_set_header Connection $http_connection;
		proxy_set_header Host $host;
		proxy_cache_bypass $http_upgrade;
		proxy_set_header X-Real-IP $remote_addr;
		proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
		proxy_set_header X-Forwarded-Proto $scheme;
	}

	location /error/ {
		alias %home%/%user%/web/%domain%/document_errors/;
	}

	include %home%/%user%/conf/web/%domain%/nginx.conf_*;
}
