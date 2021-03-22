<?php
namespace JavierLeon9966\ExtendedBlocks\block;
use pocketmine\block\{Block, CobblestoneWall, Fence, FanceGate, Trapdoor};
use pocketmine\level\Position;
use pocketmine\level\sound\DoorSound;
use pocketmine\item\Item;
use pocketmine\math\{AxisAlignedBB, Vector3};
use pocketmine\Player;
use JavierLeon9966\ExtendedBlocks\item\ItemFactory;
final class CustomBlock extends Block{
	use PlaceholderTrait;
	private $variantBitmask = -1;
	private $isReplaceable = false;
	private $breakable = true;
	private $toolType = 0;
	private $toolHarvestLevel = 0;
	private $hardness = 10;
	private $frictionFactor = 0.6;
	private $transparent = false;
	private $solid = true;
	private $isFlowable = false;
	private $entityCollision = true;
	private $passThrough = false;
	private $canClimb = false;
	private $drops = [[]];
	private $xpDrop = [0, 0];
	private $silkTouch = true;
	private $fuelTime = 0;
	private $flameEncouragement = 0;
	private $flammibility = 0;
	private $burnsForever = false;
	private $type = 'normal';
	public function __construct(
		string $name,
		int $id,
		int $meta,
		int $itemId,
		int $variantBitmask,
		bool $isReplaceable,
		bool $breakable,
		int $toolType,
		int $toolHarvestLevel,
		float $hardness,
		float $frictionFactor,
		bool $transparent,
		bool $solid,
		bool $isFlowable,
		bool $entityCollision,
		bool $passThrough,
		bool $canClimb,
		array $drops,
		array $xpDrop,
		bool $silkTouch,
		int $fuelTime,
		int $flameEncouragement,
		int $flammibility,
		bool $burnsForever,
		string $type
	){
		parent::__construct($id, $meta, $name, $itemId);
		$this->variantBitmask = $variantBitmask;
		$this->isReplaceable = $isReplaceable;
		$this->breakable = $breakable;
		$this->toolType = min(32, max(0, $toolType));
		$this->toolHarvestLevel = min(6, min(1, $toolHarvestLevel));
		$this->hardness = $hardness;
		$this->frictionFactor = $frictionFactor;
		$this->transparent = $transparent;
		$this->solid = $solid;
		$this->isFlowable = $isFlowable;
		$this->entityCollision = $entityCollision;
		$this->passThrough = $passThrough;
		$this->canClimb = $canClimb;
		$this->drops = $drops;
		$this->xpDrop = $xpDrop;
		$this->silkTouch = $silkTouch;
		$this->fuelTime = $fuelTime;
		$this->flameEncouragement = $flameEncouragement;
		$this->flammibility = $flammibility;
		$this->burnsForever = $burnsForever;
		$this->type = $type;
	}
	public function getVariantBitmask(): int{
		switch($this->type){
			case 'slab':
			case 'stair':
			case 'trapDoor':
				return 0;
			case 'fenceGate':
			case 'wall':
			case 'fence':
			case 'normal':
			default:
				return $this->variantBitmask;
		}
	}
	public function canBeReplaced(): bool{
		return $this->isReplaceable;
	}
	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool{
		switch($this->type){
			case 'fenceGate':
				$this->meta = ($player instanceof Player ? ($player->getDirection() - 1) & 0x03 : 0);
				$this->getLevelNonNull()->setBlock($blockReplace, new Placeholder($this), true);
				return true;
			case 'slab':
				if($face == Vector3::SIDE_DOWN or ($face != Vector3::SIDE_UP and $clickVector->y > 0.5)){
					$this->meta |= 0x01;
				}
				if($blockReplace instanceof Placeholder and $blockReplace->getBlock()->getId() == $this->id and $blockClicked->getVariant() != $this->getVariant()){
					return false;
				}
				$this->getLevelNonNull()->setBlock($blockReplace, new Placeholder($this), true);
				return true;
			case 'stair':
				$faces = [
					0 => 0,
					1 => 2,
					2 => 1,
					3 => 3
				];
				$this->meta = $player !== null ? $faces[$player->getDirection()] & 0x03 : 0;
				if(($clickVector->y > 0.5 and $face != Vector3::SIDE_UP) or $face == Vector3::SIDE_DOWN){
					$this->meta |= 0x04; //Upside-down stairs
				}
				$this->getLevelNonNull()->setBlock($blockReplace, new Placeholder($this), true);
				return true;
			case 'trapDoor':
				$directions = [
					0 => 1,
					1 => 3,
					2 => 0,
					3 => 2
				];
				if($player !== null){
					$this->meta = $directions[$player->getDirection() & 0x03];
				}
				if(($clickVector->y > 0.5 and $face != self::SIDE_UP) or $face == self::SIDE_DOWN){
					$this->meta |= Trapdoor::MASK_UPPER; //top half of block
				}
				$this->getLevelNonNull()->setBlock($blockReplace, new Placeholder($this), true);
				return true;
			case 'fence':
			case 'wall':
			case 'normal':
			default:
				return $this->getLevelNonNull()->setBlock($this, new Placeholder($this), true);
		}
	}
	public function isBreakable(Item $item): bool{
		return $this->breakable;
	}
	public function getToolType(): int{
		return $this->toolType;
	}
	public function getToolHarvestLevel(): int{
		return $this->toolHarvestLevel;
	}
	public function onActivate(Item $item, Player $player = null): bool{
		switch($this->type){
			case 'fenceGate':
				$this->meta = (($this->meta ^ 0x04) & ~0x02);

				if($player !== null){
					$this->meta |= (($player->getDirection() - 1) & 0x02);
				}
				$this->getLevelNonNull()->setBlock($this, new Placeholder($this), true);
				$this->level->addSound(new DoorSound($this));
				return true;
			case 'trapDoor':
				$this->meta ^= Trapdoor::MASK_OPENED;
				$this->getLevelNonNull()->setBlock($this, new Placeholder($this), true);
				$this->level->addSound(new DoorSound($this));
				return true;
			case 'stair':
			case 'fence':
			case 'slab':
			case 'wall':
			case 'normal':
			default:
				return false;
		}
	}
	public function getHardness(): float{
		return $this->hardness;
	}
	public function getFrictionFactor(): float{
		return $this->frictionFactor;
	}
	public function isTransparent(): bool{
		return $this->transparent;
	}
	public function isSolid(): bool{
		return $this->solid;
	}
	public function canBeFlowedInto(): bool{
		return $this->isFlowable;
	}
	public function hasEntityCollision(): bool{
		return $this->entityCollision;
	}
	public function canPassThrough(): bool{
		return $this->passThrough;
	}
	public function canClimb(): bool{
		return $this->canClimb;
	}
	public function getDropsForCompatibleTool(Item $item): array{
		return array_map(function($item): Item{
			if(!is_array($item)){
				$item = ['id' => $item];
			}
			return ItemFactory::get(intval($item['id'] ?? $this->getItemId()), intval($item['meta'] ?? 0));
		}, $this->drops);
	}
	protected function getXpDropAmount(): int{
		return mt_rand(intval(@$this->xpDrop[0]), intval(@$this->xpDrop[1]));
	}
	public function isAffectedBySilkTouch(): bool{
		return $this->silkTouch;
	}
	public function getFuelTime(): int{
		return $this->fuelTime;
	}
	public function getFlameEncouragement(): int{
		return $this->flameEncouragement;
	}
	public function getFlammibility(): int{
		return $this->flammibility;
	}
	public function burnsForever(): bool{
		return $this->burnsForever;
	}
	public function getType(): string{
		return $this->type;
	}
	protected function recalculateBoundingBox(): ?AxisAlignedBB{
		switch($this->type){
			case 'wall':
				return (new class($this) extends CobblestoneWall{
					public function __construct(Position $pos){
						$this->position($pos);
					}
					public function canConnect(Block $block){
						return $block instanceof Placeholder and ($custom = $block->getBlock()) instanceof CustomBlock and $custom->getType() == 'wall' or parent::canConnect($block);
					}
				})->getBoundingBox();
			case 'fence':
				return (new class($this) extends Fence{
					public function __construct(Position $pos){
						$this->position($pos);
					}
					public function canConnect(Block $block){
						return $block instanceof Placeholder and ($custom = $block->getBlock()) instanceof CustomBlock and $custom->getType() == 'fence' or parent::canConnect($block);
					}
				})->getBoundingBox();
			case 'fenceGate':
				return BlockFactory::get(self::OAK_FENCE_GATE, $this->getDamage(), $this)->getBoundingBox();
			case 'slab':
				return BlockFactory::get(self::STONE_SLAB, $this->getDamage(), $this)->getBoundingBox();
			case 'trapDoor':
				return BlockFactory::get(self::TRAPDOOR, $this->getDamage(), $this)->getBoundingBox();
			case 'stair':
			case 'slab':
			case 'wall':
			case 'normal':
			default:
				return parent::recalculateBoundingBox();
		}
	}
	protected function recalculateCollisionBoxes(): array{
		switch($this->type){
			case 'fence':
				return (new class($this) extends Fence{
					public function __construct(Position $pos){
						$this->position($pos);
					}
					public function canConnect(Block $block){
						return $block instanceof Placeholder and ($custom = $block->getBlock()) instanceof CustomBlock and $custom->getType() == 'fence' or parent::canConnect($block);
					}
				})->getCollisionBoxes();
			case 'stair':
				return BlockFactory::get(self::OAK_STAIRS, $this->getDamage(), $this)->getCollisionBoxes();
			case 'trapDoor':
			case 'fenceGate':
			case 'slab':
			case 'wall':
			case 'normal':
			default:
				return parent::recalculateCollisionBoxes();
		}
	}
}		
