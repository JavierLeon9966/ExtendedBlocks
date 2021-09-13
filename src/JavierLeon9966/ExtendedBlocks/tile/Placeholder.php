<?php
declare(strict_types = 1);
namespace JavierLeon9966\ExtendedBlocks\tile;
use pocketmine\block\tile\Tile;
use pocketmine\nbt\tag\CompoundTag;
class Placeholder extends Tile implements PlaceholderInterface{
	use PlaceholderTrait;
	public function readSaveData(CompoundTag $nbt): void{
		$this->loadBlock($nbt);
	}
	protected function writeSaveData(CompoundTag $nbt): void{
		$this->saveBlock($nbt);
	}
}