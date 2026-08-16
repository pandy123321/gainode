<?php

namespace app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;


class MysqlDiff extends Command
{

    protected static $defaultName = 'mysqldiff';
    protected static $defaultDescription = 'mysql对比工具';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('type', InputArgument::OPTIONAL, 'type');
    }

    private function getDevPath($db='mutual_mysql'){
        $data = [
            "host" => "127.0.0.1",
            "user" => "root",
            "pwd" => "root",
            "db" => $db
        ];
        return '-1 '.$data['user'].':'.$data['pwd'].'@'.$data['host'].'~'.$data['db'].'#3306';
    }

    //ssh -i ~/.ssh/aliyun_mutual_dropshipping.pem -o ServerAliveInterval=60 -N -L 13306:127.0.0.1:3306 root@8.136.82.156
    private function getTestPath($db='mutual_mysql'){
        $data = [
            "host" => "8.136.82.156",
            "user" => "dropshipping",
            "pwd" => "MutualDropshipping",
            "db" => $db
        ];
        return '-2 '.$data['user'].':'.$data['pwd'].'@'.$data['host'].'~'.$data['db'].'#3306';
    }

    //ssh -i ~/.ssh/dropshipping.pem -o ServerAliveInterval=60 -N -L 23306:127.0.0.1:3306 root@47.252.12.67
    private function getProdPath($db='dropshipping_mysql'){
        $data = [
            "host" => "127.0.0.1",
            "user" => "dropshipping",
            "pwd" => "MutualDropshipping",
            "db" => $db
        ];
        return '-2 '.$data['user'].':'.$data['pwd'].'@'.$data['host'].'~'.$data['db'].'#23306';
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument("type");
        if(empty($type)){
            $output->writeln("请输入匹配的类型");
            return self::FAILURE;
        }
        if($type=='test'){
            $command = '/usr/local/mysqldiff/mysqldiff '.$this->getDevPath().' '.$this->getTestPath();
            $output->writeln($command.PHP_EOL);
            $res = @shell_exec($command);
            file_put_contents(runtime_path('change.sql'), $res);
            echo $res;
        }
        elseif($type=='pre'){
            $command = '/usr/local/mysqldiff/mysqldiff '.$this->getTestPath().' '.$this->getProdPath();
            $output->writeln($command.PHP_EOL);
            $res = @shell_exec($command);
            file_put_contents(runtime_path('change.sql'), $res);
            echo $res;
        }
        elseif($type=='prod'){
            $command = '/usr/local/mysqldiff/mysqldiff '.$this->getDevPath().' '.$this->getProdPath();
            $output->writeln($command.PHP_EOL);
            $res = @shell_exec($command);
            file_put_contents(runtime_path('change.sql'), $res);
            echo $res;
        }
        return self::SUCCESS;
    }

}
