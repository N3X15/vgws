import hashlib
import os
import sys
from pathlib import Path

from buildtools import log, os_utils
from buildtools.config import YAMLConfig
from buildtools.maestro import BuildMaestro
from buildtools.maestro.base_target import SingleBuildTarget
from buildtools.maestro.coffeescript import CoffeeBuildTarget
from buildtools.maestro.fileio import CopyFilesTarget, CopyFileTarget, ExtractArchiveTarget
from buildtools.maestro.git import GitSubmoduleCheckTarget
from buildtools.maestro.package_managers import ComposerBuildTarget, YarnBuildTarget
from buildtools.maestro.shell import CommandBuildTarget
from buildtools.maestro.web import CacheBashifyFiles, DartSCSSBuildTarget, DownloadFileTarget, EBashLayoutFlags, UglifyJSTarget, WebifyTarget


class RSyncRemoteTarget(SingleBuildTarget):
    BT_LABEL = 'RSYNC'

    def __init__(self, sources, destination, rsync_executable=None, progress=False, delete=False, opts=['-Rruhavp'], chmod=0o755, chown=None, chgrp=None, show_output=False, dependencies=[], provides=[], name='rsync', keyfile=None):
        self.rsync_executable = rsync_executable or os_utils.which('rsync')
        self.opts = opts
        self.progress = progress
        self.chmod = chmod
        self.chown = chown
        self.chgrp = chgrp
        self.show_output = show_output
        self.progress = progress
        self.sources = sources
        self.delete = delete
        self.destination = destination
        self.keyfile = keyfile
        files = []
        for source in sources:
            with log.info('Scanning %s...', source):
                if os.path.isdir(source):
                    files += os_utils.get_file_list(source)
                if os.path.isfile(source):
                    files += [source]
        super().__init__(target=str(Path('.build', hashlib.sha256(self.name).hexdigest()+'.target')), files=files, dependencies=dependencies, provides=provides, name=name)

    def is_stale(self):
        return True

    def build(self):
        # call rsync -Rrav --progress *.mp3 root@ss13.nexisonline.net:/host/ss13.nexisonline.net/htdocs/media/
        cmd = [self.rsync_executable]
        cmd += self.opts
        if self.progress:
            cmd += ['--progress']
        if self.delete:
            cmd += ['--delete', '--delete-before']
        if self.chmod != None:
            cmd += [f'--chmod={self.chmod:o}']
        if self.chown != None:
            chown = self.chown
            if self.chgrp is not None:
                chown += ':' + self.chgrp
            cmd += [f'--chown={chown}']
        if self.keyfile != None:
            keypath = self.keyfile
            os_utils.cmd(['chmod','400',keypath], echo=True, show_output=True, critical=True)
            if os_utils.is_windows() and ':' in keypath:
                #keypath = os_utils.cygpath(keypath)
                keypath = keypath.replace('\\','/')
            cmd += ['-e', f"ssh -i {keypath}"]
        cmd += [x.replace('\\', '/') for x in self.sources]
        cmd += [self.destination]

        os_utils.cmd(cmd, show_output=self.show_output, echo=self.should_echo_commands(), critical=True, acceptable_exit_codes=[0, 23])
        self.touch(self.target)

class JQueryUIBuildTarget(SingleBuildTarget):
    BT_LABEL = 'JQUERYUI'
    def __init__(self, base_path):
        self.base_path = base_path
        self.all_files = []
        for root, _, files in os.walk(self.base_path):
            for basefilename in files:
                self.all_files.append(os.path.join(root,basefilename))
        super().__init__(target=os.path.join(self.base_path,'dist','jquery-ui.min.js'), files=self.all_files[:100])

    def build(self):
        with os_utils.Chdir(os.path.join(self.base_path)):
            os_utils.cmd([os_utils.which('yarn')], echo=True, show_output=True, critical=True)
            os_utils.cmd([os_utils.which('grunt'), 'requirejs', 'uglify:main'], echo=True, show_output=True, critical=True)

os_utils.ENV.prependTo('PATH', os.path.join('node_modules','.bin'))

cfg = YAMLConfig('buildconf.yml')

bm = BuildMaestro()

# We add additional arguments to the script here
argp = bm.build_argparser()
argp.add_argument('--no-minify', action='store_true', default=False, help="Don't minify anything.")
argp.add_argument('--deploy', action='store_true', help='Also run rsync/SSH deployment steps.')
# Parse args
args = bm.parse_args(argp)

CMD_EXT = '.cmd' if os.name == 'nt' else ''

YARNLIB = Path('node_modules')
HTDOCS = Path('htdocs')
VENDOR = Path('vendor')
HTDOCS_FONTLIB = HTDOCS / 'fonts' / 'lib'
HTDOCS_JSLIB = HTDOCS / 'js' / 'lib'
HTDOCS_CSSLIB = HTDOCS / 'css' / 'lib'
BOOTSTRAP_ROOT = VENDOR / 'twbs' / 'bootstrap-sass' / 'assets' / 'fonts' / 'bootstrap'
FONTS = ['glyphicons-halflings-regular']
FONT_EXT = ['eot', 'svg', 'ttf', 'woff', 'woff2']
COFFEE = YARNLIB / '.bin' / ('coffee'+CMD_EXT)
UGLIFY = YARNLIB / '.bin' / ('uglifyjs'+CMD_EXT)
MANIFEST_OUT = HTDOCS / 'manifest.json'

#if os.path.isfile(MANIFEST_OUT):
#    with open(MANIFEST_OUT, 'w') as f:
#        f.write('{}')
js_targets = []

submodules = bm.add(GitSubmoduleCheckTarget())

yarn_install = YarnBuildTarget()
bm.add(yarn_install)

composer_install = ComposerBuildTarget()
bm.add(composer_install)

for font_id in FONTS:
    for ext in FONT_EXT:
        basefilename = font_id + '.' + ext
        bm.add(CopyFileTarget(os.path.join('htdocs', 'fonts', basefilename), os.path.join(BOOTSTRAP_ROOT,basefilename), verbose=False))
#bm.add(CopyFileTarget(os.path.join('htdocs', 'fonts', 'BebasNeue.otf'), os.path.join('lib','bebas neue', 'BebasNeue.otf'), verbose=False))

JQUERY_ROOT = os.path.join(YARNLIB, 'jquery')
js_targets += [
    bm.add(CopyFileTarget(os.path.join(HTDOCS_JSLIB, 'jquery.min.js'), os.path.join(JQUERY_ROOT, 'dist', 'jquery.min.js'), dependencies=[yarn_install.target])),
    bm.add(CopyFileTarget(os.path.join(HTDOCS_JSLIB, 'jquery.min.map'), os.path.join(JQUERY_ROOT, 'dist', 'jquery.min.map'), dependencies=[yarn_install.target]))
]

TAGIT_ROOT = os.path.join('lib','tag-it.js')
# Tag-It
js_targets += [
    bm.add(CopyFileTarget(
        filename=os.path.join(TAGIT_ROOT, 'js', 'tag-it.min.js'),
        target=os.path.join(HTDOCS_JSLIB, 'tag-it.min.js')
    ))
]

BC_LAYOUT = EBashLayoutFlags.NAME | EBashLayoutFlags.HASHDIR
def bashCache(filename, basedir='htdocs', destdir='htdocs', outdirname=None):
    return bm.add(CacheBashifyFiles(destdir=destdir,
                                    source=filename,
                                    manifest=MANIFEST_OUT,
                                    basedirsrc=basedir,
                                    basedirdest=outdirname,
                                    dependencies=[filename],
                                    flags=BC_LAYOUT))

def mkCoffee(basename, dependencies=[], files=None, babel=False, minify=True):
    global js_targets
    if files is None:
        files=[os.path.join('coffee', f'{basename}.coffee')]
    built_js = bm.add(CoffeeBuildTarget(target=os.path.join('tmp', 'js', f'{basename}.js'),
                                        files=files,
                                        coffee_executable=COFFEE,
                                        make_map=False,
                                        dependencies=[yarn_install.target]+dependencies))
    if babel:
        built_js.coffee_opts += ['--transpile'] # Babel
    bashed = bashCache(built_js.target, basedir='tmp', outdirname='js')
    js_targets += [bashed]
    if not args.no_minify and minify:
        built_js = bm.add(UglifyJSTarget(target=os.path.join('tmp', 'js', f'{basename}.min.js'),
                                         inputfile=built_js.target,
                                         dependencies=[yarn_install.target, built_js.target],
                                         mangle=False,
                                         compress_opts=['keep_fnames,unsafe'],
                                         uglify_executable=UGLIFY))

        bashed = bashCache(built_js.target, basedir='tmp', outdirname='js')
        js_targets += [bashed]
    return bashed

# JQuery UI
# Shit I'm not doing yet...
'''
JQUERYUI_THEME = os.path.join(JSLIB, 'jquery-ui-themes', 'themes', 'base')
JQUERYUI_BASE_THEME = os.path.join(JSLIB, 'jquery-ui-themes', 'themes', 'base')
theme_vared = bm.add(ReplaceTextTarget('tmp/theme.varified.css', os.path.join(JSLIB, 'jquery-ui', 'themes', 'base', 'theme.css'), {
    r'([\s]+[\S]+| )\/\*\{([^\}\*\/]+)\}\*\/': ' --var-jq_ui_\\2'
}))
theme_datafied = bm.add(DatafyImagesTarget('tmp/theme.fixed.css',     theme_vared.target,     basedir=JQUERYUI_THEME))
theme_converted = bm.add(SCSSConvertTarget(os.path.join('tmp', '_theme2__gen.scss'), [theme_datafied.target], [theme_datafied.target]))
theme_var2 = bm.add(ReplaceTextTarget('tmp/tag-it.fixed.css', theme_converted.target, {
    r'\-\-var\-': '$'
}))
'''
# JQUI comes precompiled but only as a ZIP on a webpage. So first, we download it.
# I made this target class just for you pomf.  Just for you.
jqui_dl=bm.add(DownloadFileTarget(target=os.path.join('tmp', 'jquery-ui-1.12.1.zip'),
                                  url='https://jqueryui.com/resources/download/jquery-ui-1.12.1.zip'))
# Now we extract it.  Another Pomf special class.
jqui_extract = bm.add(ExtractArchiveTarget(target_dir=os.path.join('tmp', 'jquery-ui'),
                                           archive=jqui_dl.target,
                                           provides=[os.path.join('tmp', 'jquery-ui', 'jquery-ui-1.12.1', 'jquery-ui.js')]))
js_targets += [bm.add(CopyFileTarget(
    target=os.path.join(HTDOCS_JSLIB, 'jquery-ui.min.js'),
    filename=os.path.join('tmp', 'jquery-ui', 'jquery-ui-1.12.1', 'jquery-ui.min.js'),
    dependencies=[jqui_extract.target]
))]
js_targets += [bm.add(CopyFileTarget(
    target=os.path.join(HTDOCS_JSLIB, 'URLSearchParams.polyfill.js'),
    filename=os.path.join(YARNLIB, 'url-search-params-polyfill', 'index.js'),
    dependencies=[jqui_extract.target]
))]


js_targets += [bm.add(CopyFileTarget(
    target=os.path.join(HTDOCS_JSLIB, 'String.polyfill.js'),
    filename=os.path.join('lib', 'polyfill', 'string.polyfill.js'),
    dependencies=[jqui_extract.target]
))]
js_targets += [bm.add(CopyFileTarget(
    target=os.path.join(HTDOCS_JSLIB, 'Array.polyfill.js'),
    filename=os.path.join('lib', 'polyfill', 'array.polyfill.js'),
    dependencies=[jqui_extract.target]
))]
#js_targets += [bm.add(UglifyJSTarget(
#    inputfile=os.path.join('tmp', 'jquery-ui', 'jquery-ui-1.12.1', 'jquery-ui.js'),
#    target=os.path.join(HTDOCS_JSLIB, 'jquery-ui.min.js'),
#    mangle=False,
#    compress_opts=['keep_fnames,unsafe'],
#    uglify_executable=UGLIFY,
#    dependencies=[jqui_extract.target]
#))]
JQUI_THEME_ROOT = os.path.join(YARNLIB,'jquery-ui-themes', 'themes', 'smoothness')
bm.add(CopyFileTarget(os.path.join(HTDOCS_CSSLIB, 'jquery-ui.min.css'), os.path.join(JQUI_THEME_ROOT, 'jquery-ui.min.css'), dependencies=[yarn_install.target]))
bm.add(CopyFilesTarget(os.path.join(bm.builddir, '.jqui_theme.target'), os.path.join(JQUI_THEME_ROOT, 'images'), os.path.join(HTDOCS_CSSLIB, 'images'), dependencies=[yarn_install.target]))

# cp -rv vendor/twbs/bootstrap-sass/assets/stylesheets/bootstrap/* style/bootstrap/
source = os.path.join('vendor','twbs','bootstrap-sass', 'assets', 'stylesheets', 'bootstrap')
bootstrap_files = bm.add(CopyFilesTarget(os.path.join(bm.builddir, '.bootstrap-sass.target'), source, os.path.join('style', 'bootstrap'), dependencies=[composer_install.target]))

# coffee/editpoll.coffee -> tmp/js/editpoll.js -> tmp/js/editpoll.min.js -> htdocs/js/ab/cd/editpoll_min-{md5sum[4:]}.js
mkCoffee('bans')
mkCoffee('editpoll')
mkCoffee('rapsheet')
mkCoffee('byond', babel=True, minify=False)
mkCoffee('lobby-core', babel=True, files=[
    os.path.join('coffee', 'lobby', 'players', 'Player.coffee'),
    os.path.join('coffee', 'lobby', 'players', 'Image.coffee'),
    os.path.join('coffee', 'lobby', 'players', 'Video.coffee'),
    os.path.join('coffee', 'lobby', 'core.coffee'),
    os.path.join('coffee', 'lobby', 'tinkermenu.coffee'),
    os.path.join('coffee', 'lobby', 'zzStartup.coffee'),
])
mkCoffee('core', babel=True, files=[
    os.path.join('coffee', 'core', '_functions.coffee'),
    os.path.join('coffee', 'core', 'JQConfirm.coffee'),
    os.path.join('coffee', 'core', 'JQPrompt.coffee'),
    os.path.join('coffee', 'core', 'Message.coffee'),
    os.path.join('coffee', 'core', 'Modal.coffee'),
    os.path.join('coffee', 'core', 'VGWSCore.coffee'),
    os.path.join('coffee', 'core', 'VGWSLogProxy.coffee'),
    os.path.join('coffee', 'core', 'zzStartup.coffee'),
])

style = bm.add(DartSCSSBuildTarget(
    target='htdocs/css/style.css',
    files=['style/style.scss'],
    dependencies=[
        yarn_install.target,
        bootstrap_files.target
    ],
    import_paths=['style'],
    imported=['style/_funcs.scss'],
    sass_path=os.path.join(YARNLIB, '.bin', 'sass' + ('.cmd' if os_utils.is_windows() else ''))))

style_bashed = bashCache(style.target, outdirname='css')

bm.add(CopyFilesTarget(target=os.path.join(bm.builddir, 'imgs-to-htdocs.tgt'),
                       source=os.path.join('img'),
                       destination=os.path.join('htdocs', 'img')))


lobbytool = bm.add(CommandBuildTarget([os.path.join('htdocs', 'data', 'lobby.json')], files=['lobbyscreens', 'main', '__POOL__.toml'], cmd=[sys.executable, 'devtools/lobbytool.py', 'collect']))
#bm.add(CopyFileTarget(target=os.path.join('dist', 'data', 'jobs.json'),
#                      filename=os.path.join('data', 'jobs.json')))

fonts = []
''' Webify is currently dead.

    bm.add(WebifyTarget(destination=os.path.join(HTDOCS_FONTLIB, 'bebas-neue', 'bebas-neue-regular.woff'),
                        source=os.path.join('lib', 'bebas neue', 'BebasNeue Regular.otf'),
                        webify_base_path='lib/bin/')),
    bm.add(WebifyTarget(destination=os.path.join(HTDOCS_FONTLIB, 'bebas-neue', 'bebas-neue-bold.woff'),
                        source=os.path.join('lib', 'bebas neue', 'BebasNeue Bold.otf'),
                        webify_base_path='lib/bin/')),
    bm.add(WebifyTarget(destination=os.path.join(HTDOCS_FONTLIB, 'bebas-neue', 'bebas-neue-thin.woff'),
                        source=os.path.join('lib', 'bebas neue', 'BebasNeue Thin.otf'),
                        webify_base_path='lib/bin/')),
'''
#]
# This requires having ssh access and rsync.
if args.deploy:
    def deploy_dir(bm, dirname, is_private=False, deps=[]):
        host = cfg.get('servers.deploy.host', None)
        user = cfg.get('servers.deploy.user', None)
        if user is None or host is None:
            return None
        prefix = '.' if is_private else cfg.get('paths.PUBLIC_OUT', '.')
        dest = os.path.join(cfg.get('paths.APP_ROOT', None), '' if prefix == '.' else prefix, dirname).replace('\\', '/')
        deployuri = f'{user}@{host}:{dest}'
        return bm.add(RSyncRemoteTarget(
            ['/'.join(['dist', '' if prefix == '.' else prefix, dirname, '.', '.'])],
            deployuri,
            name=dest,
            keyfile=KEYFILE,
            show_output=True,
            chown=cfg.get('servers.deploy.chown.user', 'www-data'),
            chgrp=cfg.get('servers.deploy.chown.group', 'www-data'),
            delete=True,
            dependencies=deps))

    KEYFILE = cfg.get('servers.deploy.keyfile', None)
    PUBLIC_OUT = cfg.get('paths.PUBLIC_OUT', '.')
    PUBLIC_DIRS = [
        'js',
        #'src',
        'css',
        'img',
        'fonts',
        #'svg',
    ]
    PRIVATE_DIRS = [
        'style',
        'classes',
        'templates',
        'data',
    ]
    PUBLIC_FILES = [
        'index.php',
        'style.php',
        'api.php',
        #'manifest.json'
    ]

    PRIVATE_FILES = [
        'composer.json',
        'composer.lock'
    ]
    private_dir_ops=[]
    for basedir in PRIVATE_DIRS:
        private_dir_ops += [bm.add(CopyFilesTarget(target=os.path.join(bm.builddir, f'{basedir}-to-private.tgt'),
                                                   source=os.path.join(basedir),
                                                   destination=os.path.join('dist', basedir))).target]

    private_file_ops=[]
    for basefilename in PRIVATE_FILES:
        private_file_ops += [bm.add(CopyFileTarget(target=os.path.join('dist', basefilename),
                                                   filename=os.path.join(basefilename)))]

    public_dir_ops=[]
    for basedir in PUBLIC_DIRS:
        public_dir_ops += [bm.add(CopyFilesTarget(target=os.path.join(bm.builddir, f'{basedir}-to-public.tgt'),
                                                  source=os.path.join('htdocs', basedir),
                                                  destination=os.path.join('dist', PUBLIC_OUT, basedir))).target]

    public_file_ops=[]
    for basefilename in PUBLIC_FILES:
        public_file_ops += [bm.add(CopyFileTarget(target=os.path.join('dist', PUBLIC_OUT, basefilename),
                                                  filename=os.path.join(basefilename)))]
    public_file_ops += [bm.add(CopyFileTarget(target=os.path.join('dist', PUBLIC_OUT, 'manifest.json'),
                                              filename=os.path.join('htdocs', 'manifest.json'),
                                              dependencies=[x.target for x in js_targets]))]

    BT_MAIN = [lobbytool]
    #clean = maestro.add(CommandBuildTarget(targets=['@clean'], files=[], cmd=['ssh', '-i', KEYFILE, 'root@192.168.9.5', 'cd /host/ws-tux-001/htdocs/chanman && bash ./clean.sh'], show_output=False, echo=False))
    for dirname in PUBLIC_DIRS:
        deps = []
        if dirname == 'js':
            deps = [x.target for x in js_targets]
        if dirname == 'css':
            deps = [style_bashed.target]
        #if dirname == 'fonts':
        #    deps = [fonts[0].target]
        BT_MAIN += [deploy_dir(bm, dirname, is_private=False, deps=public_dir_ops+deps)]

    for dirname in PRIVATE_DIRS:
        deps = []
        if dirname in ('classes', 'vendor'):
            deps = [composer_install.target]
        BT_MAIN += [deploy_dir(bm, dirname, is_private=True, deps=private_dir_ops+deps)]

    host = cfg.get('servers.deploy.host', None)
    user = cfg.get('servers.deploy.user', None)
    loose_files = []
    prefix = cfg.get('paths.PUBLIC_OUT', '.')
    for filename in PUBLIC_FILES:
        loose_files += ['/'.join(['dist', prefix, '.', filename])]
    loose_files += ['/'.join(['dist', prefix, '.', 'manifest.json'])]
    dest = os.path.join(cfg.get('paths.APP_ROOT', None), prefix).replace('\\', '/')
    deployuri = f'{user}@{host}:{dest}'
    BT_MAIN += [bm.add(RSyncRemoteTarget(loose_files, deployuri, name='loose-public', keyfile=KEYFILE, dependencies=[] + [x.target for x in js_targets+public_file_ops], show_output=False))]

    loose_files = []
    for filename in PRIVATE_FILES:
        loose_files += ['/'.join(['dist', '.', filename])]
    dest = os.path.join(cfg.get('paths.APP_ROOT', None)).replace('\\', '/')
    deployuri = f'{user}@{host}:{dest}'
    loose_private = bm.add(RSyncRemoteTarget(loose_files, deployuri, name='loose-private', keyfile=KEYFILE, dependencies=[], show_output=True))
    BT_MAIN += [loose_private]
    loose_private.dependencies += [style.target]+[x.target for x in js_targets]

    app_root = cfg.get('paths.APP_ROOT', None)
    chuser = cfg.get('servers.deploy.chown.user', 'www-data')
    chgroup = cfg.get('servers.deploy.chown.group', 'www-data')
    commands = [
        #f'rm -rfv {app_root}/data/cache/tag{{cache,id2name,name2id}}{{,.json}}',
        f'mkdir -pv {app_root}/style/scss_cache',
        #f'chown -R {chuser}:{chgroup} {app_root}/composer.json {app_root}/composer.lock {app_root}/vendor{app_root}/vendor',
        f'chown -R {chuser}:{chgroup} {app_root}',
        #f'chmod +x {app_root}/lib/php/bin/phinx',
        f'cd {app_root}',
        f'sudo -u {chuser} composer i -o --no-interaction --no-dev',
        f'cd -',
    ]
    keypath = KEYFILE
    if os_utils.is_windows() and ':' in keypath:
        keypath = keypath.replace('\\','/')
    allbts = []
    for x in BT_MAIN:
        if x is not None:
            allbts += x.provides()
    bm.add(CommandBuildTarget(
        targets=['@after'],
        files=allbts,
        cmd=['ssh', '-i', keypath, f'{user}@{host}', ' && '.join(commands)],
        dependencies=allbts,
        show_output=True,
        echo=False))
bm.as_app(argp)
