<?php
declare(strict_types = 1);
namespace JavierLeon9966\ExtendedBlocks\block;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\Player;
use JavierLeon9966\ExtendedBlocks\item\ItemFactory;
trait PlaceholderTrait{
	public function getItemId(): int{
		return $this->itemId ?? $this->id > 255 ? 255 - $this->id : $this->id;
	}
	public function canBePlaced(): bool{
		return $this->getRuntimeId() != Block::get(Block::INFO_UPDATE)->getRuntimeId();
	}
	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool{
		return $this->getLevelNonNull()->setBlock($this, new Placeholder($this), true);
	}
	public function getDropsForCompatibleTool(Item $item): array{
		return [ItemFactory::get($this->getItemId(), $this->getVariant())];
	}
	public function getSilkTouchDrops(Item $item): array{
		return [ItemFactory::get($this->getItemId(), $this->getVariant())];
	}
	public function getPickedItem(): Item{
		return ItemFactory::get($this->getItemId(), $this->getVariant());
	}
}