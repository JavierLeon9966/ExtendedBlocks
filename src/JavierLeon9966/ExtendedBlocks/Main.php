<?php
declare(strict_types = 1);
namespace JavierLeon9966\ExtendedBlocks;

use pocketmine\Server;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Tile;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\network\mcpe\protocol\{LevelChunkPacket, UpdateBlockPacket, BatchPacket, PacketPool};
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;

use JavierLeon9966\ExtendedBlocks\block\{BlockFactory, Placeholder};
use JavierLeon9966\ExtendedBlocks\item\ItemFactory;
use JavierLeon9966\ExtendedBlocks\tile\Placeholder as PTile;
class Main extends PluginBase implements Listener{
    use SingletonTrait;
    private static $registered = false;
    //Allow blocks above lit_blast_furnace to be visible and be valid
    private static function registerRuntimeIds(): void{
        if(self::$registered) return;
        self::$registered = true;
        
        $nameToLegacyMap = json_decode(file_get_contents(Server::getInstance()->getResourcePath()."vanilla/block_id_map.json"), true);
        $metaMap = [];

        /** @see RuntimeBlockMapping::getBedrockKnownStates() */
        foreach(RuntimeBlockMapping::getBedrockKnownStates() as $runtimeId => $state){
            $name = $state->getString("name");
            if(!isset($nameToLegacyMap[$name]))
                continue;

            $legacyId = $nameToLegacyMap[$name];
            if(!isset($metaMap[$legacyId])){
                $metaMap[$legacyId] = 0;
            }

            $meta = $metaMap[$legacyId]++;
            if($meta > 0xf)
                continue;

            /** @see RuntimeBlockMapping::registerMapping() */
            $registerMapping = new \ReflectionMethod(RuntimeBlockMapping::class, 'registerMapping');
            $registerMapping->setAccessible(true);
            $registerMapping->invoke(null, $runtimeId, $legacyId, $meta);
        }
        
    }
    public function onLoad(){
        self::setInstance($this);
        
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
     * @ignoreCancelled true
     * @priority MONITOR
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
            foreach($packet->getPackets() as $buf){
                $pk = PacketPool::getPacket($buf);
                if($pk instanceof LevelChunkPacket){
                    $this->getScheduler()->scheduleDelayedTask(new ClosureTask(static function() use($pk, $player, $level): void{
                        $blocks = [];
                        for($x = (($pk->getChunkX() - (int)($pk->getChunkX() <= 0)) << 4) - (int)($pk->getChunkX() <= 0); $x < (($pk->getChunkX() + (int)($pk->getChunkX() > 0)) << 4) - (int)($pk->getChunkX() <= 0); ++$x){
                            for($z = (($pk->getChunkZ() - (int)($pk->getChunkZ() <= 0)) << 4) - (int)($pk->getChunkZ() <= 0); $z < (($pk->getChunkZ() + (int)($pk->getChunkZ() > 0)) << 4) - (int)($pk->getChunkZ() <= 0); ++$z){
                                for($y = 0; $y <= $level->getWorldHeight(); ++$y){
                                    $block = $level->getBlockAt($x, $y, $z, true, false);
                                    if($block instanceof Placeholder){
                                        $blocks[] = $block;
                                    }
                                }
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
