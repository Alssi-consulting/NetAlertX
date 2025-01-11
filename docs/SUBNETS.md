# Subnets Configuration

You need to specify the network interface and the network mask. You can also configure multiple subnets and specify VLANs (see VLAN exceptions below).

`ARPSCAN` can scan multiple networks if the network allows it. To scan networks directly, the subnets must be accessible from the network where NetAlertX is running. This means NetAlertX needs to have access to the interface attached to that subnet. You can verify this by running the following command in the container (replace the interface and ip mask):

`sudo arp-scan --interface=eth0 192.168.1.0/24`

In this example, `--interface=eth0 192.168.1.0/24` represents a neighboring subnet. If this command returns no results, the network is not accessible due to your network or firewall restrictions.

If direct scans are not possible (Wi-Fi Extenders, VPNs and inaccessible networks), check the [remote networks documentation](https://github.com/jokob-sk/NetAlertX/blob/main/docs/REMOTE_NETWORKS.md). 

> [!TIP] 
> You may need to increase the time between scans `ARPSCAN_RUN_SCHD` and the timeout `ARPSCAN_RUN_TIMEOUT` (and similar settings for related plugins) when adding more subnets. If the timeout setting is exceeded, the scan is canceled to prevent the application from hanging due to rogue plugins.  
> Check [debugging plugins](/docs/DEBUG_PLUGINS.md) for more tips.

## Example Values

> [!NOTE] 
> Please use the UI to configure settings as it ensures the config file is in the correct format. Edit `app.conf` directly only when really necessary.  
> ![Settings location](/docs/img/SUBNETS/subnets-setting-location.png)

* **Examples for one and two subnets:**
  * One subnet: `SCAN_SUBNETS = ['192.168.1.0/24 --interface=eth0']`
  * Two subnets: `SCAN_SUBNETS = ['192.168.1.0/24 --interface=eth0','192.168.1.0/24 --interface=eth1 --vlan=107']`

If you get timeout messages, decrease the network mask (e.g.: from `/16` to `/24`) or increase the `TIMEOUT` setting (e.g.: `ARPSCAN_RUN_TIMEOUT` to `300` (5-minute timeout)) for the plugin and the interval between scans (e.g.: `ARPSCAN_RUN_SCHD` to `*/10 * * * *` (scans every 10 minutes)).

---

## Explanation

### Network Mask

**Example value:** `192.168.1.0/24`

The `arp-scan` time itself depends on the number of IP addresses to check.

> The number of IPs to check depends on the [network mask](https://www.calculator.net/ip-subnet-calculator.html) you set in the `SCAN_SUBNETS` setting.  
> For example, a `/24` mask results in 256 IPs to check, whereas a `/16` mask checks around 65,536 IPs. Each IP takes a couple of seconds, so an incorrect configuration could make `arp-scan` take hours instead of seconds.

Specify the network filter, which **significantly** speeds up the scan process. For example, the filter `192.168.1.0/24` covers IP ranges from `192.168.1.0` to `192.168.1.255`.

### Network Interface (Adapter)

**Example value:** `--interface=eth0`

The adapter will probably be `eth0` or `eth1`. (Check `System Info` > `Network Hardware`, or run `iwconfig` in the container to find your interface name(s)).

![Network hardware](/docs/img/SUBNETS/system_info-network_hardware.png)

> [!TIP]  
> As an alternative to `iwconfig`, run `ip -o link show | awk -F': ' '!/lo|vir|docker/ {print $2}'` in your container to find your interface name(s) (e.g.: `eth0`, `eth1`):
> ```bash
> Synology-NAS:/# ip -o link show | awk -F': ' '!/lo|vir|docker/ {print $2}'
> sit0@NONE
> eth1
> eth0
> ```

### VLANs

**Example value:** `--vlan=107`

- Append `--vlan=107` to the `SCAN_SUBNETS` field (e.g.: `192.168.1.0/24 --interface=vmbr0 --vlan=107`) for multiple VLANs.

#### VLANs on a Hyper-V Setup

> Community-sourced content by [mscreations](https://github.com/mscreations) from this [discussion](https://github.com/jokob-sk/NetAlertX/discussions/404).

**Tested Setup:** Bare Metal → Hyper-V on Win Server 2019 → Ubuntu 22.04 VM → Docker → NetAlertX.

**Approach 1 (may cause issues):**  
Configure multiple network adapters in Hyper-V with distinct VLANs connected to each one using Hyper-V's network setup. However, this action can potentially lead to the Docker host's inability to handle network traffic correctly. This might interfere with other applications such as Authentik.

**Approach 2 (working example):**

Network connections to switches are configured as trunk and allow all VLANs access to the server.

By default, Hyper-V only allows untagged packets through to the VM interface, blocking VLAN-tagged packets. To fix this, follow these steps:

1. Run the following command in PowerShell on the Hyper-V machine:

   ```powershell
   Set-VMNetworkAdapterVlan -VMName <Docker VM Name> -Trunk -NativeVlanId 0 -AllowedVlanIdList "<comma separated list of vlans>"
   ```


2. Within the VM, set up sub-interfaces for each VLAN to enable scanning. On Ubuntu 22.04, Netplan can be used. In /etc/netplan/00-installer-config.yaml, add VLAN definitions:

  ```yaml
      network:
        ethernets:
          eth0:
            dhcp4: yes
        vlans:
          eth0.2:
            id: 2
            link: eth0
            addresses: [ "192.168.2.2/24" ]
            routes:
              - to: 192.168.2.0/24
                via: 192.168.1.1
  ```

3. Run `sudo netplan apply` to activate the interfaces for scanning in NetAlertX.

In this case, use `192.168.2.0/24 --interface=eth0.2` in NetAlertX.

#### VLAN Support & Exceptions

Please note the accessibility of macvlans when configured on the same computer. This is a general networking behavior, but feel free to clarify via a PR/issue.

- NetAlertX does not detect the macvlan container when it is running on the same computer.
- NetAlertX recognizes the macvlan container when it is running on a different computer.

