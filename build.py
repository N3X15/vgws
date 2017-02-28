import __future__

import codecs
import hashlib
import json
import os
import re
import shutil
import sys

from buildtools import log, os_utils
from buildtools.config import YAMLConfig
from buildtools.maestro import BuildMaestro, ReplaceTextTarget
from buildtools.maestro.coffeescript import CoffeeBuildTarget
from buildtools.maestro.web import SCSSBuildTarget, SCSSConvertTarget

OUTDIR = os.path.join('js')
JSLIB = 'js-src/lib'

npm_packages = []
gem_packages = []
COFFEE = os_utils.which('coffee')
if COFFEE is None:
    npm_packages += ['coffee-script']
SASS = os_utils.which('sass')
if SASS is None:
    gem_packages += ['sass', 'compass']
SASS_CONVERT = os_utils.which('sass-convert')
if SASS_CONVERT is None:
    gem_packages += ['sass', 'compass']
if len(gem_packages) > 0:
    log.critical('Please run `gem update --system && gem install ' + ' '.join(gem_packages) + '`')
    sys.exit(1)
if len(npm_packages) > 0:
    log.critical('Please run `npm install -g ' + ' '.join(npm_packages) + '`')
    sys.exit(1)
NPM = os_utils.which('npm')
os_utils.ensureDirExists(OUTDIR)

os_utils.single_copy('bower_components/jquery/dist/jquery.min.js', 'htdocs/js/lib')
os_utils.single_copy('bower_components/jquery/dist/jquery.min.map', 'htdocs/js/lib')

bm = BuildMaestro()

#bm.add(CoffeeBuildTarget('htdocs/js/vgws.js',    ['coffee/src/vgws.coffee']))
bm.add(CoffeeBuildTarget('htdocs/js/editpoll.multichoice.js',    ['coffee/editpoll.multichoice.coffee']))
#bm.add(SCSSBuildTarget('htdocs/css/style.css', ['style/style.scss'], [], import_paths=['style'], compass=True))

bm.RecognizeType(SCSSBuildTarget)
bm.RecognizeType(SCSSConvertTarget)
bm.RecognizeType(CoffeeBuildTarget)
bm.RecognizeType(ReplaceTextTarget)

bm.saveRules('Makefile.pmk')
bm.loadRules('Makefile.pmk')
#bm.saveRules('Makefile.after.pmk')
bm.run()
