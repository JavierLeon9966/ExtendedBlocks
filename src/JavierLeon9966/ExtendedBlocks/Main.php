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
use pocketmine\network\mcpe\protocol\{LevelChunkPacket, UpdateBlockPacket, MovePlayerPacket, NetworkChunkPublisherUpdatePacket};
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;

use JavierLeon9966\ExtendedBlocks\block\BlockFactory;
use JavierLeon9966\ExtendedBlocks\item\ItemFactory;
use JavierLeon9966\ExtendedBlocks\tile\Placeholder;
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
            $registerMapping->invoke($runtimeId, $legacyId, $meta);
        }
        
    }
    public function onLoad(){
        self::setInstance($this);
        
        self::registerRuntimeIds();
        Tile::registerTile(Placeholder::class);
        BlockFactory::init();
        ItemFactory::init();
    }
    public function onEnable(){
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(): void{
            //Waiting 5 seconds to allow any plugins to register their items
            ItemFactory::initCreativeItems();
            $this->getLogger()->info('Successfully added items/blocks into inventory.');
        }), 100);
        $this->getPluginManager()->registerEvents($this, $this);
    }
    /**
     * @priority MONITOR
     * @ignoreCancelled true
     */
    public function onDataPacketSend(DataPacketSendEvent $event): void{
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $level = $player->getLevel();
        
        if($packet instanceof LevelChunkPacket){
            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(static function() use($packet, $player, $level): void{
                try{
                    $level->sendBlocks([$player], array_map(static function(PlaceholderInterface $placeholder): Placeholder{
                        return $placeholder->getBlock();
                    }, array_filter($level->getChunkTiles($packet->getChunkX(), $packet->getChunkZ()), static function(Tile $tile): bool{
                        return $tile instanceof PlaceholderInterface and $tile->getBlock(true)->isValid() and $tile->getBlock() instanceof Placeholder;
                    })), UpdateBlockPacket::FLAG_ALL_PRIORITY);
                }catch(\Throwable $_){
                    //No extended blocks found
                }
            }), intdiv($player->getPing(), 50) +1);
        }elseif($packet instanceof NetworkChunkPublisherUpdatePacket){
            try{
                $level->sendBlocks([$player], array_map(static function(PlaceholderInterface $placeholder): Placeholder{
                    return $placeholder->getBlock();
                }, array_filter($level->getTiles(), static function(Tile $tile): bool{
                    return in_array($player, $level->getViewersForPosition($tile), true) and $tile instanceof PlaceholderInterface and $tile->getBlock(true)->isValid() and $tile->getBlock() instanceof Placeholder;
                })), UpdateBlockPacket::FLAG_ALL_PRIORITY);
            }catch(\Throwable $_){
                //No extended blocks found
            }
        }elseif($packet instanceof MovePlayerPacket){
            Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(static function() use($level, $player): void{
                try{
                    $level->sendBlocks([$player], array_map(static function(PlaceholderInterface $placeholder): Placeholder{
                        return $placeholder->getBlock();
                    }, array_filter($level->getTiles(), static function(Tile $tile): bool{
                        return in_array($player, $level->getViewersForPosition($tile), true) and $tile instanceof PlaceholderInterface and $tile->getBlock(true)->isValid() and $tile->getBlock() instanceof Placeholder;
                    })), UpdateBlockPacket::FLAG_ALL_PRIORITY);
                }catch(\Throwable $_){
                    //No extended blocks found
                }
            }), intdiv($player->getPing(), 50) +1);
        }
    }
}
