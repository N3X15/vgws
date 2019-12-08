import os
import sys

from buildtools import log, os_utils
from buildtools.maestro import BuildMaestro
from buildtools.maestro.git import GitSubmoduleCheckTarget
from buildtools.maestro.base_target import SingleBuildTarget
from buildtools.maestro.fileio import CopyFileTarget, CopyFilesTarget
from buildtools.maestro.coffeescript import CoffeeBuildTarget
from buildtools.maestro.package_managers import YarnBuildTarget, ComposerBuildTarget
from buildtools.maestro.web import CacheBashifyFiles, DartSCSSBuildTarget

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
bm = BuildMaestro()

YARNLIB = os.path.join('node_modules')
HTDOCS_JSLIB = os.path.join('htdocs','js','lib')
HTDOCS_CSSLIB = os.path.join('htdocs','css', 'lib')
BOOTSTRAP_ROOT = os.path.join('vendor', 'twbs', 'bootstrap', 'dist', 'fonts')
FONTS = ['glyphicons-halflings-regular']
FONT_EXT = ['eot', 'svg', 'ttf', 'woff', 'woff2']
COFFEE = os.path.join(YARNLIB, '.bin', 'coffee')

submodules = bm.add(GitSubmoduleCheckTarget())

yarn_install = YarnBuildTarget()
bm.add(yarn_install)

composer_install = ComposerBuildTarget()
bm.add(composer_install)

for font_id in FONTS:
    for ext in FONT_EXT:
        basefilename = font_id + '.' + ext
        bm.add(CopyFileTarget(os.path.join('htdocs', 'fonts', basefilename), os.path.join(BOOTSTRAP_ROOT,basefilename), verbose=False))

JQUERY_ROOT = os.path.join(YARNLIB,'jquery')
bm.add(CopyFileTarget(os.path.join(HTDOCS_JSLIB, 'jquery.min.js'), os.path.join(JQUERY_ROOT, 'dist', 'jquery.min.js'), dependencies=[yarn_install.target]))
bm.add(CopyFileTarget(os.path.join(HTDOCS_JSLIB, 'jquery.min.map'), os.path.join(JQUERY_ROOT, 'dist', 'jquery.min.map'), dependencies=[yarn_install.target]))

TAGIT_ROOT = os.path.join('lib','tag-it.js')
# Tag-It
bm.add(CopyFileTarget(
    filename=os.path.join(TAGIT_ROOT, 'js', 'tag-it.min.js'),
    target=os.path.join(HTDOCS_JSLIB, 'tag-it.min.js')
))

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

JQUI_THEME_ROOT = os.path.join(YARNLIB,'jquery-ui-themes', 'themes', 'smoothness')
bm.add(CopyFileTarget(os.path.join(HTDOCS_CSSLIB, 'jquery-ui.min.css'), os.path.join(JQUI_THEME_ROOT, 'jquery-ui.min.css'), dependencies=[yarn_install.target]))
bm.add(CopyFilesTarget(os.path.join(bm.builddir, '.jqui_theme.target'), os.path.join(JQUI_THEME_ROOT, 'images'), os.path.join(HTDOCS_CSSLIB, 'images'), dependencies=[yarn_install.target]))

# cp -rv vendor/twbs/bootstrap-sass/assets/stylesheets/bootstrap/* style/bootstrap/
source = os.path.join('vendor','twbs','bootstrap-sass', 'assets', 'stylesheets', 'bootstrap')
bootstrap_files = bm.add(CopyFilesTarget(os.path.join(bm.builddir, '.bootstrap-sass.target'), source, os.path.join('style', 'bootstrap'), dependencies=[composer_install.target]))

#bm.add(CoffeeBuildTarget('htdocs/js/vgws.js',    ['coffee/src/vgws.coffee']))
bm.add(CoffeeBuildTarget(os.path.join('htdocs', 'js', 'editpoll.multichoice.js'), [os.path.join('coffee', 'editpoll.multichoice.coffee')], coffee_executable=COFFEE))
bm.add(CoffeeBuildTarget(os.path.join('htdocs', 'js', 'editpoll.option.js'),      [os.path.join('coffee', 'editpoll.multichoice.coffee')], coffee_executable=COFFEE))
bm.add(DartSCSSBuildTarget(
    target='htdocs/css/style.css',
    files=['style/style.scss'],
    dependencies=[
        yarn_install.target,
        bootstrap_files.target
    ],
    import_paths=['style'],
    imported=['style/_funcs.scss'],
    sass_path=os.path.join(YARNLIB, '.bin', 'dart-sass' + ('.cmd' if os_utils.is_windows() else ''))))
bm.as_app()
