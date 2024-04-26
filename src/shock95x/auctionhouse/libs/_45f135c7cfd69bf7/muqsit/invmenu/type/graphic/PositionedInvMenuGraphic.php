<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\invmenu\type\graphic;

use pocketmine\math\Vector3;

interface PositionedInvMenuGraphic extends InvMenuGraphic{

	public function getPosition() : Vector3;
}