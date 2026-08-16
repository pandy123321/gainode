<?php

namespace app\command;

use support\Db;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * 同步老数据
 */
class Sync extends Command
{
    protected static $defaultName = 'Sync';
    protected static $defaultDescription = 'Sync';


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->syncMember();
        return self::SUCCESS;
    }


    private function syncMember()
    {
        $rows = Db::connection('gainode')->table('hg_admin_member')->where('role_id',210)->get();
        foreach($rows as $v){
            $data = [
                'id' => $v->id,
                'username' => $v->username,
                'password' => $v->password,
                'nickname' => $v->nickname,
                'mobile' => $v->mobile,
                'email' => $v->email,
                'avatar' => $v->avatar,
                'status' => $v->status,
                'created_at' => $v->created_at,
                'updated_at' => $v->updated_at,
            ];
        }
    }
}
