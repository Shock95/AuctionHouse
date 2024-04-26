<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\invmenu\type\graphic\network;

use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\invmenu\session\InvMenuInfo;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\invmenu\session\PlayerSession;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;

interface InvMenuGraphicNetworkTranslator{

	public function translate(PlayerSession $session, InvMenuInfo $current, ContainerOpenPacket $packet) : void;
}