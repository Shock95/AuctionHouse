<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\tile;

use pocketmine\block\tile\Sign;
use pocketmine\nbt\tag\CompoundTag;

class AHSign extends Sign{

	const TYPE_SHOP = "auctionhouse-menu";
	const TYPE_PLAYER = "auctionhouse-player";

	private string $type = "";
	private string $value = "";

	public function readSaveData(CompoundTag $nbt) : void{
		parent::readSaveData($nbt);
		$this->type = $nbt->getString("type");
		$this->value = $nbt->getString("value");
	}

	public function writeSaveData(CompoundTag $nbt) : void{
		parent::writeSaveData($nbt);
		$nbt->setString("type", $this->type);
		$nbt->setString("value", $this->value);
	}

	public function addAdditionalSpawnData(CompoundTag $nbt) : void{
		parent::addAdditionalSpawnData($nbt);
		$nbt->setString(self::TAG_ID, "Sign");
	}

	public function setType(string $type) : void{
		$this->type = $type;
	}

	public function getType() : string{
		return $this->type;
	}

	public function setValue(string $value) : void{
		$this->value = $value;
	}

	public function getValue() : string{
		return $this->value;
	}
}
