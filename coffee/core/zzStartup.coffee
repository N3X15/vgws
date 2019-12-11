log = new VGWSLogProxy
core = new VGWSCore
$ ->
  core.bodyIsReady = yes
  for cb in core.bodyCallbacks
    cb()
  return
