<?php

declare(strict_types=1);

namespace Thunder33345\StaffChat\Commands;

use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use Thunder33345\StaffChat\Main;
use Thunder33345\StaffChat\StaffChat;

class StaffChatCommand extends VanillaCommand
{
    const errPerm = TextFormat::RED.'Insufficient Permissions';
    const permChat = 'staffchat.chat';
    const permRead = 'staffchat.read';
    /** @var Main */
    private $plugin;

    public function __construct(StaffChat $plugin)
    {
        parent::__construct("staffchat", "StaffChat Command", "/staffchat", ["staffchat", "sc"]);
        $this->setPermission("staffchat.command");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if(!isset($args[0])) $args[0] = "help";
        switch($args[0]){
            case "help":
                $msgs = [
                    'Staff chat help menu',
                    'say <message> - chat into staff chat',
                    'on - enable chatting mode',
                    'off - disable chatting mode',
                    'toggle - toggle chatting mode',
                    //'config - config command',//maybe latter...
                    'reload - reloads and flushes internal data',
                    'attach <true|false> - attach console into staff chat',
                    'check <player> - checks other player status',
                    //'setl [-fs] <player> <true|false> - sets other players listen status',
                    //'setc [-fs] <player> <true|false> - sets other players chat status',
                    //'-f to forcefully set player status regardless of their permission',
                    //'-s silently sets staffchat status',
                    //'-n dont notify other staff(only for players with insufficient permissions)',
                    'author - show author info',
                    //' - ',
                ];
                foreach($msgs as $msg) $sender->sendMessage(TextFormat::GOLD."Staff Chat Help> ".$msg);
                break;
            case "say":
                if($sender->hasPermission(self::permChat) OR $sender instanceof ConsoleCommandSender){
                    array_shift($args);
                    $this->consoleBroadcast($sender, implode(" ", $args));
                }else $sender->sendMessage(self::errPerm);
                break;

            case "on":
                if($sender->hasPermission(self::permRead)){
                    if($sender instanceof Player){
                        $this->setChatting($sender, true);
                        $sender->sendMessage(TextFormat::GREEN.'[ON] All messages will now go directly into STAFF chat!');
                    }else $sender->sendMessage('Please run this command as player');
                }else $sender->sendMessage(self::errPerm);
                break;
            case "off":
                if($sender->hasPermission(self::permRead)){
                    if($sender instanceof Player){
                        $this->setChatting($sender, false);
                        $sender->sendMessage(TextFormat::GREEN.'[OFF] All messages will now go into NORMAL chat!');
                    }else $sender->sendMessage('Please run this command as player');
                }else $sender->sendMessage(self::errPerm);
                break;
            case "toggle":
                if($sender->hasPermission(self::permRead)){
                    if($sender instanceof Player){
                        $this->setChatting($sender, !$this->isChatting($sender));
                        $sender->sendMessage(TextFormat::GREEN."Staff Chat: ".$this->getReadableState($sender));
                    }else $sender->sendMessage('Please run this command as player');
                }else $sender->sendMessage(self::errPerm);
                break;

            case "reload":
                if(!$sender->hasPermission('staffchat.reload')){
                    $sender->sendMessage(self::errPerm);
                    break;
                }
                $this->flush();
                $sender->sendMessage(TextFormat::GREEN."Successfully flushed internal data...");
                break;
            case "attach":
                if(!$sender->hasPermission('staffchat.attach') AND !$sender instanceof ConsoleCommandSender) $sender->sendMessage(self::errPerm);
                if(isset($args[1])) switch($args[1]){
                    case "on":
                    case "true":
                        $this->setConsoleState(true);
                        $this->getLogger()->notice("Console has been attached to staff chat by ".$sender->getName());
                        break;
                    case "false":
                    case "off":
                        $this->getLogger()->notice("Console has been detach from staff chat by ".$sender->getName());
                        $this->setConsoleState(false);
                        break;
                    default:
                        $sender->sendMessage("/staffchat attach <true|false>");
                        break;
                }else $sender->sendMessage("/staffchat attach <true|false>");
                $sender->sendMessage(TextFormat::GREEN.'Console State: '.$this->getReadableConsoleState());
                break;
            case "check":
                if(!$sender->hasPermission('staffchat.check')){
                    $sender->sendMessage(self::errPerm);
                    return true;
                }
                if(!isset($args[1])){
                    if($sender instanceof Player){
                        $sender->sendMessage('No player provided using yourself instated');
                        $args[1] = $sender->getName();
                    }else{
                        $sender->sendMessage("Please input player's name");
                    }

                }
                if(($player = $this->getServer()->getPlayer($args[1])) === null){
                    $sender->sendMessage('Player "'.$args[1].'" cant be found');
                    return true;
                }
                $sender->sendMessage('Status of "'.$player->getName().'"');
                $sender->sendMessage('Can chat: '.$this->readableTrueFalse($player->hasPermission(self::permChat)), 'yes', 'no');
                $sender->sendMessage('Can read: '.$this->readableTrueFalse($player->hasPermission(self::permRead)), 'yes', 'no');
                $sender->sendMessage('Is chatting: '.$this->getReadableState($player, 'yes', 'no'));
                break;
            case "list":
                if(!$sender->hasPermission('staffchat.list')){
                    $sender->sendMessage(self::errPerm);
                    return true;
                }
                $canChatAndRead = [];
                $canChat = [];
                $canRead = [];
                foreach($this->getServer()->getOnlinePlayers() as $onlinePlayer){
                    if($onlinePlayer->hasPermission(self::permChat) AND $onlinePlayer->hasPermission(self::permRead)){
                        $canChatAndRead[] = $onlinePlayer->getName();
                    }else{
                        if($onlinePlayer->hasPermission(self::permChat)) $canChat[] = $onlinePlayer->getName();
                        if($onlinePlayer->hasPermission(self::permRead)) $canRead[] = $onlinePlayer->getName();
                    }
                }
                $chatting = $this->getChatting();
                $sender->sendMessage('Info List Of Online Players');
                if(count($canChatAndRead) > 0) $sender->sendMessage('Can Chat And Read('.count($canChatAndRead).') :'.implode(',', $canChatAndRead));
                if(count($canChat) > 0) $sender->sendMessage('Can Chat('.count($canChat).') :'.implode(',', $canChat));
                if(count($canRead) > 0) $sender->sendMessage('Can Read('.count($canRead).') :'.implode(',', $canChat));
                if(count($chatting) > 0) $sender->sendMessage('Is Chatting('.count($chatting).'): '.implode(',', $chatting));
                $sender->sendMessage('End Of Info List');
                break;
            case "author":
            case "authors":
            case "credit":
            case "credits":
            case "v":
            case "ver":
            case "version":
                $sender->sendMessage(TextFormat::GREEN.'Staff Chat Running Version: '.$this->getDescription()->getVersion());
                $sender->sendMessage(TextFormat::GREEN.'Staff Chat is created by @Thunder33345');
                $sender->sendMessage(TextFormat::GREEN.'Plugin repo: github.com/ThunderDoesPlugins/StaffChat');
                $sender->sendMessage(TextFormat::GREEN.'My discord server invite is there');
                $sender->sendMessage(TextFormat::GREEN.'My Github: github.com/Thunder33345');
                $sender->sendMessage(TextFormat::GREEN.'My Plugin Github: github.com/ThunderDoesPlugins Go over there to grab some free goodies like this!');
                $sender->sendMessage(TextFormat::GREEN.'My Personal Twitter Account: twitter.com/Thunder33345 or @thunder33345 (feel free to ask for bug fixes!)');
                break;
            default:
                $sender->sendMessage(TextFormat::RED."Command Not Found");
                break;
        }
        return true;
    }

    private function consoleBroadcast(CommandSender $sender, $message)
    {
        if($sender instanceof Player){
            $this->playerBroadcast($sender, $message);
            return;
        }
        if(strlen($this->format) <= 0) $this->format = $this->replaceColour($this->getConfig()->get('player-format'));
        $formatted = str_replace('%player%', $sender->getName(), $this->format);
        $formatted = str_replace('%msg%', $this->replaceColour($message), $formatted);
        $this->rawBroadcast($formatted);
    }

    /**
     * Plugin Broadcast API
     *
     * @param $pluginName string Your plugin name that will be broadcasted to user
     * @param $message string Your message to be sent to users
     * @param string $format Your format, overwrites the defualt user prefered format, you are suggested to leave it as
     *  it is
     */
    public function pluginBroadcast($pluginName, $message, $format = '')
    {
        if(strlen($this->pluginFormat) <= 0) $this->pluginFormat = $this->replaceColour($this->getConfig()->get('plugin-format'));
        if(strlen($format) == 0) $format = $this->pluginFormat;else $format = $this->replaceColour($format);
        $formatted = str_replace('%plugin%', $pluginName, $format);
        $formatted = str_replace('%msg%', $message, $formatted);
        $this->rawBroadcast($formatted);
    }

    /**
     * @return Plugin
     */
    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

}
