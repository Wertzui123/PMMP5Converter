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
            $contents = convertSkullType($contents);
            $contents = convertReplaceServerBroadcastPackets($contents);
            $contents = convertImmobile($contents);
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
    $content = preg_replace('/api: 4\.\d+(\.\d+)?/', 'api: 5.0.0', $content);
    $content = preg_replace('/(?<=api: \[)[^]]*\K4\.\d+(\.\d+)?(\b|$)/', '5.0.0', $content);
    file_put_contents($path, $content);
}

function convertItemFactory($contents)
{
    $contents = preg_replace_callback('/ItemFactory::getInstance\(\)->get\(ItemIds::([A-Z_]+)\)/', function ($matches) {
        return "VanillaItems::$matches[1]()";
    }, $contents);

    $contents = preg_replace_callback('/ItemFactory::getInstance\(\)->get\(ItemIds::([A-Z_]+), 0\)/', function ($matches) {
        return "VanillaItems::$matches[1]()";
    }, $contents);

    $contents = preg_replace_callback('/ItemFactory::getInstance\(\)->get\(ItemIds::([A-Z_]+), 0, (.*)\)/', function ($matches) {
        return "VanillaItems::$matches[1]()->setCount($matches[2])";
    }, $contents);

    $contents = preg_replace_callback('/ItemFactory::getInstance\(\)->get\((?!ItemIds)(.*?)\)/', function ($matches) {
        $item = $matches[1];
        $commaCount = substr_count($item, ',');
        if ($commaCount < 1) {
            $item .= ', 0';
        }
        if ($commaCount < 2) {
            $item .= ', 1';
        }
        if ($commaCount < 3) {
            $item .= ', null';
        }
        return "GlobalItemDataHandlers::getDeserializer()->deserializeStack(GlobalItemDataHandlers::getUpgrader()->upgradeItemTypeDataInt($item))";
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

    $contents = preg_replace_callback('/BlockFactory::getInstance\(\)->get\(BlockLegacyIds::([A-Z_]+), 0\)/', function ($matches) {
        return "VanillaBlocks::$matches[1]()";
    }, $contents);

    $contents = preg_replace_callback('/BlockFactory::getInstance\(\)->get\(BlockLegacyIds::([A-Z_]+), 0, (.*)\)/', function ($matches) {
        return "VanillaBlocks::$matches[1]()->setCount($matches[2])";
    }, $contents);

    $contents = preg_replace_callback('/BlockFactory::getInstance\(\)->get\((?!BlockLegacyIds)(.*?)\)/', function ($matches) {
        $block = $matches[1];
        $commaCount = substr_count($block, ',');
        if ($commaCount < 1) {
            $block .= ', 0';
        }
        return "GlobalBlockStateHandlers::getDeserializer()->deserialize(GlobalBlockStateHandlers::getUpgrader()->upgradeIntIdMeta($block))";
    }, $contents);

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
    $contents = preg_replace($regex, 'VanillaBlocks::MOB_HEAD()->setMobHeadType(MobHeadType::PLAYER())->asItem()', $contents);
    return $contents;
}

function convertSkullType(string $contents): string
{
    $regex = '/SkullType/';
    $contents = preg_replace($regex, 'MobHeadType', $contents);
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

function convertImmobile(string $contents): string
{
    $regex = '/setImmobile\(\)/';
    $contents = preg_replace($regex, 'setNoClientPredictions()', $contents);
    $regex = '/isImmobile\(\)/';
    $contents = preg_replace($regex, 'hasNoClientPredictions()', $contents);
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

if (preg_match('/(?<=api: \[|)[^]]*\K5\.\d+(\.\d+)?(\b|$)/', file_get_contents(realpath($argv[1]) . '/plugin.yml')) > 0) {
    echo "This plugin is already on API 5 (see plugin.yml)!\n";
    exit(0);
}

if (preg_match('/(?<=api: \[|)[^]]*\K4\.\d+(\.\d+)?(\b|$)/', file_get_contents(realpath($argv[1]) . '/plugin.yml')) === 0) {
    echo "This plugin is not on API 4 (see plugin.yml)!\n";
    exit(0);
}

echo "Converting the plugin " . basename(realpath($argv[1])) . " to PocketMine API 5...\n";
convert(realpath($argv[1]));