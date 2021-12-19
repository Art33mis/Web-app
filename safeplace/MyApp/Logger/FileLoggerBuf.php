<?php

namespace MyApp\Logger;

class FileLoggerBuf extends BaseLogger
{
    protected string $fileName;
    protected $fileHandler; // must be resource

    /**
     * FileLoggerBuf constructor.
     * @param string $fileName - название файла
     */
    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
        $this->openFileForLog();

        parent::__construct(LoggerInterface::TYPE_FILE_BUFFER);
    }

    /**
     * Проверяет, что передан ресурс, и устанавливает значение свойства fileHandler
     * @param $fileHandler
     */
    protected function setFileHandler( $fileHandler )
    {
        if( !is_resource( $fileHandler ) ) {
            throw new TypeError('invalid argument, must be a resource for fileHandler');
        }
        $this->fileHandler = $fileHandler;
    }

    protected function openFileForLog()
    {
        $fileHandler = fopen( $this->fileName, 'a' );
        $this->setFileHandler($fileHandler);
    }

    public function logEvent(string $message, string $file, int $line, string $function)
    {

        $logRecordTxt = $this->prepareLogRecord4file( $message, $file, $line, $function );
        fwrite($this->fileHandler, $logRecordTxt);
    }

    protected function prepareLogRecord4file( string $message, string $file, int $line, string $function )
    {
        $lr = $this->prepareLogRecord( $message, $file, $line, $function );
        return "LOG type(".$this->getType().") [".$lr['date']."]: ".$lr['message']." at ".$lr['file']." line ".$lr['line'].PHP_EOL;
    }


    /**
     * Деструктор класса
     * освободит файловый дескриптор
     */
    function __destruct()
    {
        fclose($this->fileHandler);
    }
}


