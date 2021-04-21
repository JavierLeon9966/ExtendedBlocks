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
		}
		return $this->variantBitmask;
	}
	public function canBeReplaced(): bool{
		return $this->isReplaceable;
	}
	private static function getSlabVariantBitmask(int $id): int{
		switch($id){
			case self::WOODEN_SLAB:
			case self::STONE_SLAB:
			case self::STONE_SLAB2:
			case 417: //STONE_SLAB3
				return 0x07;
			case 421: //STONE_SLAB4
				return 0x04;
		}
		return 0;
	}
	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool{
		switch($this->type){
			case 'fenceGate':
				$this->meta = ($player instanceof Player ? ($player->getDirection() - 1) & 0x03 : 0);
				break;
			case 'slab':
				$this->meta &= self::getSlabVariantBitmask($this->id);
				if($face == Vector3::SIDE_DOWN or ($face != Vector3::SIDE_UP and $clickVector->y > 0.5)){
					$this->meta |= self::getSlabVariantBitmask($this->id) + 1;
				}
				if($blockReplace instanceof Placeholder and $blockReplace->getBlock()->getId() == $this->id and $blockClicked->getVariant() != $this->getVariant()){
					return false;
				}
				break;
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
				break;
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
		}
		return $this->getLevelNonNull()->setBlock($blockReplace, new Placeholder($this), true);
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
		}
		return false;
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
			return ItemFactory::get((int)($item['id'] ?? $this->getItemId()), (int)($item['meta'] ?? 0));
		}, $this->drops);
	}
	protected function getXpDropAmount(): int{
		return mt_rand((int)$this->xpDrop[0], (int)$this->xpDrop[1]);
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
				$wall = new class extends CobblestoneWall{
					public function canConnect(Block $block){
						return $block instanceof Placeholder and ($custom = $block->getBlock()) instanceof CustomBlock and $custom->getType() == 'wall' or parent::canConnect($block);
					}
				};
				$wall->position($this);
				return $wall->getBoundingBox();
			case 'fence':
				$fence = new class extends Fence{
					public function canConnect(Block $block){
						return $block instanceof Placeholder and ($custom = $block->getBlock()) instanceof CustomBlock and $custom->getType() == 'fence' or parent::canConnect($block);
					}
				};
				$fence->position($this);
				return $fence->getBoundingBox();
			case 'slab':
				if(($this->meta & (self::getSlabVariantBitmask($this->id) + 1)) > 0){
					return new AxisAlignedBB(
						$this->x,
						$this->y + 0.5,
						$this->z,
						$this->x + 1,
						$this->y + 1,
						$this->z + 1
					);
				}
				return new AxisAlignedBB(
					$this->x,
					$this->y,
					$this->z,
					$this->x + 1,
					$this->y + 0.5,
					$this->z + 1
				);
			case 'fenceGate':
				return BlockFactory::get(self::OAK_FENCE_GATE, $this->meta, $this)->getBoundingBox();
			case 'trapDoor':
				return BlockFactory::get(self::TRAPDOOR, $this->meta, $this)->getBoundingBox();
		}
		return parent::recalculateBoundingBox();
	}
	protected function recalculateCollisionBoxes(): array{
		switch($this->type){
			case 'fence':
				$fence = new class extends Fence{
					public function canConnect(Block $block){
						return $block instanceof Placeholder and ($custom = $block->getBlock()) instanceof CustomBlock and $custom->getType() == 'fence' or parent::canConnect($block);
					}
				};
				$fence->position($this);
				return $fence->getCollisionBoxes();
			case 'stair':
				return BlockFactory::get(self::OAK_STAIRS, $this->meta, $this)->getCollisionBoxes();
		}
		return parent::recalculateCollisionBoxes();
	}
}		
