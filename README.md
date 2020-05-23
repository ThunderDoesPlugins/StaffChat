# Staff Chat
[<img src="https://img.shields.io/badge/Poggit-view-brightgreen.svg" width="100" height="25" />](https://poggit.pmmp.io/ci/ThunderDoesPlugins/StaffChat/StaffChat)
[<img src="https://img.shields.io/badge/Discord-join-697EC4.svg" width="100" height="25" />](https://discord.gg/uBghvNp)

<!--
  Title: Staff Chat
  Description: An Advanced staff chat plugins for your staff members
  Author: Thunder33345
  -->
<meta name='keywords' content='staffchat, staff chat, plugin, pocketmine, mcpe'>
<meta name='description' content='An Advanced staff chat plugins for your staff members'>

Advance private chat plugins for your staff members

Allows you to create a staff chat between staff members with permissions and chat prefix

If you want to make a video of it, please link back to this repo in your description, you may request your videos to be featured here if it meet reasonable quality

## How to install and use

To download compiled PHAR, please click the poggit view button above then scroll down selecting the latest by clicking on Direct for latest version, or you can click on latest release "Direct Download"

Put this in your Plugins, start the server to generate a config file, you may edit the config to suit your needs, scroll down for more info on configuration files

To chat into staff chat type your prefix followed by your message, no spaces are required
For example prefix is . so typing ".this message will be sent to staff chat" will sent that message to staff chat letting all online staff to know

To chat into staff chat without prefix or commands use "/sc on" this will put all of your message into staff chat for convenience if you wish to have long conversations "/sc off" when you are done

To chat into staff chat as console type "/sc say <message here>"

Console can also choose to receive staff chat or not by "/sc attach on/off"

## Intended Usage

This plugin was created to allow servers with multiple staff members to cooperate together while chatting silently between each other without /tell and with ease of use!

## Commands:

Commands start with /staffchat alias is /sc

| Command             | Info                                                      |
|---------------------|-----------------------------------------------------------|
| say                 | Send a message to staff channel (this is for the console) |
| on/off/toggle       | Switch current chat mode (chats directly into staff chat) |
| reload              | Reloads configs and flushes internal data                 |
| attach <true/false> | Attach console into/out of staff chat                     |
| check [player]      | Checks player staff chat status and permissions           |
| list                | List player with staff chat permissions and is chatting   |

## Configs:

| Config Value  | Info                                                                                                           |
|---------------|----------------------------------------------------------------------------------------------------------------|
| prefix        | Used to send a message directly into staff chat Example: ".hello staff" you may set it to any value you prefer |
| auto-attach   | Automatically make console listen to staff chat on start?                                                      |
| console-prefix| Console logging prefix for Staff Chat                                                                          |
| player-format | Staff chat formatting example: "![bold]StaffChat![reset]%sender%>%msg%"                                        |
| plugin-format | Staff chat formatting for plugins                                                                              |
| functions     | (BETA) Enable functions See #functions section below for more                                                  |
| joinleave     | Enable join leave announce message to staff                                                                    |
| join/leave    | Join leave format                                                                                              |

There's also references inside config file

## Functions:

You can use function by typing "$name" in staff chat and will be replaced with appropriate text(buggy might not work)

|Function Name            | Usage                                                                                      |
|-------------------------|--------------------------------------------------------------------------------------------|
| $pos                    | Replaces it with your level,x,y,z                                                          |
| $ping                   | Replaces $ping with bolded green and play Enderman teleport+Anvil fall sound to all staff  |
| $near(distance number)$ | Replaces it with "Near Me(count):" and Playername(GM:mode Dist:number)                     |

## Permissions:

| permission node  | Info                                                   |
|------------------|--------------------------------------------------------|
| staffchat.read   | Allow players to read chat                             |
| staffchat.chat   | Allow players to chat into chat                        |
| staffchat.attach | Allow players to attach or detach console from chat    |
| staffchat.check  | Allow players to check other player's staff chat status|
| staffchat.list   | Allow players to list players staff chat status        |

By default all permission nodes are granted for operators.

## Video
[Tutorial by JJ Birdman](https://www.youtube.com/watch?v=wdwaLXjw9Xs)
