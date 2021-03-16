<?php
declare(strict_types = 1);
namespace JavierLeon9966\ExtendedBlocks\block;
use pocketmine\block\BlockFactory as PMFactory;
use pocketmine\block\UnknownBlock;
class BlockFactory extends PMFactory{
	public static function init(): void{
		self::getBlockStatesArray()->setSize(16384);
		self::$solid->setSize(1024);
		self::$transparent->setSize(1024);
		self::$hardness->setSize(1024);
		self::$light->setSize(1024);
		self::$lightFilter->setSize(1024);
		self::$diffusesSkyLight->setSize(1024);
		self::$blastResistance->setSize(1024);
		self::registerBlock(new Placeholder, true);
		for($id = 0, $size = self::getBlockStatesArray()->getSize() >> 4; $id < $size; ++$id){
			if(!self::getBlockStatesArray()[$id << 4]){
				self::registerBlock(new UnknownBlock($id));
			}
		}
	}
}