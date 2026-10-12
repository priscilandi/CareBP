# Apache and deployment

**Platform scope:** The included `apache/carebp.conf` and commands below are for Ubuntu/Debian Linux. On macOS use the [PHP development server](setup.md), or provide a separately engineered Apache setup. Other Linux distributions need adapted service/package commands. **The given Apache config is a local development example, not a production-ready host.**

## Ubuntu/Debian Apache

These commands configure the **provided local-development virtual host on Ubuntu/Debian Linux only**. Do not run the `apt`, `a2ensite`, `systemctl`, or `www-data` commands on macOS; for macOS, use [setup](setup.md) or create a separate, platform-specific Apache/PHP handler configuration. They assume Apache and PHP's Apache module were installed in dependency installation. The example domain `carebp.local` is a hostname you resolve locally, **not** a publicly configured domain.

### 6.1 Copy the full project to the server

From the project root:

```bash

sudo mkdir -p /var/www/carebp
sudo cp -a . /var/www/carebp/

```

Copy the **whole** application because `backend/` needs the private config and database code, but do not serve that whole directory as web content. Edit `/var/www/carebp/.env` for the database credentials on the **Apache server**, then restrict its permissions:

```bash

sudo chown root:www-data /var/www/carebp/.env

sudo chmod 640 /var/www/carebp/.env

```

If you are deploying from another machine, transfer the project securely and create the destination `.env` on the server instead of transferring development credentials.

### 6.2 Enable the included virtual host

Inspect `apache/carebp.conf` before installing it. Its document root points to `/var/www/carebp/frontend`, and its `/api/` alias points to the public API scripts under `/var/www/carebp/backend/api`. **If your deployment directory differs, update both paths in the virtual-host file.**

```bash

sudo cp apache/carebp.conf /etc/apache2/sites-available/carebp.conf

sudo a2ensite carebp.conf

sudo apache2ctl configtest       # Expected: Syntax OK

sudo systemctl reload apache2

```

The provided host uses `ServerName carebp.local` on port `80`. On the computer **where you will open the site**, add the following entry to its hosts file (`/etc/hosts` on macOS/Linux; see the Windows PowerShell instructions below):

```text

127.0.0.1 carebp.local

```

This `127.0.0.1` entry works only when your browser and Apache run on the **same computer**. For another machine on your network, resolve `carebp.local` to the Apache machine's actual address instead. Then visit **http://carebp.local/**. If Apache's default virtual host catches the request, inspect the enabled sites; if appropriate on this **Ubuntu/Debian development server**, run `sudo a2dissite 000-default.conf` and `sudo systemctl reload apache2`.

#### Windows/PowerShell: resolve `carebp-local` when Apache is on another machine

If you use **Windows to access Apache running on Linux, a VM, WSL, or another computer**, the Apache server's IP may not be `127.0.0.1`. The Windows hosts file must map your chosen hostname to the IP address **reachable from Windows**. A VM or WSL IP may change when it restarts; update the hosts entry whenever that address changes. Do not use a public IP or an example IP unless it is actually your server's address.

1. **Find the server IP.** On the Linux machine hosting Apache, run `hostname -I` or `ip -4 addr`. For WSL, run `hostname -I` inside the Linux distribution. Choose the IP Windows can reach; with a remote machine, this is normally its LAN IP. If Apache is running directly on the same Windows computer, use `127.0.0.1` instead.

2. **Make Apache recognize `carebp-local`.** The supplied virtual host uses `ServerName carebp.local`, so add `ServerAlias carebp-local` inside the same `<VirtualHost *:80>` block in `/etc/apache2/sites-available/carebp.conf` (or change `ServerName` to `carebp-local`). Then on the Apache server run:

   ```bash

   sudo apache2ctl configtest

   sudo systemctl reload apache2

   ```

3. **Edit the Windows hosts file from an elevated PowerShell session.** Right-click PowerShell and select **Run as administrator**, then run:

   ```powershell

   notepad "$env:SystemRoot\System32\drivers\etc\hosts"

   ```

   Add the following line using **your Apache server's actual reachable IP**, then save the file. For example, if Apache is at `192.168.1.50`:

   ```text

   192.168.1.50 carebp-local

   ```

   If Apache is on the same computer and bound to localhost, use `127.0.0.1 carebp-local` instead. Do not remove existing hosts-file entries.

4. **Refresh and test from Windows.** In PowerShell, run:

   ```powershell

   ipconfig /flushdns

   ping carebp-local

   Test-NetConnection carebp-local -Port 80

   ```

   The IP shown for `carebp-local` should match the entry you added, and `TcpTestSucceeded` should be `True`. A blocked ICMP ping alone does not mean the site is unavailable. Open **http://carebp-local/** and then **http://carebp-local/api/health.php** in your browser.

If the hostname resolves to the wrong address, reopen the Windows hosts file and correct the IP; check for duplicate `carebp-local` entries. If it resolves correctly but port 80 fails, verify Apache is running and listening on an interface Windows can reach, and check VM/WSL networking and firewalls. If you see Apache's default page, check `ServerName`/`ServerAlias`, site activation, and which virtual host handles port 80 (`sudo apache2ctl -S`). A Windows hosts entry only resolves a name—it does not configure Apache or open a network port.

#### macOS and Linux: resolve `carebp-local` to the Apache server

On **macOS or Linux**, edit `/etc/hosts` on the computer **where you will open the website**. The hostname must point to an IP address that computer can reach. If Apache runs on the same computer, use `127.0.0.1`; if Apache runs on a separate Linux machine, VM, or other device, use that server's reachable LAN or VM IP instead. That IP can change after a network or VM restart.

1. **Find Apache's IP if it is on another computer.** Run the appropriate command **on the computer hosting Apache**. The Ubuntu/Debian commands apply to the provided Apache setup; the macOS command applies only if you independently configured Apache on macOS:

   ```bash

   # Linux Apache server:

   hostname -I

   # Or inspect network interfaces:

   ip -4 addr

   # macOS Apache server (usually Wi-Fi; try en1 or check network settings if blank):

   ipconfig getifaddr en0

   ```

   Select the address reachable from the macOS or Linux computer running your browser. Do not blindly copy an example address or use `127.0.0.1` for a different machine.

2. **Configure Apache to accept the hostname.** The included virtual host uses `ServerName carebp.local`. On the Apache server, edit the installed virtual host (for the documented Debian/Ubuntu setup, `/etc/apache2/sites-available/carebp.conf`) and add `ServerAlias carebp-local` inside its existing `<VirtualHost *:80>` block. Then validate and reload Apache **on Ubuntu/Debian Linux**:

   ```bash

   sudo apache2ctl configtest

   sudo systemctl reload apache2

   ```

   macOS clients do **not** run these Apache commands: editing `/etc/hosts` on macOS is a separate client-side step. If you installed Apache directly on macOS, configure and reload that installation according to its own service and virtual-host paths.

3. **Edit the hosts file on your macOS or Linux computer.** Open Terminal and run:

   ```bash

   sudo nano /etc/hosts

   ```

   Add **one** of these lines, depending on where Apache is running:

   ```text

   127.0.0.1 carebp-local       # Apache is on this same computer

   ```

   Or, if Apache is on another computer and its actual reachable IP is `192.168.1.50`:

   ```text

   192.168.1.50 carebp-local    # Replace with your server's actual IP

   ```

   Do not remove existing hosts-file entries. In nano, press **Ctrl+O**, **Enter**, and **Ctrl+X** to save and exit. This change is made on each client computer that needs to use `carebp-local`.

4. **Refresh name resolution and test.** On macOS, flush the DNS cache if the old IP is still being used:

   ```bash

   sudo dscacheutil -flushcache

   sudo killall -HUP mDNSResponder

   ```

   On **Ubuntu/Debian Linux**, hosts-file changes usually take effect immediately; if using `systemd-resolved` and the previous address persists, run `sudo resolvectl flush-caches`. Then use the verification command for the **browser computer's OS**:

   ```bash

   # Linux: verify the hosts-file resolution

   getent hosts carebp-local

   # macOS or Linux: verify the server responds on port 80 and serves the API

   curl -i http://carebp-local/api/health.php

   ```

   Open **http://carebp-local/** in your browser. The health endpoint should return HTTP `200` with `database: connected` after the database setup is complete. If the hostname resolves to the wrong IP, check `/etc/hosts` for stale or duplicate entries. If it resolves correctly but the site does not load, check Apache (`sudo apache2ctl -S` on Debian/Ubuntu), port 80, firewall rules, and VM networking. A hosts-file entry only maps a name to an IP; Apache still needs to be reachable and configured for that hostname.

### 6.3 Confirm that Apache serves both the frontend and API

```bash

curl -i http://carebp.local/api/health.php

# Expect HTTP 200 and JSON containing "status":"ok","database":"connected"

curl -s http://carebp.local/api/categories.php

curl -s 'http://carebp.local/api/facilities.php?zip=30904'

```

If the frontend works but `/api/` responds with 404, check that this virtual host is active and that its `/api/` alias points to the correct deployed directory. If PHP source appears in the browser or downloads instead of executing, disable access until you fix the Apache PHP handler.

### Public hosting is a separate deployment step

The supplied Apache config and HTTP example are **for local development**. Before exposing an installation to the Internet, use a real domain, configure HTTPS/TLS and appropriate access logs, verify the sponsor's location/medical data, and review server hardening and access permissions. Do not point Apache's `DocumentRoot` at `/var/www/carebp` or otherwise expose `.env`, SQL, or internal PHP files. The supplied admin and audit placeholders are not a secured management system.
