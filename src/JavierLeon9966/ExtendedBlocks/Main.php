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

use JavierLeon9966\ExtendedBlocks\block\{BlockFactory, Placeholder, CustomBlock, NetheriteBlock};
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
		ItemFactory::init();
		BlockFactory::init();
		foreach($this->getConfig()->getAll() as $name => $block){
			BlockFactory::registerBlock(new CustomBlock(
				strval($name),
				intval($block['id']),
				intval($block['meta'] ?? 0),
				intval($block['itemId'] ?? 255 - $block['id']),
				intval($block['variantBitmask'] ?? -1),
				(bool)($block['isReplaceable'] ?? false),
				(bool)($block['breakable'] ?? true),
				intval($block['toolType'] ?? 0),
				intval($block['toolHarvestLevel'] ?? 0),
				(float)($block['hardness'] ?? 10),
				(float)($block['frictionFactor'] ?? 0.6),
				(bool)($block['transparent'] ?? false),
				(bool)($block['solid'] ?? true),
				(bool)($block['isFlowable'] ?? false),
				(bool)($block['entityCollision'] ?? true),
				(bool)($block['passThrough'] ?? false),
				(bool)($block['canClimb'] ?? false),
				(array)($block['drops'] ?? [[]]),
				(array)($block['xpDrop'] ?? [0, 0]),
				(bool)($block['silkTouch'] ?? true),
				intval($block['fuelTime'] ?? 0),
				intval($block['flameEncouragement'] ?? 0),
				intval($block['flammibility'] ?? 0),
				(bool)($block['burnsForever'] ?? false),
				strval($block['type'] ?? 'normal')
			));
			if($block['creativeItem'] ?? true){
				ItemFactory::addCreativeItem(ItemFactory::get(intval($block['itemId'] ?? 255 - $block['id'])));
			}
		}
		BlockFactory::registerBlock(new NetheriteBlock);
		ItemFactory::addCreativeItem(ItemFactory::get(255 - 525));
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
			$blocks = [];
			$packet->decode();
			foreach($packet->getPackets() as $buf){
				$pk = PacketPool::getPacket($buf);
				if($pk instanceof LevelChunkPacket){
					$pk->decode();
					foreach($level->getChunkTiles($pk->getChunkX(), $pk->getChunkZ()) as $tile){
						if($tile->getBlock() instanceof Placeholder){
							$blocks[] = $tile->getBlock(true);
						}
					}
				}
			}
			if(count($blocks) > 0){
				$this->getScheduler()->scheduleDelayedTask(new ClosureTask(static function() use($blocks, $player, $level): void{
					$level->sendBlocks([$player], $blocks, UpdateBlockPacket::FLAG_ALL_PRIORITY);
				}), intdiv($player->getPing(), 50) + 1);
			}
		}
	}
}