<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\invmenu\type\util\builder;

use LogicException;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\invmenu\type\BlockActorFixedInvMenuType;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\invmenu\type\graphic\network\BlockInvMenuGraphicNetworkTranslator;

final class BlockActorFixedInvMenuTypeBuilder implements InvMenuTypeBuilder{
	use BlockInvMenuTypeBuilderTrait;
	use FixedInvMenuTypeBuilderTrait;
	use GraphicNetworkTranslatableInvMenuTypeBuilderTrait;
	use AnimationDurationInvMenuTypeBuilderTrait;

	private ?string $block_actor_id = null;

	public function __construct(){
		$this->addGraphicNetworkTranslator(BlockInvMenuGraphicNetworkTranslator::instance());
	}

	public function setBlockActorId(string $block_actor_id) : self{
		$this->block_actor_id = $block_actor_id;
		return $this;
	}

	private function getBlockActorId() : string{
		return $this->block_actor_id ?? throw new LogicException("No block actor ID was specified");
	}

	public function build() : BlockActorFixedInvMenuType{
		return new BlockActorFixedInvMenuType($this->getBlock(), $this->getSize(), $this->getBlockActorId(), $this->getGraphicNetworkTranslator(), $this->getAnimationDuration());
	}
}