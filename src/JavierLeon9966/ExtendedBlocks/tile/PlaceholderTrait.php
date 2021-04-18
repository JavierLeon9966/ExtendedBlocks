<?php
declare(strict_types = 1);
namespace JavierLeon9966\ExtendedBlocks\tile;
use pocketmine\block\{Block, BlockFactory, VanillaBlocks};
use pocketmine\nbt\tag\CompoundTag;
trait PlaceholderTrait{
	protected Block $block;
	protected function loadBlock(CompoundTag $nbt): void{
		$block = $nbt->getCompoundTag("Block");
		if($block !== null){
			$this->block = BlockFactory::getInstance()->get($block->getShort("id"), $block->getByte("meta"));
		}
	}
	protected function saveBlock(CompoundTag $nbt): void{
	}
	public function getBlock(bool $extended = false): Block{
		if(!$extended){
			return parent::getBlock();
		}
		return $this->block ??= VanillaBlocks::RESERVED6();
	}
}