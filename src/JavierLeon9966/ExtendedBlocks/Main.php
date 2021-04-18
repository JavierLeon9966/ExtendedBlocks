<?php
declare(strict_types = 1);
namespace JavierLeon9966\ExtendedBlocks;

use pocketmine\event\Listener;
use pocketmine\event\chunk\ChunkLoadEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\TileFactory;

use JavierLeon9966\ExtendedBlocks\tile\{Placeholder as PTile, PlaceholderInterface};
class Main extends PluginBase{
	public function onLoad(): void{
		TileFactory::getInstance()->register(PTile::class);
	}
	public function onEnable(): void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority MONITOR
	 */
	public function onChunkLoad(ChunkLoadEvent $event): void{
		foreach($event->getChunk()->getTiles() as $tile){
			if($tile instanceof PlaceholderInterface){
				$event->getWorld()->setBlock($tile->getPos(), $tile->getBlock(true));
			}
		}
	}
}
