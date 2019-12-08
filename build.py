import os
import sys

from buildtools import log, os_utils
from buildtools.maestro import BuildMaestro
from buildtools.maestro.base_target import SingleBuildTarget
from buildtools.maestro.fileio import CopyFileTarget, CopyFilesTarget
from buildtools.maestro.coffeescript import CoffeeBuildTarget
from buildtools.maestro.nodejs import YarnBuildTarget, ComposerBuildTarget

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
            os_utils.cmd([os_utils.which('npm'), 'install'], echo=True, show_output=True, critical=True)
            os_utils.cmd([os_utils.which('grunt'), 'requirejs', 'uglify:main'], echo=True, show_output=True, critical=True)

bm = BuildMaestro()

HTDOCS_JSLIB = os.path.join('htdocs','js','lib')
HTDOCS_CSSLIB = os.path.join('htdocs','css', 'lib')
BOOTSTRAP_ROOT = os.path.join('vendor', 'twbs', 'bootstrap', 'dist', 'fonts')
FONTS = ['glyphicons-halflings-regular']
FONT_EXT = ['eot', 'svg', 'ttf', 'woff', 'woff2']

yarn_install = YarnBuildTarget(modules_dir=os.path.join('lib','js'))
bm.add(yarn_install)

composer_install = ComposerBuildTarget()
bm.add(composer_install)

for font_id in FONTS:
    for ext in FONT_EXT:
        basefilename = font_id + '.' + ext
        bm.add(CopyFileTarget(os.path.join('htdocs', 'fonts', basefilename), os.path.join(BOOTSTRAP_ROOT,basefilename), verbose=False))

JQUERY_ROOT = os.path.join('lib','js','jquery')
bm.add(CopyFileTarget(os.path.join(HTDOCS_JSLIB, 'jquery.min.js'), os.path.join(JQUERY_ROOT, 'dist', 'jquery.min.js'), dependencies=[yarn_install.target]))
bm.add(CopyFileTarget(os.path.join(HTDOCS_JSLIB, 'jquery.min.map'), os.path.join(JQUERY_ROOT, 'dist', 'jquery.min.map'), dependencies=[yarn_install.target]))

JQUERY_UI_ROOT = os.path.join('lib','js','jquery-ui')
jqui_builder = JQueryUIBuildTarget(JQUERY_UI_ROOT)
bm.add(jqui_builder)
bm.add(CopyFileTarget(os.path.join(HTDOCS_JSLIB, 'jquery-ui.min.js'), jqui_builder.target, dependencies=[jqui_builder.target, yarn_install.target]))

TAGIT_ROOT = os.path.join('lib','js','tag-it')
bm.add(CopyFileTarget(os.path.join(HTDOCS_JSLIB, 'tag-it.min.js'), os.path.join(TAGIT_ROOT, 'js', 'tag-it.min.js'), dependencies=[yarn_install.target]))
#bm.add(CopyFileTarget(os.path.join(HTDOCS_JSLIB, 'jquery.min.map'), os.path.join(TAGIT_ROOT, 'dist', 'tagit.min.map'))) # I wish.

JQUI_THEME_ROOT = os.path.join('lib','js','jquery-ui-themes', 'themes', 'smoothness')
bm.add(CopyFileTarget(os.path.join(HTDOCS_CSSLIB, 'jquery-ui.min.css'), os.path.join(JQUI_THEME_ROOT, 'jquery-ui.min.css'), dependencies=[yarn_install.target]))
bm.add(CopyFilesTarget(os.path.join(bm.builddir, '.jqui_theme.target'), os.path.join(JQUI_THEME_ROOT, 'images'), os.path.join(HTDOCS_CSSLIB, 'images'), dependencies=[yarn_install.target]))

# cp -rv vendor/twbs/bootstrap-sass/assets/stylesheets/bootstrap/* style/bootstrap/
source = os.path.join('vendor','twbs','bootstrap-sass', 'assets', 'stylesheets', 'bootstrap')
bm.add(CopyFilesTarget(os.path.join(bm.builddir, '.bootstrap-sass.target'), os.path.join('style', 'bootstrap'), source, dependencies=[composer_install.target]))

#bm.add(CoffeeBuildTarget('htdocs/js/vgws.js',    ['coffee/src/vgws.coffee']))
bm.add(CoffeeBuildTarget(os.path.join('htdocs', 'js', 'editpoll.multichoice.js'), [os.path.join('coffee', 'editpoll.multichoice.coffee')]))
bm.add(CoffeeBuildTarget(os.path.join('htdocs', 'js', 'editpoll.option.js'),      [os.path.join('coffee', 'editpoll.multichoice.coffee')]))
#bm.add(SCSSBuildTarget('htdocs/css/style.css', ['style/style.scss'], [], import_paths=['style'], compass=True))
bm.as_app()
