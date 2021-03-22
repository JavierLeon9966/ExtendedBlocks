<?php
declare(strict_types = 1);
namespace JavierLeon9966\ExtendedBlocks\block;
use pocketmine\block\{Solid, BlockToolType};
use pocketmine\item\TieredTool;
class NetheriteBlock extends Solid{
	use PlaceholderTrait;
	protected $id = 525;
	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}
	public function getName(): string{
		return "Netherite Block";
	}
	public function getToolType(): int{
		return BlockToolType::TYPE_PICKAXE;
	}
	public function getToolHarvestLevel(): int{
		return TieredTool::TIER_DIAMOND;
	}
	public function getHardness(): float{
		return 50;
	}
	public function getBlastResistance(): float{
		return 6000;
	}
}