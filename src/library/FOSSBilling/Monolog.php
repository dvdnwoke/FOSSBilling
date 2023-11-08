<?php declare(strict_types=1);
/**
 * Copyright 2022-2023 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace FOSSBilling;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

class Monolog
{
    protected $logger = null;
    public string $dateFormat = "d-M-Y H:i:s e";
    public string $outputFormat = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";

    public array $channels = [
        "application",
        "cron",
        "database",
        "license",
        "mail",
        "event"
    ];

    public function __construct()
    {
        $channels = $this->channels;

        foreach ($channels as $channel) {

            $path = PATH_LOG . DIRECTORY_SEPARATOR . $channel . '.log';

            $this->logger[$channel] = new Logger($channel);
            $stream = new StreamHandler($path, Level::Debug);
            $this->logger[$channel]->pushHandler($stream);

            $formatter = new LineFormatter($this->outputFormat, $this->dateFormat, true, true, true);
            $this->logger[$channel]->getHandlers()[0]->setFormatter($formatter);
        }
    }

    /**
     * @return \Monolog\Logger The logger for the specified channel. If the channel does not exist, the default logger (the 'application' channel) is returned.
     */
    public function getChannel(string $channel = 'application'): Logger
    {
        return $this->logger[$channel] ?? $this->logger['application'];
    }

    /**
     * Convert numeric FOSSBilling priority to Monolog priority
     *
     * @return int
     */
    public function parsePriority(int $priority): int
    {
        // Map numeric priority to Monolog priority
        $map = [
            \Box_Log::EMERG => Level::Emergency->value,
            \Box_Log::ALERT => Level::Alert->value,
            \Box_Log::CRIT => Level::Critical->value,
            \Box_Log::ERR => Level::Error->value,
            \Box_Log::WARN => Level::Warning->value,
            \Box_Log::NOTICE => Level::Notice->value,
            \Box_Log::INFO => Level::Info->value,
            \Box_Log::DEBUG => Level::Debug->value,
        ];

        return $map[$priority] ?? Level::Debug->value;
    }

    public function write(array $event, string $channel = 'application'): void
    {
        $priority = $this->parsePriority($event['priority']);
        $message = $event['message'];
        $context = isset($event['info']) && is_array($event['info']) ? $event['info'] : [];

        $this->getChannel($channel)->log($priority, $message, $context);
    }
}
