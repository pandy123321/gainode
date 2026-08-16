<?php

namespace support\translate;

/**
 * 创建基础类
 */
interface TranslateInterface
{

    public function getLanguageList();

    public function translateText(string $text,string $targetLanguage='en',$sourceLanguage='auto');

    public function translateArrayText(array $texts,string $targetLanguage='en',$sourceLanguage='auto');
}
