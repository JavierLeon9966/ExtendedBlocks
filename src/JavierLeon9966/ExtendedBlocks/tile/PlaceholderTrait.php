
declare(strict_types = 1);
namespace JavierLeon9966\ExtendedBlocks\tile;
use pocketmine\block\BlockFactory;
use pocketmine\block\Reserved6;
use pocketmine\block\Block;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\{ByteTag, ShortTag};
trait PlaceholderTrait{
    protected $block = null;
	protected function loadBlock(CompoundTag $nbt): void{
	    $block = $nbt->getCompoundTag("Block");
	    if($block !== null){
	        $this->block = BlockFactory::get($block->getShort("id"), $block->getByte("meta"), $this);
	    }
	}
	protected function saveBlock(CompoundTag $nbt): void{
	    $block = $this->getBlock(true);
	    if($block->isValid()){
	        $nbt->setTag(new CompoundTag("Block", [
	            new ShortTag("id", $block->getId()),
	            new ByteTag("meta", $block->getDamage())
	        ]));
	    }
	}
	public function getCleanedNBT() : ?CompoundTag{
	    $tag = parent::getCleanedNBT();
	    if($tag !== null){
	        $tag->removeTag("Block");
	    }
	    return $tag;
	}
	public function getBlock(bool $real = false): Block{
	    if(!$real) return parent::getBlock();
	    return $this->block ?? new Reserved6(255, 0, "Reserved6");
	}
}