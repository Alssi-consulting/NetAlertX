# Pi.Alert
<!--- --------------------------------------------------------------------- --->

WIFI / LAN intruder detector.

Scan the devices connected to your WIFI / LAN and alert you the connection of
unknown devices. It also warns if a "always connected" devices disconnects.

![Main screen][main]


# Docker image 🐳
[![Docker](https://img.shields.io/github/workflow/status/jokob-sk/Pi.Alert/docker?label=Build&logo=GitHub)](https://github.com/jokob-sk/Pi.Alert/actions/workflows/docker.yml)
[![GitHub Committed](https://img.shields.io/github/last-commit/jokob-sk/Pi.Alert?color=40ba12&label=Committed&logo=GitHub&logoColor=fff)](https://github.com/jokob-sk/Pi.Alert)
[![Docker Size](https://img.shields.io/docker/image-size/jokobsk/pi.alert?label=Size&logo=Docker&color=0aa8d2&logoColor=fff)](https://hub.docker.com/r/jokobsk/pi.alert)
[![Docker Pulls](https://img.shields.io/docker/pulls/jokobsk/pi.alert?label=Pulls&logo=docker&color=0aa8d2&logoColor=fff)](https://hub.docker.com/r/jokobsk/pi.alert)
[![Docker Pushed](https://img.shields.io/badge/dynamic/json?color=0aa8d2&logoColor=fff&label=Pushed&query=last_updated&url=https%3A%2F%2Fhub.docker.com%2Fv2%2Frepositories%2Fjokobsk%2Fpi.alert%2F&logo=docker&link=http://left&link=https://hub.docker.com/repository/docker/jokobsk/pi.alert)](https://hub.docker.com/r/jokobsk/pi.alert)

🐳 Docker hub: [jokobsk/Pi.Alert](https://registry.hub.docker.com/r/jokobsk/pi.alert) <br/>
📄 [Dockerfile](https://github.com/jokob-sk/Pi.Alert/blob/main/Dockerfile) <br/>
📚 [Docker instructions](https://github.com/jokob-sk/Pi.Alert/blob/main//dockerfiles/README.md)


Dark mode (and much more) within this fork courtesy of [leiweibau](https://github.com/leiweibau/Pi.Alert)

## How it works
The system continuously scans the network for:
  - New devices
  - New connections (re-connections)
  - Disconnections
  - "Always Connected" devices down
  - Devices IP changes
  - Internet IP address changes

## Scan Methods
Up to three scanning methods are used:
  - **Method 1: arp-scan**. The arp-scan system utility is used to search
        for devices on the network using arp frames.
  - **Method 2: Pi-hole**. This method is optional and complementary to
        method 1. If the Pi-hole DNS server is active, Pi.Alert examines its
        activity looking for active devices using DNS that have not been
        detected by method 1.
  - **Method 3. dnsmasq**. This method is optional and complementary to the
        previous methods. If the DHCP server dnsmasq is active, Pi.Alert
        examines the DHCP leases (addresses assigned) to find active devices
        that were not discovered by the other methods.


### Notification Service Support 
   - [Apprise](https://hub.docker.com/r/caronc/apprise) 
   - [Pushsafer](https://www.pushsafer.com/)
   - [NTFY](https://ntfy.sh/)
   - Webhooks ([sample JSON](docs/webhook_json_sample.json))
   - (❗Experimental) Home Assistant (via [MQTT](https://www.home-assistant.io/integrations/mqtt/) (processing ~5s per device, deleting not supported, use [MQTT Explorer](https://mqtt-explorer.com/) for now))  

  | ![Report 1][report1] | ![Report 2][report2] |
  | -------------------- | -------------------- |

### Security
There is a configurable login to prevent unauthorized use. 

> * Set `PIALERT_WEB_PROTECTION = True` in `pialert.conf` to enable. The default password is `123456`.
> To change password run `/home/pi/pialert/back/pialert-cli`

## Features   
  - Display in a visual way all the information collected by the back
    - Sessions, Connected devices, Favorites
    - Events, Presence, Concurrent devices
    - Down alerts
    - IP's
    - Manual Nmap scans, Optional speedtest for Device "Internet"
    - Simple Network relationship display
  - Maintenance tasks and Settings like:
    - Status Infos (active scans, database size, backup counter)
    - Theme Selection (blue, red, green, yellow, black, purple)
    - Language Selection (english, german, spanish)
    - Light/Dark-Mode Switch
    - Pause arp-scan
    - DB maintenance, Backup, Restore tools
    - CSV Export / Import
  - Help/FAQ Section 

  | ![Screen 1][screen1] | ![Screen 2][screen2] |
  | -------------------- | -------------------- |
  | ![Screen 3][screen3] | ![Screen 4][screen4] |
  | ![Screen 5][screen5] | ![Screen 6][screen6] |

# Installation
<!--- --------------------------------------------------------------------- --->
Initially designed to run on a Raspberry Pi, probably it can run on many other
Linux distributions.

> ⚠ Please note, this [fork (jokob-sk)](https://github.com/jokob-sk/Pi.Alert) is only tested via the [docker install method](dockerfiles/README.md). Check out [leiweibau's fork](https://github.com/leiweibau/Pi.Alert/) if you want to install Pi.Alert on the server directly.

Instructions for [pucherot's original code can be found here](https://github.com/pucherot/Pi.Alert/)

# Device Management
<!--- --------------------------------------------------------------------- --->
  - [Device Management instructions](docs/DEVICE_MANAGEMENT.md)


## Other useful info
<!--- --------------------------------------------------------------------- --->

### [Versions History](docs/VERSIONS_HISTORY.md)

### License
  GPL 3.0
  [Read more here](LICENSE.txt)

  Source of the animated GIF (Loading Animation)
  https://commons.wikimedia.org/wiki/File:Loading_Animation.gif
  
  Source of the selfhosted Fonts
  https://github.com/adobe-fonts/source-sans

  
### Special thanks 🥇

  This code is a collaborative body of work, with special thanks to: 

   - 🏆 [pucherot/Pi.Alert](https://github.com/pucherot/Pi.Alert) is the original creator od PiAlert
   - [leiweibau](https://github.com/leiweibau/Pi.Alert): Things
   - [Macleykun](https://github.com/Macleykun): Help with Dockerfile clean-up
   - [Final-Hawk](https://github.com/Final-Hawk): Help with NTFY, styling and other fixes
   - [TeroRERO](https://github.com/terorero): Spanish translation
   - [jokob-sk](https://github.com/jokob-sk/Pi.Alert): DB Maintenance tools
   - Please see the [Git commit history](https://github.com/jokob-sk/Pi.Alert/commits/main) for a full list of people and their contributions to the project

<!--- --------------------------------------------------------------------- --->
[main]:    ./docs/img/1_devices.jpg           "Main screen"
[screen1]: ./docs/img/2_1_device_details.jpg  "Screen 1"
[screen2]: ./docs/img/2_2_device_sessions.jpg "Screen 2"
[screen3]: ./docs/img/2_3_device_presence.jpg "Screen 3"
[screen4]: ./docs/img/3_presence.jpg          "Screen 4"
[screen5]: ./docs/img/2_4_network.png         "Screen 5"
[screen6]: ./docs/img/2_5_device_nmap_ready.jpg "Screen 6"
[report1]: ./docs/img/4_report_1.jpg          "Report sample 1"
[report2]: ./docs/img/4_report_2.jpg          "Report sample 2"
[main_dark]: /docs/img/1_devices_dark.jpg     "Main screen dark"
[maintain_dark]: /docs/img/5_maintain.jpg     "Maintain screen dark"
