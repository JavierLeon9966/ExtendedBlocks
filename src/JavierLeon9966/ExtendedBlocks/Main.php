<?php
declare(strict_types = 1);
namespace JavierLeon9966\ExtendedBlocks;

use pocketmine\Server;
use pocketmine\scheduler\ClosureTask;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Tile;
use pocketmine\item\{Item, ItemBlock};
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\network\mcpe\protocol\{LevelChunkPacket, UpdateBlockPacket, BatchPacket, PacketPool};
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use const pocketmine\RESOURCE_PATH;

use JavierLeon9966\ExtendedBlocks\block\{BlockFactory, Placeholder};
use JavierLeon9966\ExtendedBlocks\item\ItemFactory;
use JavierLeon9966\ExtendedBlocks\tile\Placeholder as PTile;
class Main extends PluginBase implements Listener{
	
	/**
	 * Force registration of the missing runtime ID.
	 *
	 * if the data or Minecraft is updated, this may require modification.
	 * Also, this is not the correct way to do this, as it is forcibly added using reflection classes.
	 *
	 * Credits to: PresentKim
	 */
	private static function registerRuntimeIds(): void{
		$nameToLegacyMap = json_decode(file_get_contents(RESOURCE_PATH."vanilla/block_id_map.json"), true);
		$metaMap = [];
		
		foreach(RuntimeBlockMapping::getBedrockKnownStates() as $runtimeId => $state){
			$name = $state->getString("name");
			if(!isset($nameToLegacyMap[$name])){
				continue;
			}

			$legacyId = $nameToLegacyMap[$name];
			if(!isset($metaMap[$legacyId])){
				$metaMap[$legacyId] = 0;
			}

			$meta = $metaMap[$legacyId]++;
			if($meta > 15){
				continue;
			}

			/** @see RuntimeBlockMapping::registerMapping() */
			$registerMapping = new \ReflectionMethod(RuntimeBlockMapping::class, 'registerMapping');
			$registerMapping->setAccessible(true);
			$registerMapping->invoke(null, $runtimeId, $legacyId, $meta);
		}
		
	}
	public function onLoad(){
		self::registerRuntimeIds();
		Tile::registerTile(PTile::class);
		BlockFactory::init();
		ItemFactory::init();
	}
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	/**
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onEntityInventoryChange(EntityInventoryChangeEvent $event): void{
		$item = $event->getNewItem();
		if($item->getVanillaName() == 'Unknown' and ItemFactory::isRegistered($item->getId())){
			$event->setNewItem(ItemFactory::get($item->getId(), $item->getDamage(), $item->getCount(), $item->getNamedTag()));
		}
	}
	
	/**
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onInventoryTransaction(InventoryTransactionEvent $event){
		foreach($event->getTransaction()->getActions() as $action){
			$item = $action->getTargetItem();
			if($item->getVanillaName() == 'Unknown' and ItemFactory::isRegistered($item->getId())){
				$targetItem = new \ReflectionProperty($action, 'targetItem');
				$targetItem->setAccessible(true);
				$targetItem->setValue($action, ItemFactory::get($item->getId(), $item->getDamage(), $item->getCount(), $item->getNamedTag()));
			}
		}
	}
	/**
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onPlayerLogin(PlayerLoginEvent $event): void{
		$player = $event->getPlayer();
		$player->getInventory()->setContents(array_map(static function(Item $item): Item{
			if($item->getId() > 0){
				return ItemFactory::get($item->getId(), $item->getDamage(), $item->getCount(), $item->getNamedTag());
			}
			return $item;
		}, $player->getInventory()->getContents()));
	}
	/**
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onDataPacketSend(DataPacketSendEvent $event): void{
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		$level = $player->getLevel();
		if($packet instanceof BatchPacket){
			$packet->decode();
			foreach($packet->getPackets() as $buf){
				$pk = PacketPool::getPacket($buf);
				if(!$pk->canBeBatched()){
					throw new \UnexpectedValueException("Received invalid " . get_class($pk) . " inside BatchPacket");
				}
				if($pk instanceof LevelChunkPacket){
					$pk->decode();
					$this->getScheduler()->scheduleDelayedTask(new ClosureTask(static function() use($pk, $player): void{
						if(!$player->isOnline()){
							return;
						}
						$blocks = [];
						$level = $player->getLevelNonNull();
						foreach($level->getChunkTiles($pk->getChunkX(), $pk->getChunkZ()) as $tile){
							$block = $tile->getBlock();
							if($block instanceof Placeholder){
								$blocks[] = $block->getBlock();
							}
						}
						if(count($blocks) > 0){
							$level->sendBlocks([$player], $blocks, UpdateBlockPacket::FLAG_ALL_PRIORITY);
						}
					}), intdiv($player->getPing(), 50) +1);
				}
			}
		}
	}
}