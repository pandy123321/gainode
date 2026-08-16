<?php

namespace support\make;

/**
 * 创建基础类
 */
interface MakeInterface
{
    /**
     * 获取表名对应的类名
     * @return string
     */
    public function getFileClass(string $name);

    /**
     * 获取表名对应的文件地址
     * @return string
     */
    public function getFilePath(string $name);
    /**
     * 获取所有数据表
     * @return array
     */
    public function getList();

    /**
     * 获取生成的模版数据
     * @return string
     */
    public function getTemplate(string $name);

    /**
     * 创建文件
     * @param string $name
     * @return bool
     */
    public function createFile(string $name);

    /**
     * 获取今天创建的文件列表
     * @param int $days 最近修改时间天数
     * @return array
     */
    public function getTodayList(int $days=1);
}
