<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\commands\subcommand;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use shock95x\auctionhouse\menu\category\CategoryListMenu;

class CategoriesCommand extends BaseSubCommand {

	protected function prepare(): void {
		$this->setPermission("auctionhouse.command.category");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if(!$sender instanceof Player) {
			return;
		}
		new CategoryListMenu($sender, false);
	}
}