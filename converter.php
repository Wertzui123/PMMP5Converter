<?php

function convert($path)
{
    foreach (glob($path . '/*') as $file) {
        if (basename($file) === 'plugin.yml') {
            convertPluginYaml($file);
        } else if (substr($file, -4) === '.php') {
            $contents = file_get_contents($file);
            $contents = convertItemFactory($contents);
            $contents = convertBlockFactory($contents);
            $contents = convertBlockBreakInfo($contents);
            $contents = convertChunkBlocks($contents);
            $contents = convertPreLoginKickReasons($contents);
            $contents = convertPluginPermissions($contents);
            $contents = convertPlayerHeadVanillaItem($contents);
            $contents = convertReplaceServerBroadcastPackets($contents);
            // TODO: Convert EntityLegacyIds? (apparently hardly used in my own code)
            // TODO: Convert BlockPlaceEvent->getBlock()? (apparently hardly used in my own code)
            // TODO: Convert PlayerChatEvent formatting? (apparently hardly used in my own code)
            file_put_contents($file, $contents);
        } else if (is_dir($file)) {
            convert($file);
        }
    }
}

function convertPluginYaml($path)
{
    $content = file_get_contents($path);
    $content = preg_replace('/api: 4.*/', 'api: 5.0.0', $content);
    file_put_contents($path, $content);
}

function convertItemFactory($contents)
{
    $contents = preg_replace_callback('/ItemFactory::getInstance\(\)->get\(ItemIds::([A-Z_]+)\)/', function ($matches) {
        return "VanillaItems::$matches[1]()";
    }, $contents);

    $contents = preg_replace_callback('/ItemFactory::getInstance\(\)->get\((.*)\)/', function ($matches) {
        return "GlobalItemDataHandlers::getDeserializer()->deserializeStack(GlobalItemDataHandlers::getUpgrader()->upgradeItemTypeDataInt($matches[1]))";
    }, $contents);

    $contents = preg_replace_callback('/ItemIds::([A-Z_]+)/', function ($matches) {
        return "ItemTypeIds::$matches[1]";
    }, $contents);

    return $contents;
}

function convertBlockFactory($contents)
{
    $contents = preg_replace_callback('/BlockFactory::getInstance\(\)->get\(BlockLegacyIds::([A-Z_]+)\)/', function ($matches) {
        return "VanillaBlocks::$matches[1]()";
    }, $contents);

    $contents = preg_replace_callback('/BlockFactory::getInstance\(\)->get\((.*)\)/', function ($matches) {
        return "GlobalBlockDataHandlers::getDeserializer(GlobalBlockDataHandlers::getUpgrader()->upgradeBlockTypeDataInt($matches[1]))";
    }, $contents); // TODO: This could introduce bugs in situations like `BlockFactory::getInstance()->get(BlockLegacyIds::XY, $meta)`

    $contents = preg_replace_callback('/BlockLegacyIds::([A-Z_]+)/', function ($matches) {
        return "BlockTypeIds::$matches[1]";
    }, $contents);

    $regex = '/BlockTypeIds::LAPIS_ORE/';
    $contents = preg_replace($regex, 'BlockTypeIds::LAPIS_LAZULI_ORE', $contents);

    $regex = '/BlockTypeIds::QUARTZ_ORE/';
    $contents = preg_replace($regex, 'BlockTypeIds::NETHER_QUARTZ_ORE', $contents);

    return $contents;
}

function convertBlockBreakInfo($contents)
{
    $contents = preg_replace_callback('/new BlockBreakInfo\((.*)\)/', function ($matches) {
        return "new BlockTypeInfo(new BlockBreakInfo($matches[1]))";
    }, $contents); // TODO: This could introduce bugs when a BlockBreakInfo is created in other contexts
    return $contents;
}

function ConvertChunkBlocks(string $contents): string
{
    $contents = preg_replace_callback('/getFullBlock\((.*)\)/', function ($matches) {
        return "getBlockStateId($matches[1])";
    }, $contents); // TODO: This could introduce bugs when there is another method called "getFullBlock"
    return $contents;
}

function ConvertPreLoginKickReasons(string $contents): string
{
    $regex = '/PlayerPreLoginEvent::KICK_REASON_BANNED/';
    $contents = preg_replace($regex, 'PlayerPreLoginEvent::KICK_FLAG_BANNED', $contents);
    $regex = '/PlayerPreLoginEvent::KICK_REASON_PLUGIN/';
    $contents = preg_replace($regex, 'PlayerPreLoginEvent::KICK_FLAG_PLUGIN', $contents);
    $regex = '/PlayerPreLoginEvent::KICK_REASON_PRIORITY/';
    $contents = preg_replace($regex, 'PlayerPreLoginEvent::KICK_FLAG_PRIORITY', $contents);
    $regex = '/PlayerPreLoginEvent::KICK_REASON_SERVER_FULL/';
    $contents = preg_replace($regex, 'PlayerPreLoginEvent::KICK_FLAG_SERVER_FULL', $contents);
    $regex = '/PlayerPreLoginEvent::KICK_REASON_SERVER_WHITELISTED/';
    $contents = preg_replace($regex, 'PlayerPreLoginEvent::KICK_FLAG_SERVER_WHITELISTED', $contents);
    $regex = '/clearAllKickReasons\(\)/';
    $contents = preg_replace($regex, 'clearAllKickFlags()', $contents);
    $contents = preg_replace_callback('/clearKickReason\((.*)\)/', function ($matches) {
        return "clearKickFlag($matches[1])";
    }, $contents);
    $contents = preg_replace_callback('/getKickReason\((.*)\)/', function ($matches) {
        return "getKickFlag($matches[1])";
    }, $contents);
    $contents = preg_replace_callback('/setKickReason\((.*)\)/', function ($matches) {
        return "setKickFlag($matches[1])";
    }, $contents);
    $contents = preg_replace_callback('/isKickReasonSet\((.*)\)/', function ($matches) {
        return "isKickFlagSet($matches[1])";
    }, $contents);
    $regex = '/getFinalKickMessage\(\)/';
    $contents = preg_replace($regex, 'getFinalDisconnectReason()', $contents);
    return $contents;
}

function ConvertPluginPermissions(string $contents): string
{
    $contents = preg_replace_callback('/\$this->setPermission\((.*)\)/', function ($matches) {
        return '$this->setPermissions([' . $matches[1] . '])';
    }, $contents);
    $regex = '/\$this->getPermission\(\)/';
    $contents = preg_replace($regex, '$this->getPermissions()[0]', $contents);
    return $contents;
}

function ConvertPlayerHeadVanillaItem(string $contents): string
{
    $regex = '/VanillaItems::PLAYER_HEAD\(\)/';
    $contents = preg_replace($regex,
        'VanillaBlocks::MOB_HEAD()->setSkullType(SkullType::PLAYER())->asItem()', $contents);
    return $contents;
}

function convertReplaceServerBroadcastPackets(string $contents): string
{
    $regex = '/Server::getInstance\(\)->broadcastPackets/';
    $contents = preg_replace($regex, 'NetworkBroadcastUtils::broadcastPackets', $contents);
    $regex = '/\$this->getServer\(\)->broadcastPackets/';
    $contents = preg_replace($regex, 'NetworkBroadcastUtils::broadcastPackets', $contents);
    $regex = '/\$this->plugin->getServer\(\)->broadcastPackets/';
    $contents = preg_replace($regex, 'NetworkBroadcastUtils::broadcastPackets', $contents);
    return $contents;
}

if (!isset($argv[1])) {
    echo "Usage: php converter.php /path/to/your/plugin\n";
    exit(0);
}

if (!file_exists(realpath($argv[1]) . '/plugin.yml')) {
    echo "Missing plugin.yml!\n";
    exit(0);
}

if (!strpos(file_get_contents(realpath($argv[1]) . '/plugin.yml'), 'api: 4')) {
    echo "This plugin is already on API 5 (see plugin.yml)!\n";
    exit(0);
}

echo "Converting the plugin " . basename(realpath($argv[1])) . " to PocketMine API 5...\n";
convert(realpath($argv[1]));