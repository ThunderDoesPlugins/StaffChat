<?php
/** Created By Thunder33345 **/

namespace Thunder33345\StaffChat;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
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

  public function broadcast($name,$message)
  {
    if(!isset($this->format) OR $this->format === "") {
      $format = $this->getConfig()->get("format");
      $this->format = $this->replaceColour($format);
    }
    $format = $this->format;
    //now i hate my ide... i need line 70 yet wanting to keep this all inline...

    $formatted = str_replace('%sender%',$name,$format);
    $formatted = str_replace('%msg%',$message,$formatted);
    foreach($this->getServer()->getOnlinePlayers() as $player){
      if(!$player->hasPermission('staffchat.read')) continue;
      $player->sendMessage($formatted);
    }
    if($this->console) $this->getServer()->getLogger()->info('[Staff Chat] '.$formatted);
  }

  public function onCommand(CommandSender $sender,Command $command,$label,array $args)
  {
    if(!isset($args[0])) $args[0] = "help";
    switch($args[0]){
      case "help":
        $msgs = [
         "Staff chat help",
         "say - chat into staff chat",
         "on - enable chatting mode",
         "off - disable chatting mode",
         "toggle - toggle chatting mode",
         "config - config command",
         "reload - reloads",
         "attach - attach console into staff chat",
         //" - ",
        ];
        foreach($msgs as $msg) $sender->sendMessage(TextFormat::GOLD.$msg);
        break;
      case "say":
        if($sender->hasPermission(self::permChat) OR $sender instanceof ConsoleCommandSender) {
          array_shift($args);
          $this->broadcast($sender->getName(),implode(" ",$args));
        } else $sender->sendMessage(self::errPerm);
        break;

      case "on":
        if($sender->hasPermission(self::permRead)) {
          $this->setChatting($sender,true);
          $sender->sendMessage(TextFormat::GREEN.'[ON] All messages will now go directly into STAFF chat!');
        }//i hate my ide...
        else $sender->sendMessage(self::errPerm);
        break;
      case "off":
        if($sender->hasPermission(self::permRead)) {
          $this->setChatting($sender,false);
          $sender->sendMessage(TextFormat::GREEN.'[OFF] All messages will now go into NORMAL chat!');
        } else $sender->sendMessage(self::errPerm);
        break;
      case "toggle":
        if($sender->hasPermission(self::permRead)) {
          $this->setChatting($sender,!$this->getState($sender));
          $sender->sendMessage(TextFormat::GREEN."Staff Chat: ".$this->getState($sender));
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

        foreach($this->chatting as $player => $chatting){
          if(!$chatting) continue;
          $player = $this->getServer()->getPlayerExact($player);
          $player->sendMessage(TextFormat::RED."Staff Chat> All message will now go into NORMAL chat");
        }
        $this->chatting = [];

        $this->format = [];
        $sender->sendMessage(TextFormat::GREEN."Successfully flushed internal data...");
        break;
      case "attach":
        if(!$sender->hasPermission('staffchat.attach') AND !$sender instanceof ConsoleCommandSender) $sender->sendMessage(self::errPerm);
        if(isset($args[1])) switch($args[1]){
          case "on":
          case "true":
            $this->console = true;
            break;
          case "false":
          case "off":
            $this->console = false;
            break;
          default:
            $sender->sendMessage("/staffchat attach <true|false>");
            break;
        } else {
          $sender->sendMessage("/staffchat attach <true|false>");
        }
        $sender->sendMessage(TextFormat::GREEN.'Console State: '.$this->getConsoleState());
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
    if($sub == $this->prefix) {
      if(!$player->hasPermission(self::permChat)) {
        $player->sendMessage(self::errPerm);
        return;
      }
      $event->setCancelled(true);
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

  private function isChatting($player)
  {
    if($player instanceof CommandSender) $player = $player->getName();
    $player = strtolower($player);
    if(isset($this->chatting[$player])) return $this->chatting[$player]; else return false;
  }

  private function setChatting($player,bool $state)
  {
    if($player instanceof CommandSender) $player = $player->getName();
    $player = strtolower($player);
    $this->chatting[$player] = $state;
  }

  private function getState($player)
  {
    if($player instanceof CommandSender) $player = $player->getName();
    $player = strtolower($player);
    if(isset($this->chatting[$player])) if($this->chatting[$player]) return "on"; else return "off"; else
      return "off";
  }

  private function getConsoleState()
  {
    if($this->console) return "Attached"; else return "Detached";
  }
}