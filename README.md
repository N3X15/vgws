# HERE BE DRAGONS
**WARNING:** This is one of the shittiest, most hurried projects I have ever done,
and with a minimum of QC.  This code is old, and has bugs, undiscovered
vulnerabilities, and unfinished features up the wazoo.

**This code is currently completely broken due to abandoned libraries. I am fixing it.**

We're (eventually) moving to webpack and Symfony, too.

# /vg/station Web Services

*Oh god why did I ever code this:trade:*

This runs the public /vg/station website.  It is ancient shitcode.

## Installing

Please don't.

You will need
* A soul to donate to Satan
* A development PC
* A server to deploy to
* Python >= 3.8 (On your desktop)
* PHP >= 8.0 (On BOTH machines)
* `composer` (on BOTH machines)
* Node.js and `yarn` (On your desktop)
* `rsync` (desktop)
* `ssh` (desktop)
* An account that can:
  * read and write to the deployment directory
  * can run `composer` as the webserver without imploding
  * Can `chown` and `chmod` deployment directory stuff
  * *You know which one.*

```shell
# ===> ON YOUR PERSONAL PC <===

# Python dependencies
pip install -U poetry

# Clone to ./VGWS
git clone https://github.com/N3X15/vgws.git VGWS

# PHP configuration (replace $EDITOR with code, notepad++, vim, nano, etc)
cp config.php.dist config.php
$EDITOR config.php

# Build/deployment configuration
cp buildconf.example.yml buildconf.yml
$EDITOR buildconf.yml

# Install build dependencies
poetry install --no-root

# Run build
python3 devtools/build.py

# If build succeeded, you can run this shit to deploy to the server.
#RSYNC WILL DELETE FILES THAT IT DOESN'T RECOGNIZE IN
# classes/, templates/, style/, $PUBLIC_DIR/css, $PUBLIC_DIR/fonts, $PUBLIC_DIR/img, $PUBLIC_DIR/js.
#BACK UP YOUR SHIT.
python3 devtools/build.py --deploy
```
