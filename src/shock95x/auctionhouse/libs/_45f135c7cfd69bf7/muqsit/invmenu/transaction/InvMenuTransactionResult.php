<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\invmenu\transaction;

use Closure;
use pocketmine\player\Player;

final class InvMenuTransactionResult{

	/** @var (Closure(Player) : void)|null */
	public ?Closure $post_transaction_callback = null;

	public function __construct(
		readonly public bool $cancelled
	){}

	/**
	 * @deprecated Access {@see InvMenuTransactionResult::$cancelled} directly
	 * @return bool
	 */
	public function isCancelled() : bool{
		return $this->cancelled;
	}

	/**
	 * Notify when we have escaped from the event stack trace and the
	 * client's network stack trace.
	 * Useful for sending forms and other stuff that cant be sent right
	 * after closing inventory.
	 *
	 * @param (Closure(Player) : void)|null $callback
	 * @return self
	 */
	public function then(?Closure $callback) : self{
		$this->post_transaction_callback = $callback;
		return $this;
	}

	/**
	 * @deprecated Access {@see InvMenuTransactionResult::$post_transaction_callback} directly
	 * @return (Closure(Player) : void)|null
	 */
	public function getPostTransactionCallback() : ?Closure{
		return $this->post_transaction_callback;
	}
}