<?php
declare(strict_types = 1);
namespace JavierLeon9966\ExtendedBlocks\item;
use pocketmine\item\ItemFactory as PMFactory;
use pocketmine\item\{Item, ItemBlock, Durable};
use pocketmine\nbt\tag\CompoundTag;
use const pocketmine\RESOURCE_PATH;

use JavierLeon9966\ExtendedBlocks\block\BlockFactory;
class ItemFactory extends PMFactory{
	protected static function getList(){
		static $list = null;
		if($list === null){
			$list = new \ReflectionProperty(parent::class, 'list');
			$list->setAccessible(true);
		}
		return $list->getValue();
	}
	public static function init(): void{
		//Removes limitations as PM 4.0+
		if(!is_array(self::getList())){
			$list = new \ReflectionProperty(parent::class, 'list');
			$list->setAccessible(true);
			$list->setValue(self::getList()->toArray());
		}
	}
	public static function get(int $id, int $meta = 0, int $count = 1, $tags = null): Item{
		if (!is_string($tags) and !$tags instanceof CompoundTag and $tags !== null){
			throw new \TypeError("`tags` argument must be a string or CompoundTag instance, " . (is_object($tags) ? "instance of " . get_class($tags) : gettype($tags)) . " given");
		}
		$listed = self::getList()[$id] ?? null;
		if($listed !== null) $item = clone $listed;
		elseif($id < 256) $item = new ItemBlock($id < 0 ? 255 - $id : $id, $meta, $id);
		else $item = new Item($id, $meta);

		$item->setDamage($meta);
		$item->setCount($count);
		$item->setCompoundTag($tags);
		return $item;
	}
	public static function isRegistered(int $id): bool{
		if($id < 256){
			return BlockFactory::isRegistered($id < 0 ? 255 - $id : $id);
		}
		return parent::isRegistered($id);
	}
	public static function addCreativeItem(Item $item): void{
		if(Item::isCreativeItem($item)) return;
		$creativeItems = json_decode(file_get_contents(RESOURCE_PATH . "vanilla" . DIRECTORY_SEPARATOR . "creativeitems.json"), true);
		foreach($creativeItems as $i => $d){
			if(self::jsonDeserialize($d)->equals($item, !$item instanceof Durable)){
				$items = Item::getCreativeItems();
				$items[$i] = $item;
				$creative = new \ReflectionProperty(Item::class, 'creative');
				$creative->setAccessible(true);
				$creative->setValue($items);
				break;
			}
		}
	}
	private static function jsonDeserialize(array $data): Item{
		$nbt = "";

		//Backwards compatibility
		if(isset($data["nbt"])){
			$nbt = $data["nbt"];
		}elseif(isset($data["nbt_hex"])){
			$nbt = hex2bin($data["nbt_hex"]);
		}elseif(isset($data["nbt_b64"])){
			$nbt = base64_decode($data["nbt_b64"], true);
		}
		return self::get(
			(int)$data["id"],
			(int)($data["damage"] ?? 0),
			(int)($data["count"] ?? 1),
			(string)$nbt
		);
	}
}
