<?php
declare(strict_types = 1);
namespace JavierLeon9966\ExtendedBlocks\block;
use pocketmine\tile\Tile;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\block\Block;
use pocketmine\block\Reserved6;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\nbt\tag\{CompoundTag, ShortTag, ByteTag};
use JavierLeon9966\ExtendedBlocks\tile\{Placeholder as PTile, PlaceholderInterface};
class Placeholder extends Block{
    protected $tile = null;
    private static $blocks = [];
    public function __construct(Block $block = null, Tile $tile = null){
        self::$blocks[] = $block = $block ?? new Reserved6(255, 0, "Reserved6");
        parent::__construct(255, $block->getDamage(), $block->getName(), $block->getItemId());
        if($block->isValid()){
            $this->position($block);
            if($tile === null){
                $nbt = PTile::createNBT($this);
                $nbt->setTag(new CompoundTag("Block", [
                    new ShortTag("id", $block->getId()),
                    new ByteTag("meta", $block->getDamage())
                ]));
                $tile = Tile::createTile("Placeholder", $this->getLevel(), $nbt);
            }
            assert($tile instanceof PlaceholderInterface);
            $nbt = $tile->getCleanedNBT();
            $nbt->setTag(new CompoundTag("Block", [
                new ShortTag("id", $block->getId()),
                new ByteTag("meta", $block->getDamage())
            ]));
            $readSaveData = new \ReflectionMethod($tile, 'readSaveData');
            $readSaveData->setAccessible(true);
            $readSaveData->invoke($tile, $nbt);
            $this->tile = $tile;
        }
    }
    public function checkTile(): void{
        if($this->tile === null){
            if($this->isValid()){
                if(($tile = $this->getLevel()->getTile($this)) instanceof PlaceholderInterface){
                    $this->tile = $tile;
                }
            }
        }
    }
    public function getBlock(): Block{
        $this->checkTile();
        if(!$this->tile){
            return new Reserved6(255, 0, "Reserved6");
        }
        return $this->tile->getBlock(true);
    }
    public function getName(): string{
        return $this->getBlock()->getName();
    }
    public function getRuntimeId(): int{
        return $this->getBlock()->getRuntimeId();
    }
    public function getVariantBitmask() : int{
		return $this->getBlock()->getVariantBitmask();
	}
	public function getVariant() : int{
		return $this->getBlock()->getVariant();
	}
	public function canBeReplaced() : bool{
		return $this->getBlock()->canBeReplaced();
	}
	public function isBreakable(Item $item) : bool{
		return $this->getBlock()->isBreakable($item);
	}
	public function getToolType() : int{
		return $this->getBlock()->getToolType();
	}
	public function getToolHarvestLevel() : int{
		return $this->getBlock()->getToolHarvestLevel();
	}
	public function onBreak(Item $item, Player $player = null) : bool{
	    return $this->getBlock()->onBreak($item, $player);
	}
	public function getBreakTime(Item $item) : float{
	    return $this->getBlock()->getBreakTime($item);
	}
	public function onNearbyBlockChange() : void{
	    $this->getBlock()->onNearbyBlockChange();
	}
	public function ticksRandomly() : bool{
		return true;
	}
	public function onRandomTick() : void{
	    foreach(self::$blocks as $block){
	        if($block->isValid()){
	            if($block->getLevel()->getBlock($block) instanceof $this){
	                if($block->ticksRandomly()){
	                    $block->onRandomTick();
	                }
	            }
	        }
	    }
	}
	public function onScheduledUpdate() : void{
	    $this->getBlock()->onScheduledUpdate();
	}
	public function onActivate(Item $item, Player $player = null) : bool{
		return $this->getBlock()->onActivate($item, $player);
	}
	//TODO
    public function getBlastResistance(): float{
        return 18000000;
    }
	public function getFrictionFactor() : float{
		return $this->getBlock()->getFrictionFactor();
	}
	public function isTransparent() : bool{
	    return true;
	}
	public function canBeFlowedInto() : bool{
		return $this->getBlock()->canBeFlowedInto();
	}
	public function hasEntityCollision() : bool{
		return $this->getBlock()->hasEntityCollision();
	}
	public function canPassThrough() : bool{
		return $this->getBlock()->canPassThrough();
	}
	public function canClimb() : bool{
		return $this->getBlock()->canClimb();
	}
	public function addVelocityToEntity(Entity $entity, Vector3 $vector) : void{
	    $this->getBlock()->addVelocityToEntity($entity, $vector);
	}
	public function getDrops(Item $item): array{
	    return $this->getBlock()->getDrops($item);
	}
	public function getDropsForCompatibleTool(Item $item) : array{
	    return $this->getBlock()->getDropsForCompatibleTool($item);
	}
	public function getSilkTouchDrops(Item $item) : array{
	    return $this->getBlock()->getSilkTouchDrops($item);
	}
	public function getXpDropForTool(Item $item) : int{
	    return $this->getBlock()->getXpDropForTool($item);
	}
	public function isAffectedBySilkTouch() : bool{
		return $this->getBlock()->isAffectedBySilkTouch();
	}
	public function getFlameEncouragement() : int{
	    return $this->getBlock()->getFlameEncouragement();
	}
	public function getFlammability() : int{
		return $this->getBlock()->getFlammability();
	}
	public function burnsForever() : bool{
		return $this->getBlock()->burnsForever();
	}
	public function onIncinerate() : void{
	    $this->getBlock()->onIncinerate();
	}
	public function getAffectedBlocks() : array{
		return $this->getBlock()->getAffectedBlocks();
	}
	public function onEntityCollide(Entity $entity) : void{
	    $this->getBlock()->onEntityCollide($entity);
	}
	public function getCollisionBoxes() : array{
	    return $this->getBlock()->getCollisionBoxes();
	}

	public function getBoundingBox() : ?AxisAlignedBB{
	    return $this->getBlock()->getBoundingBox();
	}
}