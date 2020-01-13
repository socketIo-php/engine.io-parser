<?php
declare(strict_types=1);

namespace EngineIoParser\Enums;

/**
 * Class PacketEnum
 *
 * @package EngineIoParser\Enums
 */
class PacketEnum extends AbstractEnum
{
    /**
     * @message("open")
     */
    public const OPEN = 0;

    /**
     * @message("close")
     */
    public const CLOSE = 1;

    /**
     * @message("ping")
     */
    public const PING = 2;

    /**
     * @message("pong")
     */
    public const PONG = 3;

    /**
     * @message("message")
     */
    public const MESSAGE = 4;

    /**
     * @message("upgrade")
     */
    public const UPGRADE = 5;

    /**
     * @message("noop")
     */
    public const NOOP = 6;
}