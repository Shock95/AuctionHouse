<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\simplepackethandler;

use InvalidArgumentException;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\simplepackethandler\interceptor\IPacketInterceptor;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\simplepackethandler\interceptor\PacketInterceptor;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\simplepackethandler\monitor\IPacketMonitor;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\simplepackethandler\monitor\PacketMonitor;
use pocketmine\event\EventPriority;
use pocketmine\plugin\Plugin;

final class SimplePacketHandler{

	public static function createInterceptor(Plugin $registerer, int $priority = EventPriority::NORMAL, bool $handle_cancelled = false) : IPacketInterceptor{
		if($priority === EventPriority::MONITOR){
			throw new InvalidArgumentException("Cannot intercept packets at MONITOR priority");
		}
		return new PacketInterceptor($registerer, $priority, $handle_cancelled);
	}

	public static function createMonitor(Plugin $registerer, bool $handle_cancelled = false) : IPacketMonitor{
		return new PacketMonitor($registerer, $handle_cancelled);
	}
}