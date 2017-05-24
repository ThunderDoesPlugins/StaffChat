# StaffChat
[<img src="https://img.shields.io/badge/Poggit-view-brightgreen.svg" width="100" height="25" />](https://poggit.pmmp.io/ci/ThunderDoesPlugins/StaffChat/StaffChat)
[<img src="https://img.shields.io/badge/Discord-join-697EC4.svg" width="100" height="25" />](https://discord.gg/uBghvNp)

Private Staff Chat Channel Plugin

Allows you to create a staff chat between staff using permissions

## If you want to make a video of it, please link back to this repo in your description, you may request your videos to be featured here

commands:

| Command             | Info                                                     |
|---------------------|----------------------------------------------------------|
| say                 | Send a message to staff channel                          |
| on/off/toggle       | Switch current chat mode(chats directly into staff chat) |
| reload              | Reloads configs and flushes internal data                |
| attach <true/false> | Attach console into/out of staff chat                    |
| check [player]      | Checks player staffchat status and permissions           |

Configs:

| Config Value | Info                                                                                                           |
|--------------|----------------------------------------------------------------------------------------------------------------|
| prefix       | Used to send a message directly into staff chat Example: ".hello staff" you may set it to any value you prefer |
| auto-attach  | Automatically make console listen to staff chat on start?                                                      |
| format       | Staff chat formatting example: "![bold]StaffChat![reset]%sender%>%msg%"                                        |

There's also references inside config file

Permissions:

| permission node  | Info                                                   |
|------------------|--------------------------------------------------------|
| staffchat.read   | Allow players to read chat                             |
| staffchat.chat   | Allow players to chat into chat                        |
| staffchat.attach | Allow players to attach or detach console from chat    |
| staffchat.check  | Allow players to check other player's staffchat status |

By defualt all permission nodes are granted for OPs
