import {VGWSCore} from './VGWSCore.coffee'
import {VGWSLogProxy} from './VGWSLogProxy.coffee'
export log = new VGWSLogProxy
export core = new VGWSCore
$ ->
  core.bodyIsReady = yes
  for cb in core.bodyCallbacks
    cb()
  return
