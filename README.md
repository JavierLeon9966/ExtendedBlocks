# ExtendedBlocks
This plugin adds the compatibility to add more blocks above the 255 ID in Pocketmine API 3

# How it works?
This plugin uses a Tile Entity which saves the registered blocks from another plugin (Soon config) and a Placeholder block which replaces the Reserved6 that makes instance with the block you want that uses PlaceholderTrait and gives info to the server.

It checks when a LevelChunkPacket is sent so the block would be valid and visible to the player that is within that chunk.

# How can I use it?
For now you would need to use another plugin that register the blocks itself.

For the blocks to be able to register correctly you must add `use JavierLeon9966\ExtendedBlocks\block\PlaceholderTrait;` in the block class.

Soon I'll be able to add blocks in a config. But for now it's just like a API plugin.

# How can I register blocks?
It's recommended to use this method when the plugin starts loading (`onLoad`).

`JavierLeon9966\ExtendedBlocks\block\BlockFactory::registerBlock($block)`
