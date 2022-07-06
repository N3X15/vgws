
export EMessageType =
  ERROR: 1
  WARNING: 2
  INFO: 0
export class Message
  constructor: (@Type, @Message, @File='', @Line=0) ->
    @TypeName = switch @Type
      when EMessageType.INFO
        'INFO'
      when EMessageType.WARNING
        'WARNING'
      when EMessageType.ERROR
        'ERROR'

  display: (cmi) ->
    lineinfo = ''
    if @File isnt '' and @Line > -1
      lineinfo = " in #{@File}:#{@Line}"
    text = "#{@Type}#{lineinfo}: #{@Message}"
    switch @Type
      when EMessageType.INFO
        cmi.Log.Info text
        cmi.addInfoMessage text
      when EMessageType.WARNING
        cmi.Log.Warn text
        cmi.addWarnMessage text
      when EMessageType.ERROR
        cmi.Log.Error text
        cmi.addErrorMessage text
    return
