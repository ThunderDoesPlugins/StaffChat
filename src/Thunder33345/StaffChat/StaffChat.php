<?php
/** Created By Thunder33345 **/

namespace Thunder33345\StaffChat;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class StaffChat extends PluginBase implements Listener
{
  const permChat = 'staffchat.chat';
  const permRead = 'staffchat.read';
  const errPerm = TextFormat::RED.'Insufficient Permissions';
  private $console = true;
  private $prefix = ".";
  private $format;
  private $chatting = [];

  public function onLoad()
  {

  }

  public function onEnable()
  {
    if(!file_exists($this->getDataFolder())) $this->getDataFolder();
    $this->saveDefaultConfig();
    $this->console = (bool)$this->getConfig()->get('auto-attach',true);
    $this->prefix = $this->getConfig()->get('prefix',".");
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
  }

  public function onDisable()
  {

  }

  private function broadcast($name,$message)
  {
    if(!isset($this->format) OR $this->format === "") $this->format = $this->replaceColour($this->getConfig()->get("format"));
    $formatted = str_replace('%sender%',$name,$this->format);
    $formatted = str_replace('%msg%',$message,$formatted);
    foreach($this->getServer()->getOnlinePlayers() as $player){
      if(!$player->hasPermission('staffchat.read')) continue;
      $player->sendMessage($formatted);
    }
    if($this->console) $this->getServer()->getLogger()->info('[Staff Chat] '.$formatted);
  }

  /**
   * @param $name String Plugin's name
   * @param $message String Plugin's message
   * @param bool $append weather to append [Plugin] into name
   */
  public function pluginBroadcast($name,$message,bool $append = true)
  {
    if($append) $this->broadcast('[Plugin]'.$name,$message); else $this->broadcast($name,$message);
  }

  public function onCommand(CommandSender $sender,Command $command,$label,array $args)
  {
    if(!isset($args[0])) $args[0] = "help";
    switch($args[0]){
      case "help":
        $msgs = ['Staff chat help menu','say <message> - chat into staff chat','on - enable chatting mode','off - disable chatting mode','toggle - toggle chatting mode',//'config - config command',//maybe latter...
         'reload - reloads and flushes internal data','attach <true|false> - attach console into staff chat','check <player> - checks other player status',//'setl [-fs] <player> <true|false> - sets other players listen status',
          //'setc [-fs] <player> <true|false> - sets other players chat status',
          //'-f to forcefully set player status regardless of their permission',
          //'-s silently sets staffchat status',
          //'-n dont notify other staff(only for players with insufficient permissions)',
         'author - show author info',//' - ',
        ];
        foreach($msgs as $msg) $sender->sendMessage(TextFormat::GOLD."Staff Chat Help> ".$msg);
        break;
      case "say":
        if($sender->hasPermission(self::permChat) OR $sender instanceof ConsoleCommandSender) {
          array_shift($args);
          $this->broadcast($sender->getName(),implode(" ",$args));
        } else $sender->sendMessage(self::errPerm);
        break;

      case "on":
        if($sender->hasPermission(self::permRead)) {
          if($sender instanceof Player) {
            $this->setChatting($sender,true);
            $sender->sendMessage(TextFormat::GREEN.'[ON] All messages will now go directly into STAFF chat!');
          } else $sender->sendMessage('Please run this command as player');
        } else $sender->sendMessage(self::errPerm);
        break;
      case "off":
        if($sender->hasPermission(self::permRead)) {
          if($sender instanceof Player) {
            $this->setChatting($sender,false);
            $sender->sendMessage(TextFormat::GREEN.'[OFF] All messages will now go into NORMAL chat!');
          } else $sender->sendMessage('Please run this command as player');
        } else $sender->sendMessage(self::errPerm);
        break;
      case "toggle":
        if($sender->hasPermission(self::permRead)) {
          if($sender instanceof Player) {
            $this->setChatting($sender,!$this->isChatting($sender));
            $sender->sendMessage(TextFormat::GREEN."Staff Chat: ".$this->getReadableState($sender));
          } else $sender->sendMessage('Please run this command as player');
        } else $sender->sendMessage(self::errPerm);
        break;

      case "reload":
        if(!$sender->hasPermission('staffchat.reload')) {
          $sender->sendMessage(self::errPerm);
          break;
        }
        $this->getConfig()->reload();
        $this->console = (bool)$this->getConfig()->get('auto-attach',true);
        $this->prefix = $this->getConfig()->get('prefix',".");

        foreach($this->getChatting() as $player => $chatting){
          $player = $this->getServer()->getPlayerExact($player);
          $player->sendMessage(TextFormat::RED."Staff Chat> All message will now go into NORMAL chat");
        }
        $this->chatting = [];

        $this->format = '';
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
        } else $sender->sendMessage("/staffchat attach <true|false>");
        $sender->sendMessage(TextFormat::GREEN.'Console State: '.$this->getReadableConsoleState());
        break;
      case "check":
        if(!$sender->hasPermission('staffchat.check')) {
          $sender->sendMessage(self::errPerm);
          return;
        }
        if(!isset($args[1])) {
          if($sender instanceof Player) {
            $sender->sendMessage('No player provided using yourself instated');
            $args[1] = $sender->getName();
          } else {
            $sender->sendMessage("Please input player's name");
          }

        }
        if(($player = $this->getServer()->getPlayer($args[1])) === null) {
          $sender->sendMessage('Player "'.$args[1].'" cant be found');
          return;
        }
        $sender->sendMessage('Status of "'.$player->getName().'"');
        $sender->sendMessage('Can chat: '.$this->readableTrueFalse($player->hasPermission(self::permChat)),'yes','no');
        $sender->sendMessage('Can read: '.$this->readableTrueFalse($player->hasPermission(self::permRead)),'yes','no');
        $sender->sendMessage('Is chatting: '.$this->getReadableState($player,'yes','no'));
        break;
      case "list":
        if(!$sender->hasPermission('staffchat.list')) {
          $sender->sendMessage(self::errPerm);
          return;
        }
        $canChatAndRead = [];
        $canChat = [];
        $canRead = [];
        foreach($this->getServer()->getOnlinePlayers() as $onlinePlayer){
          if($onlinePlayer->hasPermission(self::permChat) AND $onlinePlayer->hasPermission(self::permRead)) {
            $canChatAndRead[] = $onlinePlayer->getName();
          } else {
            if($onlinePlayer->hasPermission(self::permChat)) $canChat[] = $onlinePlayer->getName();
            if($onlinePlayer->hasPermission(self::permRead)) $canRead[] = $onlinePlayer->getName();
          }
        }
        $chatting = $this->getChatting();
        $sender->sendMessage('Info List Of Online Players');
        if(count($canChatAndRead) > 0) $sender->sendMessage('Can Chat And Read('.count($canChatAndRead).') :'.implode(',',$canChatAndRead));
        if(count($canChat) > 0) $sender->sendMessage('Can Chat('.count($canChat).') :'.implode(',',$canChat));
        if(count($canRead) > 0) $sender->sendMessage('Can Read('.count($canRead).') :'.implode(',',$canChat));
        if(count($chatting) > 0) $sender->sendMessage('Is Chatting('.count($chatting).'): '.implode(',',$chatting));
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
  }

  public function onChat(PlayerCommandPreprocessEvent $event)
  {
    $message = $event->getMessage();
    $player = $event->getPlayer();
    $sub = strtolower(substr($message,0,strlen($this->prefix)));
    if(substr($message,0,1) === "/") return;
    if($sub === $this->prefix) {
      $event->setCancelled(true);
      if(!$player->hasPermission(self::permChat)) {
        return;
      }
      $message = substr($message,strlen($this->prefix));
      $this->broadcast($player->getName(),$message);
    } elseif($this->isChatting($player)) {
      if(!$player->hasPermission(self::permChat)) {
        $this->setChatting($player,false);
        return;
      }
      $event->setCancelled(true);
      $this->broadcast($player->getName(),$message);
    }
  }

  /**
   * Replaces colour, just some cool hax inspired by PocketMine's @ priority tag detection system
   * @param string $string
   * Full message string
   *
   * @param string $trigger
   * Anything before and after * denote the trigger, *(wildcard) which would denote colour code
   * example ![*] means ![colourcode] ![BLACK]
   * @return string
   * Formatted String
   */
  private function replaceColour($string,$trigger = "![*]"): string
  {
    preg_match('/(.*)\*(.*)/',$trigger,$trim);
    preg_match_all('/'.preg_quote($trim[1]).'([A-Z a-z \_]*)'.preg_quote($trim[2]).'/',$string,$matches);
    foreach($matches[1] as $key => $colourCode){
      if(strpos($string,$matches[0][$key]) === false) continue;
      $colourCode = strtoupper($colourCode);
      if(defined(TextFormat::class."::".$colourCode)) {
        $code = constant(TextFormat::class."::".$colourCode);
        $string = str_replace($matches[0][$key],$code,$string);
      }
    }
    return $string;
  }

  /*
   * Public APIs
   * for plugin integrations
   */

  /**
   * checks if player is using chatting mode
   * @param $player string|Player|CommandSender Player to check
   * @return bool player is chatting status
   */
  public function isChatting($player): bool
  {
    if($player instanceof CommandSender) if($player instanceof Player) $player = $player->getName(); else return false;
    $player = strtolower($player);
    if(isset($this->chatting[$player])) return $this->chatting[$player]; else return false;
  }

  /**
   * Get Chatting array directly
   * @param bool $sort Sort to remove values with false
   * @return array
   */
  public function getChatting($sort = true)
  {
    if($sort) {
      foreach($this->chatting as $player => $chatting) if($chatting == false) unset ($this->chatting[$player]);
    }
    return $this->chatting;
  }

  /**
   * sets player to chatting mode
   * @param $player string|Player|CommandSender Player to set
   * @param bool $state
   */
  public function setChatting($player,bool $state)
  {
    if($player instanceof CommandSender) if($player instanceof Player) $player = $player->getName(); else return;
    $player = strtolower($player);
    if($state == true) $this->chatting[$player] = $state; else unset($this->chatting[$player]);
  }

  /**
   * get readable player chatting state
   * @param $player string|Player|CommandSender
   * @param string $true what to return if true
   * @param string $false what to return if false
   * @return string result in string
   */
  public function getReadableState($player,string $true = "On",string $false = "Off"): string
  {
    return $this->readableTrueFalse($this->isChatting($player),$true,$false);
  }

  /**
   * sets console attachment state
   */
  public function setConsoleState(bool $state) { $this->console = $state; }

  /**
   * gets if console is attached to staff chat
   * @return bool
   */
  public function getConsoleState(): bool { return $this->console; }

  /**
   * get readable console attachment state
   * @param string $true what to return if true
   * @param string $false what to return if false
   * @return string result in string
   */
  public function getReadableConsoleState(string $true = "Attached",string $false = "Detached"): string
  {
    return $this->readableTrueFalse($this->console,$true,$false);
  }

  /**
   * readable true false
   * @param bool $statement
   * @param string $true what to return if true
   * @param string $false what to return if false
   * @return string result in string
   */
  public function readableTrueFalse(bool $statement,$true = 'true',$false = 'false')
  {
    if($statement) return $true; else return $false;
  }
}