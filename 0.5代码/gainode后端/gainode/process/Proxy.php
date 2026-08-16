<?php
namespace process;

use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;

class Proxy
{
    public function onMessage(TcpConnection $connection, Request $request)
    {
        $replace = [
            'api.example.com' => 'api.openai.com',
            'discord.example.com' => 'discord.com',
            'cdn.example.com' => 'cdn.discordapp.com',
            'gateway.example.com' => 'gateway.discord.gg',
        ];
        $host = $request->host(true);
        if (!isset($replace[$host])) {
            return $connection->send(response('404 not found', 404));
        }
        $host = $replace[$host];
        $buffer = (string)$request;
        $con = new AsyncTcpConnection("tcp://$host:443", ['ssl' =>[
            'verify_peer' => false
        ]]);
        $buffer = preg_replace("/Host: ?(.*?)\r\n/", "Host: $host\r\n", $buffer);
        $con->transport = 'ssl';
        $connection->protocol = null;
        $con->send($buffer);
        $con->pipe($connection);
        $connection->pipe($con);
        $con->connect();
    }
}


// https://www.workerman.net/a/1567
//4 设置nginx代理，用于开启https。(以api.example.com为例)
//
//server {
//    server_name api.example.com;
//  listen 80;
//  root /home/www/webman/public;
//  # 这个配置很重要
//  proxy_buffering off;
//
//  # https证书
//  listen 443 ssl;
//  ssl_certificate ssl/xxx.pem;
//  ssl_certificate_key ssl/xxx.key;
//  ssl_session_cache shared:le_nginx_SSL:1m;
//  ssl_session_timeout 1440m;
//  ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
//  ssl_prefer_server_ciphers on;
//  ssl_ciphers "ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA:ECDHE-RSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-RSA-AES256-SHA:ECDHE-ECDSA-DES-CBC3-SHA:ECDHE-RSA-DES-CBC3-SHA:EDH-RSA-DES-CBC3-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA256:AES256-SHA256:AES128-SHA:AES256-SHA:DES-CBC3-SHA:!DSS";
//
//  location ^~ / {
//        proxy_set_header Host $host;
//      proxy_http_version 1.1;
//      proxy_set_header Connection "";
//      if (!-f $request_filename){
//            proxy_pass http://127.0.0.1:8989;
//      }
//  }
//}
//5 设置nginx代理，用于开启wss。(以gateway.example.com为例)
//
//server {
//    server_name gateway.example.com;
//  listen 80;
//  root /home/www/webman/public;
//  # 这个配置很重要
//  proxy_buffering off;
//
//  # https证书
//  listen 443 ssl;
//  ssl_certificate ssl/xxx.pem;
//  ssl_certificate_key ssl/xxx.key;
//  ssl_session_cache shared:le_nginx_SSL:1m;
//  ssl_session_timeout 1440m;
//  ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
//  ssl_prefer_server_ciphers on;
//  ssl_ciphers "ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA:ECDHE-RSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-RSA-AES256-SHA:ECDHE-ECDSA-DES-CBC3-SHA:ECDHE-RSA-DES-CBC3-SHA:EDH-RSA-DES-CBC3-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA256:AES256-SHA256:AES128-SHA:AES256-SHA:DES-CBC3-SHA:!DSS";
//
//  location ^~ / {
//        proxy_set_header Host $host;
//      proxy_http_version 1.1;
//      proxy_set_header Upgrade $http_upgrade;
//      proxy_set_header Connection "Upgrade";
//      if (!-f $request_filename){
//            proxy_pass http://127.0.0.1:8989;
//      }
//  }
//}
