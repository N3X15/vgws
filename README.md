# HERE BE DRAGONS
**WARNING:** This is one of the shittiest, most hurried projects I have ever done,
and with a minimum of QC.  This code is old, and has bugs, undiscovered
vulnerabilities, and unfinished features up the wazoo.

I am slowly cleaning it up, but until I do, **you use VGWS at your own risk**.

# /vg/station Web Services
*Oh god why did I ever code this*

THis runs the public /vg/station website.  It is ancient shitcode.

## Installing

Please don't.

You will need
* A soul to donate to Satan
* A development PC
* A server to deploy to
* Python >= 3.6 (On your desktop)
* PHP >= 7.3 (On BOTH machines)
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
git clone https://github.com/N3X15/vgws.git VGWS
# PHP configuration
cp config.php.dist config.php
$EDITOR config.php
# Build/deployment configuration
cp buildconf.example.yml buildconf.yml
$EDITOR buildconf.yml
# Install N3X15's buildtools
pip install git+https://gitlab.com/N3X15/python-build-tools.git#egg=pybuildtools
# Run build
python3.6 build.py
# If build succeeded, you can run this shit to deploy to the server.
#RSYNC WILL DELETE FILES THAT IT DOESN'T RECOGNIZE IN
# classes/, templates/, style/, $PUBLIC_DIR/css, $PUBLIC_DIR/fonts, $PUBLIC_DIR/img, $PUBLIC_DIR/js.
#BACK UP YOUR SHIT.
python3.6 build.py --deploy
```
