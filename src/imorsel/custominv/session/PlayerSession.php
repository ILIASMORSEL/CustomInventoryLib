<?php

/*
 * PlayerSession — Per-player inventory session handler.
 *
 * Core of the library. Manages fake block placement behind the player,
 * NBT data for chest pairing, ContainerOpen packet interception, and
 * restoring original blocks on inventory close.
 *
 * Key feature: automatically detects client GameVersion from login data
 * and selects the correct chest pair axis so that double chests render
 * properly on both legacy (1.21.x) and modern (26.x+) Bedrock clients.
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

namespace imorsel\custominv\session;

use imorsel\custominv\inventory\CustomInv;
use imorsel\custominv\type\InvType;
use pocketmine\block\tile\Spawnable;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;
use pocketmine\world\World;

final class PlayerSession{

    private ?CustomInv $current = null;
    private ?Position $primaryHolder = null;
    private ?Position $secondaryHolder = null;
    private bool $isLegacyClient;

    public function __construct(
        private Player $player,
        private Plugin $plugin
    ){
        $gameVersion = $player->getPlayerInfo()->getExtraData()['GameVersion'] ?? '0.0.0';
        $this->isLegacyClient = version_compare($gameVersion, '1.21.200', '<');
        $this->registerContainerCallback();
    }

    private function registerContainerCallback() : void{
        $callbacks = $this->player->getNetworkSession()->getInvManager()?->getContainerOpenCallbacks();
        if($callbacks === null) return;

        $callbacks->add(function(int $id, \pocketmine\inventory\Inventory $inv) : ?array{
            if(!$inv instanceof CustomInv) return null;
            $holder = $this->primaryHolder;
            if($holder === null) return null;

            $windowType = match($inv->getType()){
                InvType::HOPPER => WindowTypes::HOPPER,
                default => WindowTypes::CONTAINER,
            };

            return [
                ContainerOpenPacket::blockInv($id, $windowType, BlockPosition::fromVector3($holder))
            ];
        });
    }

    public function open(CustomInv $inv) : void{
        if($this->current !== null){
            $this->restoreBlocks();
        }

        $pos = $this->player->getPosition();
        $vec = $pos->add(0, -3, 0);

        if($vec->y < World::Y_MIN) $vec = $vec->add(0, 1, 0);
        elseif($vec->y > World::Y_MAX) $vec = $vec->add(0, -1, 0);

        $primary = new Position((int)$vec->x, (int)$vec->y, (int)$vec->z, $pos->getWorld());
        $this->primaryHolder = $primary;

        $blockStateId = match($inv->getType()){
            InvType::HOPPER => VanillaBlocks::HOPPER()->getStateId(),
            default => VanillaBlocks::CHEST()->getStateId(),
        };

        $nbt = CompoundTag::create()
            ->setString('CustomName', $inv->getTitle())
            ->setInt('x', $primary->x)
            ->setInt('y', $primary->y)
            ->setInt('z', $primary->z);

        if($inv->getType()->isDouble()){
            $secondary = $this->getPairPosition($primary);
            $this->secondaryHolder = $secondary;

            $nbt->setInt('pairx', $secondary->x)->setInt('pairz', $secondary->z);

            $secondaryNbt = CompoundTag::create()
                ->setString('CustomName', $inv->getTitle())
                ->setInt('x', $secondary->x)
                ->setInt('y', $secondary->y)
                ->setInt('z', $secondary->z)
                ->setInt('pairx', $primary->x)
                ->setInt('pairz', $primary->z);

            $this->sendBlock($secondary, $blockStateId, new CacheableNbt($secondaryNbt));
        }

        $this->sendBlock($primary, $blockStateId, new CacheableNbt($nbt));
        $this->current = $inv;

        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($inv) : void{
            if($this->current !== $inv) return;
            $this->player->setCurrentWindow($inv);
            $handler = $inv->getOpenHandler();
            if($handler !== null){
                $handler($this->player, $inv);
            }
        }), 8);
    }

    private function getPairPosition(Position $primary) : Position{
        $yaw = fmod($this->player->getLocation()->getYaw(), 360);
        if($yaw < 0) $yaw += 360;

        if($this->isLegacyClient){
            if(($yaw >= 45 && $yaw < 135) || ($yaw >= 225 && $yaw < 315)){
                return new Position($primary->x, $primary->y, $primary->z + 1, $primary->getWorld());
            }
            return new Position($primary->x, $primary->y, $primary->z - 1, $primary->getWorld());
        }

        return new Position($primary->x + 1, $primary->y, $primary->z, $primary->getWorld());
    }

    public function close(CustomInv $inv) : void{
        if($this->current !== $inv) return;
        $current = $this->player->getCurrentWindow();
        if($current === $inv){
            $this->player->removeCurrentWindow();
        } else {
            $this->onInventoryClosed($inv);
        }
    }

    public function onInventoryClosed(CustomInv $inv) : void{
        if($this->current !== $inv) return;
        $this->restoreBlocks();
        $this->current = null;
    }

    public function onPlayerQuit() : void{
        if($this->current !== null){
            $this->restoreBlocks();
        }
    }

    private function restoreBlocks() : void{
        if($this->primaryHolder === null) return;
        $world = $this->primaryHolder->getWorld();

        $this->restoreBlock($this->primaryHolder);
        if($this->secondaryHolder !== null){
            $this->restoreBlock($this->secondaryHolder);
        }

        $this->primaryHolder = null;
        $this->secondaryHolder = null;
    }

    private function restoreBlock(Position $pos) : void{
        $world = $pos->getWorld();
        $block = $world->getBlock($pos);
        $stateId = $block->getStateId();
        $nbt = null;
        $tile = $world->getTile($pos);
        if($tile instanceof Spawnable){
            $nbt = $tile->getSerializedSpawnCompound(
                $this->player->getNetworkSession()->getTypeConverter()
            );
        }
        $this->sendBlock($pos, $stateId, $nbt);
    }

    private function sendBlock(Vector3 $pos, int $stateId, ?CacheableNbt $nbt = null) : void{
        $blockPos = BlockPosition::fromVector3($pos);
        $networkId = $this->player->getNetworkSession()
            ->getTypeConverter()
            ->getBlockTranslator()
            ->internalIdToNetworkId($stateId);

        $this->player->getNetworkSession()->sendDataPacket(
            UpdateBlockPacket::create($blockPos, $networkId, UpdateBlockPacket::FLAG_NETWORK, UpdateBlockPacket::DATA_LAYER_NORMAL)
        );

        if($nbt !== null){
            $this->player->getNetworkSession()->sendDataPacket(
                BlockActorDataPacket::create($blockPos, $nbt)
            );
        }
    }

    public function isLegacyClient() : bool{
        return $this->isLegacyClient;
    }

    public function getCurrent() : ?CustomInv{
        return $this->current;
    }

    public function getPrimaryHolder() : ?Position{
        return $this->primaryHolder;
    }
}
