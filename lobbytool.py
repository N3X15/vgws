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
    p_create.add_argument('--format', choices=['toml', 'yaml', 'json'], default='toml', help='Format of the __POOL__ file')
    p_create.add_argument('ID', type=str, help="ID of the pool.")
    p_create.set_defaults(cmd=_cmd_create)

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
    if args.format == 'toml':
        with open(os.path.join(pooldir, '__POOL__.toml'), 'w') as f:
            toml.dump(data, f)
            log.info('Wrote %s.', f.name)
    if args.format == 'yaml':
        with open(os.path.join(pooldir, '__POOL__.yml'), 'w') as f:
            yaml.dump(data, f, default_flow_style=False)
            log.info('Wrote %s.', f.name)
    if args.format == 'json':
        with open(os.path.join(pooldir, '__POOL__.json'), 'w') as f:
            json.dump(data, f, indent=2)
            log.info('Wrote %s.', f.name)

def _cmd_collect(args=None):
    allpools = {}
    for pooldirname in os.listdir('lobbyscreens'):
        pooldir = os.path.join('lobbyscreens', pooldirname)
        data = None
        datafile = os.path.join(pooldir, '__POOL__.toml')
        if os.path.isfile(datafile):
            with open(datafile, 'r') as f:
                data = toml.load(f)
        datafile = os.path.join(pooldir, '__POOL__.yml')
        if os.path.isfile(datafile):
            with open(datafile, 'r') as f:
                data = yaml.load(f)
        datafile = os.path.join(pooldir, '__POOL__.json')
        if os.path.isfile(datafile):
            with open(datafile, 'r') as f:
                data = json.load(f)
        if data is None:
            continue
        pool = Pool()
        pool.ID = pooldirname
        pool.deserialize(data)
        poolfilesdir = os.path.join(pooldir, 'files')
        for imagebasename in os.listdir(poolfilesdir):
            basename, ext = os.path.splitext(imagebasename)
            if ext not in ('.jpg', '.png', '.gif'):
                continue
            anim = Animation()
            anim.ID = basename
            data = None
            filedatapath = os.path.join(poolfilesdir, basename+'.yml')
            if os.path.isfile(filedatapath):
                with open(filedatapath, 'r') as f:
                    data = yaml.load(f)
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
            destfile = os.path.join('htdocs', anim.image)
            os_utils.ensureDirExists(os.path.dirname(destfile), noisy=False)
            os_utils.single_copy(fullpath, destfile, as_file=True, noisy=False)
            pool.add(anim)
        with open(os.path.join(pooldir, 'parsed.toml'), 'w') as f:
            toml.dump(pool.serialize(suppress_id=True), f)
        log.info('Found pool %r: %d animations', pool.ID, len(pool.animations))
        allpools[pool.ID] = pool.serialize()
    os_utils.ensureDirExists('data')
    with open('data/lobby.json', 'w') as f:
        json.dump(allpools, f, indent=2)

if __name__ == '__main__':
    main()
