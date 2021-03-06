<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\utils;

use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\auction\Listing;
use shock95x\auctionhouse\AuctionHouse;

class Utils {

	public static function getEndTime(): int {
		return time() + (Settings::getExpireInterval() * 3600);
	}

	public static function prefixMessage($string): string {
		return str_replace("&", "\xc2\xa7", Settings::getPrefix() . " " . $string);
	}

	public static function isBlacklisted(Item $item): bool {
		foreach(Settings::getBlacklist() as $blacklistedItem) {
			return $item->equals($blacklistedItem, true, false);
		}
		return false;
	}

	public static function canAfford(Player $player, Listing $listing): bool {
		if(AuctionHouse::getInstance()->getEconomyProvider()->getMoney($player) < $listing->getPrice()) {
			return false;
		}
		return true;
	}

	public static function getButtonItem(Player $player, string $itemKey, string $messageKey, array $searchArgs = [], array $replaceArgs = []): Item {
		$item = Item::fromString(Settings::getButtons()[$itemKey]);
		$message = Locale::getMessage($player, $messageKey);

		$item->setCustomName(TextFormat::RESET . str_replace($searchArgs, $replaceArgs, $message["name"]));
		if(isset($message["lore"])) {
			$item->setLore(preg_filter('/^/', TextFormat::RESET, str_replace($searchArgs, $replaceArgs, $message["lore"])));
		}
		return $item;
	}

	public static function getMaxListings(Player $player): int {
		foreach ($player->getEffectivePermissions() as $permission) {
			if(strpos($permission->getPermission(), "auctionhouse.limit") === 0) {
				return (int) explode(".", $permission->getPermission())[2];
			}
		}
		return Settings::getMaxListings();
	}

	public static function sendSound(Player $player, int $sound) {
		$pk = new LevelSoundEventPacket();
		$pk->position = $player->asVector3();
		$pk->sound = $sound;
		$player->dataPacket($pk);
	}

	public static function sendLevelEvent(Player $player, int $evid) {
		$pk = new LevelEventPacket();
		$pk->evid = $evid;
		$pk->position = $player->asVector3()->add(0, 10);
		$pk->data = 0;
		$player->dataPacket($pk);
	}

	public static function checkConfig(Plugin $plugin, Config $config, string $key, int $version): void {
		if($config->get($key) != $version) {
			$path = $config->getPath();
			$info = pathinfo($path);

			$oldFile = $info["filename"] . "_old." . $info["extension"];

			rename($path, $info["dirname"] . "/" . $oldFile);

			$configDir = str_replace($plugin->getDataFolder(), "", $path);

			$plugin->saveResource($configDir);
			$message = "Your {$info["basename"]} file is outdated. Your old {$info["basename"]} has been saved as $oldFile and a new {$info["basename"]} file has been created. Please update accordingly.";

			$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use ($plugin, $message): void{
				$plugin->getLogger()->critical($message);
			}), 1); // should display once the server is done loading
		}
	}
}