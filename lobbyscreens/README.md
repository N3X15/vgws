# HTML5 Lobby Screens
This directory's subdirectories is where you define *pools* of lobby screens and
their associated data.

## Pools
Each pool is a list of lobby screens (called *animations*), and each pool can
have an associated media server playlist, and an option for overriding the
default page twig template.

Pool data is defined by `__POOL__.yml` in the pool directory. An example TOML
file has been included.  **The default pool is named "main", so you should start
by creating the main pool.**

* **NOTE:** You should create each pool as an LFS-enabled gitlab repo and clone
it here to avoid unnecessarily inflating this repo's size. Do not use
submodules, otherwise they'll be added to the repo index.

### Example

```shell
# (In the repo root)
git clone https://gitlab.com/vgstation13/lobby-pool-main.git lobbyscreens/main
git clone https://gitlab.com/vgstation13/lobby-pool-snow.git lobbyscreens/snow
```

## Building

To put all this together, run `python3.6 lobbytool.py collect`

build.py also runs this tool for you.
