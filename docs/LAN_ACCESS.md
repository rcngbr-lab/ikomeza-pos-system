# FRONTIER POS LAN Access

Use this when FRONTIER POS must be opened by phones, tablets, or other computers on the same local network.

## Start The System

Double-click:

```bat
start-pos.bat
```

The launcher prints two URLs:

```text
Computer URL: http://127.0.0.1:8000
Network URL : http://YOUR-LAN-IP:8000
```

Use `Computer URL` on the main POS computer.

Use `Network URL` on other devices connected to the same Wi-Fi or LAN.

## Important Notes

- Keep the POS computer turned on while other devices are using the system.
- All devices must be connected to the same local network.
- Do not use `localhost` or `127.0.0.1` on phones. Those addresses point to the phone itself.
- The launcher builds static frontend assets before starting Laravel, so LAN devices do not depend on the Vite development server.

## Windows Firewall

If another device cannot open the Network URL:

1. Open Windows Security.
2. Go to Firewall & network protection.
3. Allow PHP through the firewall, or allow inbound TCP port `8000`.
4. Restart `start-pos.bat`.

PowerShell option for administrators:

```powershell
New-NetFirewallRule -DisplayName "FRONTIER POS LAN 8000" -Direction Inbound -Protocol TCP -LocalPort 8000 -Action Allow
```

## Recommended Local Network Setup

- Use a stable Wi-Fi router or LAN cable.
- Reserve a static IP address for the POS computer in the router settings.
- Example fixed address: `192.168.1.50`
- Then staff devices can always open:

```text
http://192.168.1.50:8000
```

## Stop The System

Close the opened command windows:

- `FRONTIER POS LAN SERVER`
- `FRONTIER POS DESKTOP`

