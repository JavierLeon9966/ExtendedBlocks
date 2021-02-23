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
    public function onDataPacketSend(DataPacketSendEvent $event): void{
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $level = $player->getLevel();
        
        if($packet instanceof LevelChunkPacket){
            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(static function() use($packet, $player, $level): void{
                $blocks = [];
                for($x = $packet->getChunkX() << 4; $x < ($packet->getChunkX() + 1) << 4; ++$x){
                    for($z = $packet->getChunkZ() << 4; $z < ($packet->getChunkZ() + 1) << 4; ++$z){
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
        }elseif($packet instanceof BatchPacket){
            foreach($packet->getPackets() as $buf){
                $pk = PacketPool::getPacket($buf);
                if($pk instanceof LevelChunkPacket){
                    $this->getScheduler()->scheduleDelayedTask(new ClosureTask(static function() use($pk, $player, $level): void{
                        $blocks = [];
                        for($x = $pk->getChunkX() << 4; $x < ($pk->getChunkX() + 1) << 4; ++$x){
                            for($z = $pk->getChunkZ() << 4; $z < ($pk->getChunkZ() + 1) << 4; ++$z){
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
