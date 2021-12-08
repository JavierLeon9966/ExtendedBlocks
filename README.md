# ExtendedBlocks [![Poggit-CI](https://poggit.pmmp.io/shield.dl/ExtendedBlocks)](https://poggit.pmmp.io/p/ExtendedBlocks)
This plugin adds the compatibility to add more blocks above the 255 ID in Pocketmine API 3.0.0+

## Archived
This repository is archived because PM4 has just released so this plugin is completely pointless. If you wish to convert these blocks into PM4 blocks you should use [ExtendedBlocksConverter](https://github.com/JavierLeon9966/ExtendedBlocksConverter)

## Important
**Warning**: If you remove this plugin after some blocks has been placed it may transform into a Reserved6 block and can't be reversible.
Also this plugin is a temporary solution until PM 4 is fully released so don't except this to work or any updates.

**Note**: This plugin may affect your server performance if too many blocks are in your world. Use this plugin at your own risk!

To update this plugin safely you must stop your server and install the updated version, then you can start the server again.

## How the plugins works
This plugin uses a Tile Entity which saves the registered blocks from another plugin and a Placeholder block which replaces the Reserved6 that makes instance with the block you want that uses PlaceholderTrait and gives info to the server.

It checks when a LevelChunkPacket is sent and finds every tile within the chunk that is a Placeholder block and sends the block to the player.

## How to use the plugin
For now you would need to use another plugin that register the blocks itself.

For the blocks to be able to register correctly you must add this trait in the block class:

```php
use JavierLeon9966\ExtendedBlocks\block\PlaceholderTrait;
```

Soon It'll be able to add new blocks in a configuration, but for now it's just like a API plugin.

It's recommended to use this methods when the plugin starts loading:

```php
use pocketmine\block\BlockFactory;
use pocketmine\item\ItemBlock;
use pocketmine\plugin\PluginBase;
use JavierLeon9966\ExtendedBlocks\item\ItemFactory;
class Plugin extends PluginBase{
  public function onLoad(){
    BlockFactory::registerBlock($block);
    ItemFactory::addCreativeItem(ItemFactory::get(255 - $block->getId())); //Usually most blocks
  }
  ...
}
```

## Format you must use
This is a example of how your class must look for it to work properly

```php
use pocketmine\block\Block; //You can extend any class but be a Block
use JavierLeon9966\ExtendedBlocks\block\PlaceholderTrait;
class Sample extends Block{
  use PlaceholderTrait;
  protected $id = 526; //The id of the block must be positive
  public function __construct(int $meta = 0){ //Optional
    $this->meta = $meta;
  }
  ...
}
```
