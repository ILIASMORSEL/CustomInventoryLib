<?php

/*
 * CustomInventory — Public API facade.
 *
 * This is the primary class that plugin developers interact with.
 * Use CustomInventory::create() to build a virtual inventory of any
 * supported type (CHEST, DOUBLE_CHEST, HOPPER) and send it to players.
 * The library handles fake block placement, NBT pairing, client-version
 * detection, and session cleanup automatically.
 *
 *  ██╗██╗     ██╗ █████╗ ███████╗    ███╗   ███╗ ██████╗ ██████╗ ███████╗███████╗██╗
 *  ██║██║     ██║██╔══██╗██╔════╝    ████╗ ████║██╔═══██╗██╔══██╗██╔════╝██╔════╝██║
 *  ██║██║     ██║███████║███████╗    ██╔████╔██║██║   ██║██████╔╝███████╗█████╗  ██║
 *  ██║██║     ██║██╔══██║╚════██║    ██║╚██╔╝██║██║   ██║██╔══██╗╚════██║██╔══╝  ██║
 *  ██║███████╗██║██║  ██║███████║    ██║ ╚═╝ ██║╚██████╔╝██║  ██║███████║███████╗███████╗
 *  ╚═╝╚══════╝╚═╝╚═╝  ╚═╝╚══════╝    ╚═╝     ╚═╝ ╚═════╝ ╚═╝  ╚═╝╚══════╝╚══════╝╚══════╝
 *
 * @author Ilias Morsel
 */

declare(strict_types=1);

namespace imorsel\custominv;

use imorsel\custominv\inventory\CustomInv;
use imorsel\custominv\type\InvType;

final class CustomInventory{

    public static function create(InvType $type, string $title = '') : CustomInv{
        return new CustomInv($type, $title);
    }

    public static function chest(string $title = '') : CustomInv{
        return self::create(InvType::CHEST, $title);
    }

    public static function doubleChest(string $title = '') : CustomInv{
        return self::create(InvType::DOUBLE_CHEST, $title);
    }

    public static function hopper(string $title = '') : CustomInv{
        return self::create(InvType::HOPPER, $title);
    }
}
