import toml
import json
import yaml
import os
import sys
import argparse

from buildtools import log, os_utils

class Animation(object):
    def __init__(self):
        self.ID = ''
        self.image = ''
        self.scripts = []
        self.data = None
        self.overridePlaylist = None
        self.overrideTemplate = None

    def serialize(self, suppress_id=False) -> dict:
        o = {}
        if not suppress_id:
            o['id'] = self.ID
        o['image'] = self.image
        if self.scripts is not None and len(self.scripts) > 0:
            o['scripts'] = self.scripts
        if self.data is not None:
            o['data'] = self.data
        if self.overridePlaylist is not None:
            o['playlist'] = self.overridePlaylist
        if self.overrideTemplate is not None:
            o['template'] = self.overrideTemplate
        return o

    def deserialize(self, data: dict) -> None:
        self.ID = data.get('id', self.ID)
        self.image = data.get('image', self.image)
        self.script = data.get('script', self.script)
        self.data = data.get('data', self.data)
        self.overridePlaylist = data.get('playlist', self.overridePlaylist)
        self.overrideTemplate = data.get('template', self.overrideTemplate)

class Pool(object):
    def __init__(self):
        self.ID = ''
        self.animations = []
        self.animationsByID = {}
        self.playlist = ''
        self.template = 'main'

    def serialize(self, suppress_id=False) -> dict:
        o = {
            'id': self.ID,
            'playlist': self.playlist,
            'template': self.template
        }
        if len(self.animations) > 0:
            o['animations'] = {x.ID: x.serialize(suppress_id=suppress_id) for x in self.animations}
        return o

    def deserialize(self, data: dict) -> None:
        self.ID = data.get('id', self.ID)
        self.playlist = data['playlist']
        self.template = data.get('template', self.template)
        for k, animdata in data.get('animations', {}).items():
            anim = Animation()
            anim.ID = k
            anim.deserialize(animdata)
            self.animations += [anim]
            self.animationsByID[anim.ID] = anim

    def add(self, anim:Animation) -> None:
        assert anim.ID not in self.animationsByID.keys()
        self.animations += [anim]
        self.animationsByID[anim.ID] = anim

def main():
    argp = argparse.ArgumentParser(prog='lobbytool', description="Manages lobby pools in VGWS")
    subp = argp.add_subparsers()

    p_collect = subp.add_parser('collect', help='Build lobby database')
    p_collect.set_defaults(cmd=_cmd_collect)

    p_create = subp.add_parser('create', help='Create new lobby pool')
    p_create.add_argument('ID', type=str, help="ID of the pool.")
    p_create.set_defaults(cmd=_cmd_create)

    p_set = subp.add_parser('set-anim', help='Set properties of a given animation in a given pool')
    p_set.add_argument('poolID', type=str, help="ID of the pool.")
    p_set.add_argument('animID', type=str, help="ID of the animation.")
    p_set.add_argument('--set-filename', type=str, default=None, help="Change filename.")
    p_set.add_argument('--override-playlist', type=str, default=None, help="Override playlist")
    p_set.add_argument('--add-script', type=str, nargs='*', help="Add JS script to run in browser when playing")
    p_set.add_argument('--rm-script', type=str, nargs='*', help="Remove JS script by filename")
    p_set.add_argument('--clear-scripts', action='store_true', default=False, help="Remove all JS scripts")
    p_set.set_defaults(cmd=_cmd_set)

    args = argp.parse_args()

    cmd = getattr(args, 'cmd', None)
    if cmd is None:
        argp.print_usage()
        sys.exit(1)
    cmd(args)

def _cmd_create(args):
    data = {
        'id': args.ID,
        'playlist': 'lobby',
        'template': 'main'
    }
    pooldir = os.path.join('lobbyscreens', args.ID)
    os_utils.ensureDirExists(pooldir, noisy=True)
    os_utils.ensureDirExists(os.path.join(pooldir, 'files'), noisy=True)
    written = []
    with open(os.path.join(pooldir, '__POOL__.yml'), 'w') as f:
        yaml.dump(data, f, default_flow_style=False)
        log.info('Wrote %s.', f.name)
        written += [f.name]

    with open('.gitignore', 'w') as f:
        f.write('/parsed.yml\n')
        written += [f.name]

    with os_utils.Chdir(pooldir):
        if not os.path.isdir('.git'):
            os_utils.cmd(['git', 'init'], echo=True, show_output=True, critical=True)
        os_utils.cmd(['git', 'lfs', 'install'], echo=True, show_output=True, critical=True)
        os_utils.cmd(['git', 'lfs', 'track', '*.png', '*.gif', '*.jpg', '*.webm', '*.webp'], echo=True, show_output=True, critical=True)
        os_utils.cmd(['git', 'add', '.gitattributes']+written, echo=True, show_output=True, critical=True)

def _cmd_set(args=None):
    pooldir = os.path.join('lobbyscreens', args.poolID)

    data = {}
    data = None
    datafile = os.path.join(pooldir, '__POOL__.yml')
    if os.path.isfile(datafile):
        with open(datafile, 'r') as f:
            data = yaml.safe_load(f)
            readfrom = f.name
    if data is None:
        log.critical('Could not find __POOL__.yml')
        sys.exit(1)
    pool = Pool()
    pool.ID = args.poolID
    pool.deserialize(data)
    poolfilesdir = os.path.join(pooldir, 'files')

    if args.animID in pool.animationsByID.keys():
        anim = pool.animationsByID[args.animID]
        if args.set_filename is not None:
            anim.filename = args.set_filename
    else:
        anim = Animation()
        anim.ID = args.animID
        anim.filename = args.set_filename or f'{args.animID}.gif'
        pool.animationsByID[args.animID] = anim
    if args.override_playlist is not None:
        anim.overridePlaylist = args.override_playlist
    for script in args.add_scripts:
        anim.scripts += [script]
    for script in args.rm_scripts:
        anim.scripts.remove(script)
    if args.clear_scripts:
        anim.scripts = []

    try:
        with open('__POOL__.tmp.yml', 'w') as f:
            yaml.dump(pool.serialize(), f, default_flow_style=False)
    finally:
        os.remove(readfrom)
        os_utils.single_copy('__POOL__.tmp.yml', '__POOL__.yml')
        os.remove('__POOL__.tmp.yml')

def _cmd_collect(args=None):
    allpools = {}
    for pooldirname in os.listdir('lobbyscreens'):
        pooldir = os.path.join('lobbyscreens', pooldirname)
        data = None
        datafile = os.path.join(pooldir, '__POOL__.yml')
        if os.path.isfile(datafile):
            with open(datafile, 'r') as f:
                data = yaml.safe_load(f)
        if data is None:
            continue
        pool = Pool()
        pool.ID = pooldirname
        pool.deserialize(data)
        poolfilesdir = os.path.join(pooldir, 'files')
        for imagebasename in os.listdir(poolfilesdir):
            basename, ext = os.path.splitext(imagebasename)
            #print(basename, ext)
            if ext not in ('.jpg', '.png', '.gif', '.svg'):
                #print('  SKIPPED')
                continue
            anim = Animation()
            anim.ID = basename
            data = None
            filedatapath = os.path.join(poolfilesdir, basename+'.yml')
            if os.path.isfile(filedatapath):
                with open(filedatapath, 'r') as f:
                    data = yaml.safe_load(f)
            filedatapath = os.path.join(poolfilesdir, basename+'.toml')
            if os.path.isfile(filedatapath):
                with open(filedatapath, 'r') as f:
                    data = toml.load(f)
            filedatapath = os.path.join(poolfilesdir, basename+'.json')
            if os.path.isfile(filedatapath):
                with open(filedatapath, 'r') as f:
                    data = json.load(f)
            if data is not None:
                anim.deserialize(data)
            anim.image = imagebasename
            fullpath = os.path.join(poolfilesdir, imagebasename)
            destfile = os.path.join('htdocs', 'img', 'lobby', pool.ID, anim.image)
            os_utils.ensureDirExists(os.path.dirname(destfile), noisy=False)
            os_utils.single_copy(fullpath, destfile, as_file=True, noisy=False)
            pool.add(anim)
        with open(os.path.join(pooldir, 'parsed.yml'), 'w') as f:
            yaml.dump(pool.serialize(suppress_id=True), f, default_flow_style=False)
        log.info('Found pool %r: %d animations', pool.ID, len(pool.animations))
        allpools[pool.ID] = pool.serialize()
    os_utils.ensureDirExists('data')
    with open('data/lobby.json', 'w') as f:
        json.dump(allpools, f, indent=2)

if __name__ == '__main__':
    main()
