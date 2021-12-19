<?php

namespace MyApp\Logger;

abstract class BaseLogger implements LoggerInterface
{
    protected int $type;

    public function __construct(int $type)
    {
        $this->type = $type;
    }

    public function getType(): int
    {
        return $this->type;
    }

    protected final function prepareLogRecord( $message, $file, $line, $function ): array
    {
        return [
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'function' => $function,
            'user' => 1,
            'date' => date('d.m.Y H:m:s', time())
        ];
    }

    /**
     * информация о текущей записи лога в текстовой форме
     */
    protected function getLogTextVerbose( array $lr ): string
    {
        return "LOG type(".$this->getType().") [".$lr['date']."]: ".$lr['message']." at ".$lr['file']." line ".$lr['line'].PHP_EOL;
    }
}
