<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\utils;

use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Sign;

class AHSign extends Sign {

	const TYPE_SHOP = "auctionhouse-menu";
	const TYPE_PLAYER = "auctionhouse-player";

	private string $type = "";
	private string $value = "";

	public function __construct(Level $level, CompoundTag $nbt) {
		parent::__construct($level, $nbt);
	}

	protected function readSaveData(CompoundTag $nbt): void {
		parent::readSaveData($nbt);
		$this->type = $nbt->getString("type");
		$this->value = $nbt->getString("value");
	}

	protected function writeSaveData(CompoundTag $nbt): void {
		parent::writeSaveData($nbt);
		$nbt->setString("type", $this->type);
		$nbt->setString("value", $this->value);

	}

	public function addAdditionalSpawnData(CompoundTag $nbt): void {
		parent::addAdditionalSpawnData($nbt);
		$nbt->setString(self::TAG_ID, self::SIGN);
	}

	public static function createTag(Vector3 $position, string $type, string $value = ""): CompoundTag {
		$nbt = self::createNBT($position);
		$nbt->setString("type", $type);
		$nbt->setString("value", $value);
		return $nbt;
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getValue(): string {
		return $this->value;
	}
}