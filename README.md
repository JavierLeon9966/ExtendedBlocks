# ExtendedBlocks [![Poggit-CI](https://poggit.pmmp.io/shield.dl/ExtendedBlocks)](https://poggit.pmmp.io/p/ExtendedBlocks)
This plugin adds the compatibility to add more blocks above the 255 ID in Pocketmine API 3.0.0+

This branch of ExtendedBlocks transforms every extended blocks into a normal PocketMine 4.0 block. So you don't need worry about your Maps getting 'update!' Blocks.

## Note
If you added a custom tile implementing `PlaceholderInterface` make sure you register it first before loading the chunks or the blocks using that tile will become an 'update!' Block.